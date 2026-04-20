<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module CMSContentManager action
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
#END_LICENSE

use CMSContentManager\ListOperations;
use CMSContentManager\ContentListFilter;

if( !isset($gCms) ) exit;
// no permissions checks here.

echo '<noscript><h3 style="color: red; text-align: center;">'.$this->Lang('info_javascript_required').'</h3></noscript>'."\n";
$error = '';

$modname = $this->GetName();
$userid = get_userid();

$ajax = 0;
if( isset($params['ajax']) ) $ajax = 1;
if( isset($params['curpage']) ) {
    $curpage = max(1,min(500,(int)$params['curpage'])); // 500 is arbitrary guess
}

$builder = new ListOperations($this);
if( isset($params['expandall']) || isset($_GET['expandall']) ) {
    $builder->expand_all();
    $curpage = 1;
}
else if( isset($params['collapseall']) || isset($_GET['collapseall']) ) {
    $builder->collapse_all();
    $curpage = 1;
}
$settings = [];
if( isset($params['setoptions']) ) {
    if( $params['setoptions'] == 1) {
        $settings['limit'] = max(1,min(500,(int)$params['pagelimit']));
        $settings['type'] = (!empty($params['filter_type'])) ? $params['filter_type'] : '';
        switch ($settings['type']) {
        case ContentListFilter::EXPR_DESIGN:
            $expr = $params['filter_design'];
            break;
        case ContentListFilter::EXPR_TEMPLATE:
            $expr = $params['filter_template'];
            break;
        case ContentListFilter::EXPR_OWNER:
            $expr = $params['filter_owner'];
            break;
        case ContentListFilter::EXPR_EDITOR:
            $expr = $params['filter_editor'];
            break;
        default:
            $expr = '';
        }
        $settings['expr'] = $expr;
    }
    else { // -1 for reset
        $settings = ['limit' => 10,'type' => '','expr' => ''];
    }
    cms_userprefs::set_for_user($userid,$modname.'_pages_filter',serialize($settings));
    $curpage = 1;
}
else {
    $tmp = cms_userprefs::get_for_user($userid,$modname.'_pages_filter');
    if( $tmp ) {
        $tmp = unserialize($tmp);
    }
    $settings = $tmp ?: ['limit' => 10,'type' => '','expr' => '']; // defaults if necesssary
}

if( isset($params['expand']) ) {
    $builder->expand_section($params['expand']);
}

if( isset($params['collapse']) ) {
    $builder->collapse_section($params['collapse']);
}

if( isset($params['setinactive']) ) {
    $res = $builder->set_active($params['setinactive'],FALSE);
    if( !$res ) $error = $this->Lang('error_setinactive');
}

if( isset($params['setactive']) ) {
    $res = $builder->set_active($params['setactive'],TRUE);
    if( !$res ) $error = $this->Lang('error_setactive');
}

if( isset($params['setdefault']) ) {
    $res = $builder->set_default($params['setdefault'],TRUE);
    if( !$res ) $error = $this->Lang('error_setdefault');
}

if( isset($params['moveup']) ) {
    $res = $builder->move_content($params['moveup'],-1);
    if( !$res ) $error = $this->Lang('error_movecontent');
}

if( isset($params['movedown']) ) {
    $res = $builder->move_content($params['movedown'],1);
    if( !$res ) $error = $this->Lang('error_movecontent');
}

if( isset($params['delete']) ) {
    $res = $builder->delete_content($params['delete']);
    if( $res ) $error = $res; // @todo Rolf: ?
}

if( isset($params['multisubmit']) && isset($params['multiaction']) &&
    isset($params['multicontent']) && is_array($params['multicontent']) && count($params['multicontent']) > 0 ) {
    list($module,$bulkaction) = explode('::',$params['multiaction'],2);
    if( $module == '' || $module == '-1' || $bulkaction == '' || $bulkaction == '-1' ) {
        $this->SetMessage($this->Lang('error_nobulkaction'));
        $this->RedirectToAdminTab();
    }
    // redirect to special action to handle bulk content stuff.
    $this->Redirect($id,'admin_multicontent',$returnid,
                    array('multicontent'=>base64_encode(serialize($params['multicontent'])),
                          'multiaction'=>$params['multiaction']));
}

if( isset($curpage) ) $_SESSION[$modname.'_curpage'] = $curpage; // for use by ajax_get_content

$pagelimit = $settings['limit'];
$allpages = (int)$db->GetOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'content');
$pagelimits = $builder->get_pagelengths($allpages); //TODO better to defer until pages-list actual length is known BUT circular
if( !isset($pagelimits[$pagelimit]) ) {
    $closest = function($arr,$target) {
        $left = 0;
        $right = count($arr) - 1;
        while( $left < $right ) {
            if( abs($arr[$left] - $target) <= abs($arr[$right] - $target) ) {
                --$right;
            }
            else {
                ++$left;
            }
        }
        return $arr[$left];
    };
    $pagelimit = $closest($pagelimits,$pagelimit); // assumes $pagelimits is ascending-sorted
    $settings['limit'] = $pagelimit;
    cms_userprefs::set_for_user($userid,$modname.'_pages_filter',serialize($settings));
}

$builder->set_pagelimit($settings['limit']); //TODO better if func($npages) BUT circular logic
if( $settings['type'] ) {
    $filter = new ContentListFilter();
    $filter->type = $settings['type'];
    $filter->expr = $settings['expr'];
    $builder->set_filter($filter);
}
else {
    $filter = null;
}

CMSMS\HookManager::add_hook('admin_add_headtext', function() {
    $root_url = CMS_ROOT_URL;
    return "<script src=\"$root_url/lib/jquery/js/jquery.cmsms_autorefresh.js\" defer></script>\n";
});

$tpl = $smarty->createTemplate("module_file_tpl:$modname;defaultadmin.tpl",null,$modname,$smarty);

$url = $this->create_url($id,'ajax_get_content',$returnid);
$tpl->assign('ajax_get_content',str_replace('amp;','',$url));
$tpl->assign('ajax',$ajax);
$tpl->assign('can_add_content',$this->CheckPermission('Add Pages') || $this->CheckPermission('Manage All Content'));
$tpl->assign('can_manage_content',$this->CheckPermission('Manage All Content'));
$tpl->assign('admin_url',$config['admin_url']);
$tpl->assign('filter',$filter);
$tpl->assign('pagelimits',$pagelimits);
$tpl->assign('pagelimit',$pagelimit); // for <select/> selection
$have_locks = ($builder->get_locks($userid)) ? 1 : 0; // ignore any held by current user (this value used in js only)
$tpl->assign('have_locks',$have_locks);
$tpl->assign('locking',CmsContentManagerUtils::locking_enabled());
// selectable admin users
$tpl->assign('user_list',UserOperations::get_instance()->GetList());
// selectable designs
$tpl->assign('design_list',CmsLayoutCollection::get_list());
// selectable templates
$tpl->assign('template_list',CmsLayoutTemplate::template_query(array('as_list'=>1)));
// selectable filter-options
$tpl->assign('options_list',[
 '' => $this->Lang('none'),
 ContentListFilter::EXPR_DESIGN => $this->Lang('prompt_design'),
 ContentListFilter::EXPR_TEMPLATE => $this->Lang('prompt_template'),
 ContentListFilter::EXPR_OWNER => $this->Lang('prompt_owner'),
 ContentListFilter::EXPR_EDITOR => $this->Lang('prompt_editor')
]);
if( $error ) $tpl->assign('error',$error);

$tpl->display();

?>
