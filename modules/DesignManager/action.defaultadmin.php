<?php
#-------------------------------------------------------------------------
# Module: DesignManager - A CMSMS addon module to provide template management.
# (c) 2015 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) return;

$modname = $this->GetName();
//default filter-properties
//NOTE 'limit' sets db-query no. of selected items, indirectly-related to displayed page-length
$filter_tpl_rec = ['tpl'=>'','limit'=>10,'offset'=>0,'sortby'=>'name','sortorder'=>'asc'];
// reconcile with page-length choices set in $getlengths()
$tnum = $db->GetOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'layout_templates');
if( $tnum > 50 ) { $filter_tpl_rec['limit'] = 100; }
elseif( $tnum > 25 ) { $filter_tpl_rec['limit'] = 50; }
elseif( $tnum > 10 ) { $filter_tpl_rec['limit'] = 25; }
// might be overridden below by cms_userprefs::get(); // OR $_SESSION only?

$filter_css_rec = ['design'=>'','limit'=>10,'offset'=>0,'sortby'=>'name','sortorder'=>'asc'];
$snum = $db->GetOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'layout_stylesheets');
if( $snum > 50 ) { $filter_css_rec['limit'] = 100; }
elseif( $snum > 25 ) { $filter_css_rec['limit'] = 50; }
elseif( $snum > 10 ) { $filter_css_rec['limit'] = 25; }
// might be overridden below by cms_userprefs::get(); // OR $_SESSION only?

if( isset($params['submit_filter_tpl']) ) {
    if( $params['submit_filter_tpl'] == 1 ) { // not resetting
        $filter_tpl_rec['tpl'] = $params['filter_tpl_options'];
        if( isset($params['filter_tpl_limit']) ) {
            $filter_tpl_rec['limit'] = max(10,min($tnum,(int)$params['filter_tpl_limit']));
        }
        $filter_tpl_rec['sortby'] = trim($params['filter_tpl_sortby']);
        $filter_tpl_rec['sortorder'] = trim($params['filter_tpl_sortorder']);
    }
    cms_userprefs::set($modname.'_tpl_filter',serialize($filter_tpl_rec)); // OR $_SESSION-property only?
    unset($_SESSION[$modname.'_tpl_page']);
    $this->SetCurrentTab('templates');
}
elseif( isset($params['submit_filter_css']) ) {
    if( $params['submit_filter_css'] == 1 ) { // not resetting
        $filter_css_rec['design'] = trim($params['filter_css_design']);
        if( isset($params['filter_css_limit']) ) {
            $filter_css_rec['limit'] = max(10,min($snum,(int)$params['filter_css_limit']));
        }
        $filter_css_rec['sortby'] = trim($params['filter_css_sortby']);
        $filter_css_rec['sortorder'] = trim($params['filter_css_sortorder']);
    }
    cms_userprefs::set($modname.'_css_filter',serialize($filter_css_rec)); // OR $_SESSION only?
    unset($_SESSION[$modname.'_css_page']);
    $this->SetCurrentTab('stylesheets');
}
elseif( isset($params['submit_create']) ) {
    $tmp = $params['import_type'];
    if( startswith($tmp,'t:') ) {
        $tmp = substr($tmp, 2); // only the type-id is useful
    }
    $this->Redirect($id,'admin_edit_template',$returnid,['import_type'=>$tmp]);
}
elseif( isset($params['submit_bulk_tpl']) ) {
    $tmp = ['allparms'=>base64_encode(json_encode($params))];
    $this->Redirect($id,'admin_bulk_template',$returnid,$tmp);
}
elseif( isset($params['submit_bulk_css']) ) {
    $tmp = ['allparms'=>base64_encode(json_encode($params))];
    $this->Redirect($id,'admin_bulk_css',$returnid,$tmp);
}
elseif( isset($params['design_setdflt']) && $this->CheckPermission('Manage Designs') ) {
    $design_id = (int)$params['design_setdflt'];
    try {
        $cur_dflt = CmsLayoutCollection::load_default();
        if( is_object($cur_dflt) && $cur_dflt->get_id() != $design_id ) {
            $cur_dflt->set_default(false);
            $cur_dflt->save();
        }
    }
    catch( \Exception $e ) {
        // do nothing
    }

    $new_dflt = CmsLayoutCollection::load($design_id);
    $new_dflt->set_default(true);
    $new_dflt->save();

    $this->SetCurrentTab('designs');
    $this->ShowMessage($this->Lang('msg_dflt_design_saved'));
}

