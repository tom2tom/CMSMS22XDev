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
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
#END_LICENSE

if( !isset($gCms) ) exit;
// no permissions checks here.

$num = count(ob_list_handlers());
for ($cnt = 0; $cnt < $num; $cnt++) { ob_end_clean(); }

$modname = $this->GetName();

$tmp = cms_userprefs::get($modname.'_pages_filter');
if( $tmp ) {
    $tmp = unserialize($tmp);
}
$settings = $tmp ?: ['limit' => 10,'type' => '','expr' => '']; // defaults if necessary

$tpl = $smarty->createTemplate("module_file_tpl:$modname;ajax_get_content.tpl",null,$modname,$smarty);

try {
    // load all the content that this user can display...
    // organize it into a tree
    $builder = new CMSContentManager\ContentListBuilder($this);
    $curpage = (isset($_SESSION[$modname.'_curpage']) && !isset($params['seek'])) ? (int) $_SESSION[$modname.'_curpage'] : 1;
    if( isset($params['curpage']) ) $curpage = (int)$params['curpage'];

    //
    // build the display
    //
    $builder->set_pagelimit($settings['limit']); //TODO better if func($npages) BUT circular logic
    if( !empty($settings['type']) ) {
        $filter = new CMSContentManager\ContentListFilter();
        $filter->type = $settings['type'];
        $filter->expr = $settings['expr'];
        $builder->set_filter($filter);
    }
    if( isset($params['seek']) && $params['seek'] ) { // i.e. not 0
        $builder->seek_to((int)$params['seek']);
    }
    else {
        $builder->set_page($curpage);
    }

    $editinfo = $builder->get_content_list();
    $npages = $builder->get_numpages();

    $dolock = CmsContentManagerUtils::locking_enabled();
    if( $dolock ) {
        $userid = get_userid(false);
        $other_locks = CmsLockOperations::get_locks('content',0,$userid); //lock(s) held by other users
        $have_locks = $other_locks && is_array($other_locks);
        $locks = CmsLockOperations::get_locks('content',$userid); //lock(s) held by current user
        if( $locks && is_array($locks) ) {
            // grab page-hierarchy numbers for Smarty tip
            $pids = [];
            foreach( $locks as $obj ) {
                $pids[] = (int)$obj['oid'];
            }
            $sql = 'SELECT hierarchy FROM '.CMS_DB_PREFIX.'content WHERE content_id IN ('.implode(',',$pids).') ORDER BY hierarchy';
            $dbr = $db->GetCol($sql);
            if( $dbr ) {
                foreach( $dbr as &$one ) {
                    $one = preg_replace('/(^|\.)0*/','$1',$one); //strip padding
                }
                unset($one);
                $itemstip = $this->Lang('page').'='. implode(',',$dbr);
            }
            else {
                $itemstip = '';
            }
            $self_locks = true;
        }
        else {
            $self_locks = false;
        }
    }
    else {
        $have_locks = false;
        $self_locks = false;
    }

    if ($have_locks) {
        $tpl->assign('iconsteal_url',$this->GetModuleURLPath().'/images/steal.png');
    }

    $have_filter = !empty($settings['type']);
    $url = $this->create_url($id,'ajax_get_content',$returnid);

    $tpl->assign('ajax_get_content_url',str_replace('&amp;','&',$url));
    $tpl->assign('can_add_content',$this->CheckPermission('Add Pages') || $this->CheckPermission('Manage All Content'));
    $tpl->assign('can_reorder_content',$this->CheckPermission('Manage All Content'));
    $tpl->assign('columns',$builder->get_display_columns());
    $tpl->assign('have_filter',$have_filter);
    $tpl->assign('have_locks',$have_locks);
    $tpl->assign('have_selflocks',$self_locks);
    $tpl->assign('indent',!$have_filter && cms_userprefs::get('indent',true)); // TODO why does filtering restrict indenting?
    $tpl->assign('locking',$dolock);
    $tpl->assign('multiselect',$builder->supports_multiselect());
    $tpl->assign('npages',$npages);
    $tpl->assign('pagelimit',$settings['limit']);
    $tpl->assign('pagenumber',$builder->get_page());
    $tpl->assign('prettyurls_ok',$builder->pretty_urls_configured());
    $tpl->assign('template_list',CmsLayoutTemplate::template_query(array('as_list'=>1))); // this is just to aid loading

    if( $self_locks && $itemstip ) { $tpl->assign('which_selflocks',$itemstip); }
    if( CmsContentManagerUtils::get_pagenav_display() == 'title' ) {
        $tpl->assign('colhdr_page',$this->Lang('colhdr_name'));
        $tpl->assign('coltitle_page',$this->Lang('coltitle_name'));
    }
    else {
        $tpl->assign('colhdr_page',$this->Lang('colhdr_menutext'));
        $tpl->assign('coltitle_page',$this->Lang('coltitle_menutext'));
    }
    if( $editinfo ) { $tpl->assign('content_list',$editinfo); }
    elseif( $have_filter ) { $tpl->assign('infomsg',$this->Lang('err_nomatchingcontent')); }
    //TODO avoid repeating these registrations during every refresh and never do so unless $builder->supports_multiselect()
    if( $this->CheckPermission('Remove Pages') && $this->CheckPermission('Modify Any Page') ) {
        bulkcontentoperations::register_function($this->Lang('bulk_delete'),'delete');
    }
    if( $this->CheckPermission('Manage All Content') ) {
        bulkcontentoperations::register_function($this->Lang('bulk_active'),'active');
        bulkcontentoperations::register_function($this->Lang('bulk_inactive'),'inactive');
        bulkcontentoperations::register_function($this->Lang('bulk_cachable'),'setcachable');
        bulkcontentoperations::register_function($this->Lang('bulk_noncachable'),'setnoncachable');
        bulkcontentoperations::register_function($this->Lang('bulk_showinmenu'),'showinmenu');
        bulkcontentoperations::register_function($this->Lang('bulk_hidefrommenu'),'hidefrommenu');
        bulkcontentoperations::register_function($this->Lang('bulk_secure'),'secure');
        bulkcontentoperations::register_function($this->Lang('bulk_insecure'),'insecure');
        bulkcontentoperations::register_function($this->Lang('bulk_setdesign'),'setdesign');
        bulkcontentoperations::register_function($this->Lang('bulk_changeowner'),'changeowner');
    }
    $opts = bulkcontentoperations::get_operation_list();
    if( $opts ) $tpl->assign('bulk_options',$opts);

    $tpl->display();
}
catch( \Exception $e ) {
    echo '<div class="red">'.$e->GetMessage().'</div>';
    debug_to_log($e);
}
exit;

?>