$tmp = cms_userprefs::get($modname.'_tpl_filter'); // OR $_SESSION only?
if( $tmp ) {
    $tmp = unserialize($tmp);
    if( $tmp ) { $filter_tpl_rec = $tmp; }
}

if( isset($params['tpl_page']) ) {
    $this->SetCurrentTab('templates');
    $page = max(1,(int)$params['tpl_page']);
    $_SESSION[$modname.'_tpl_page'] = $page;
    $filter_tpl_rec['offset'] = ($page - 1) * $filter_tpl_rec['limit'];
}
elseif( isset($_SESSION[$modname.'_tpl_page']) ) {
    $page = max(1,(int)$_SESSION[$modname.'_tpl_page']);
    $filter_tpl_rec['offset'] = ($page - 1) * $filter_tpl_rec['limit'];
}

$efilter = $filter_tpl_rec; // adjust member-key
if( !empty($efilter['tpl']) ) {
    $efilter[] = $efilter['tpl'];
    unset($efilter['tpl']);
}

CMSMS\HookManager::add_hook('admin_add_headtext', function() {
    $root_url = CMS_ROOT_URL;
    return "<script src=\"$root_url/lib/jquery/js/jquery.cmsms_autorefresh.js\" defer></script>\n";
});

$tpl = $smarty->createTemplate("module_file_tpl:$modname;defaultadmin.tpl",null,$modname,$smarty);

// build a list of types and categories, and later, designs.
$opts = ['' => $this->Lang('prompt_none')];
$originators = [];
$types = CmsLayoutTemplateType::get_all();
if( $types ) {
    $filtert = [];
    $typechoices = [];
    for( $i = 0,$n = count($types); $i < $n; $i++ ) {
        $tid = $types[$i]->get_id();
        $filtert['t:'.$tid] = $types[$i]->get_langified_display_value();
        $typechoices[$tid] = $types[$i]->get_name();
        $org = $types[$i]->get_originator(); //no arg gets id?
        if( !isset($originators[$org]) ) {
            $originators['o:'.$org] = $types[$i]->get_originator(TRUE);
        }
    }
    asort($filtert);
    $opts[$this->Lang('tpl_types')] = $filtert; // data to populate the new-template type-selector
    asort($typechoices);
    $tpl->assign('list_types',$typechoices);
    asort($originators);
    $opts[$this->Lang('tpl_originators')] = $originators;
    // data to populate the template-types tab
    uasort($types,function($a,$b) {
        // core always beats alphabetic type
        // then sort by originator and then name.
        $ao = $a->get_originator();
        $bo = $b->get_originator();
        if( $ao == $a::CORE && $bo ==  $a::CORE ) return strcasecmp($a->get_name(),$b->get_name());
        if( $ao == $a::CORE ) return -1;
        if( $bo == $b::CORE ) return 1;
        return strcasecmp($a->get_langified_display_value(),$b->get_langified_display_value());
    });
    $tpl->assign('list_all_types',$types);
}
else {
    $tpl->assign('list_types',[]);
    $tpl->assign('list_all_types',[]);
}

$cats = CmsLayoutTemplateCategory::get_all();
if( $cats && is_array($cats) ) {
    $tpl->assign('list_categories',$cats);
    $filterc = [];
    for( $i = 0,$n = count($cats); $i < $n; $i++ ) {
        $filterc['c:'.$cats[$i]->get_id()] = $cats[$i]->get_name();
    }
    asort($filterc);
    $opts[$this->Lang('prompt_categories')] = $filterc;
}

$designs = CmsLayoutCollection::get_all();
if( $designs ) {
    $tpl->assign('list_designs',$designs);
    $filterd = [];
    $designchoices = [];
    for( $i = 0,$n = count($designs); $i < $n; $i++ ) {
        $did = $designs[$i]->get_id();
        $dn = $designs[$i]->get_name();
        $filterd['d:'.$did] = $dn;
        $designchoices[$did] = $dn;
    }
    asort($designchoices);
    $tpl->assign('design_names',$designchoices);
    asort($filterd);
    $opts[$this->Lang('prompt_design')] = $filterd;
}
if( $this->CheckPermission('Manage Designs') ) {
    $userops = cmsms()->GetUserOperations();
    $allusers = $userops->LoadUsers();
    $users = [-1=>$this->Lang('prompt_unknown')];
    $filteru = [];
    for( $i = 0,$n = count($allusers); $i < $n; $i++ ) {
        $filteru['u:'.$allusers[$i]->id] = $allusers[$i]->username;
        $users[$allusers[$i]->id] = $allusers[$i]->username;
    }
    asort($users);
    $tpl->assign('list_users',$users);
    asort($filteru);
    $opts[$this->Lang('prompt_user')] = $filteru;
}

if( $this->CheckPermission('Manage Stylesheets') ) {
    $tmp = cms_userprefs::get($modname.'_css_filter'); // OR $_SESSION only?
    if( $tmp ) {
        $tmp = unserialize($tmp);
        if( $tmp ) { $filter_css_rec = $tmp; }
    }
    if( isset($params['css_page']) ) {
        $page = max(1,(int)$params['css_page']);
        $_SESSION[$modname.'_css_page'] = $page;
        $filter_css_rec['offset'] = ($page - 1) * $filter_css_rec['limit'];
        $this->SetCurrentTab('stylesheets');
    }
    elseif( isset($_SESSION[$modname.'_css_page']) ) {
        $page = max(1,(int)$_SESSION[$modname.'_css_page']);
        $filter_css_rec['offset'] = ($page - 1) * $filter_css_rec['limit'];
    }
}

$getlengths = function($num) {
    $lengths = [10 => 10];
    if( $num > 10 ) { $lengths[25] = 25; }
    if( $num > 25 ) { $lengths[50] = 50; }
    if( $num > 50 ) { $lengths[100] = 100; }
    return $lengths;
};
$seetab = (!empty($params['__activetab'])) ? $params['__activetab'] : '';
$tpl->assign('tab',$seetab);
$tpl->assign('tpl_filter',$filter_tpl_rec); // filter form propereties
$tpl->assign('filter_tpl_options',$opts);
$tpl->assign('tpl_filterpages',$getlengths($tnum));
$tpl->assign('jsonfilter',json_encode($efilter)); // for templates ajaxy stuff
$tpl->assign('css_filter',$filter_css_rec); // filter form properties
$tpl->assign('css_filterpages',$getlengths($snum));
$tpl->assign('jsoncssfilter',json_encode($filter_css_rec)); // for styles ajaxy stuff
$tpl->assign('has_add_right',
             $this->CheckPermission('Modify Templates') ||
             $this->CheckPermission('Add Templates')); // for templates only
$tpl->assign('coretypename',CmsLayoutTemplateType::CORE);
$tpl->assign('manage_stylesheets',$this->CheckPermission('Manage Stylesheets'));
$tpl->assign('manage_templates',$this->CheckPermission('Modify Templates'));
$tpl->assign('manage_designs',$this->CheckPermission('Manage Designs'));
$tpl->assign('import_url',$this->create_url($id,'admin_import_template'));
$tpl->assign('admin_url',$config['admin_url']);
$tpl->assign('lock_timeout', $this->GetPreference('lock_timeout'));
$url = $this->create_url($id,'ajax_get_templates');
$tpl->assign('ajax_templates_url',str_replace('amp;','',$url));
$url = $this->create_url($id,'ajax_get_stylesheets');
$tpl->assign('ajax_stylesheets_url',str_replace('amp;','',$url));

$tpl->display();

?>
