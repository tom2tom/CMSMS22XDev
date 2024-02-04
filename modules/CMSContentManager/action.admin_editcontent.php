<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module CMSContentManager action
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE
if( !isset($gCms) ) exit;

$user_id = get_userid();

$this->SetCurrentTab('pages');

if( isset($params['cancel']) ) {
    $this->SetMessage($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

$content_id = 0; //i.e. new-page
$content_obj = null;
$error = '';
$active_tab = '';

//
// init
//
try {
    $pagedefaults = CmsContentManagerUtils::get_pagedefaults();
    $content_type = $pagedefaults['contenttype'];

    if( isset($params['content_id']) ) $content_id = (int)$params['content_id'];

    if( $content_id < 1 ) {
        // adding or copying.
        if( !$this->CheckPermission('Add Pages') ) {
            // no permission to add page.
            $this->SetError($this->Lang('error_editpage_permission')); // TODO something specific to add page
            $this->RedirectToAdminTab();
        }
    }
    else if( !$this->CanEditContent($content_id) ) {
        // nope, can't edit this page anyways.
        $this->SetError($this->Lang('error_editpage_permission'));
        $this->RedirectToAdminTab();
    }

    // Get a list of content types and pick a default if necessary
    $contentops = \ContentOperations::get_instance();
    $existingtypes = $contentops->ListContentTypes(false,true);

    //
    // load or create the initial content object
    //
    if( $content_id === -1 ) {
        if( isset($_SESSION['__cms_copy_obj__']) ) {
            // we're copying a content object.
            $from_id = $_SESSION['__cms_copy_obj__'];
            unset($_SESSION['__cms_copy_obj__']);
            $content_obj = $contentops->LoadContentFromId($from_id,true);
            if( !$content_obj ) throw new RuntimeException('Invalid session data');
            $type_name = $content_obj->Type();
            // can the following ever fail?
            if( !$type_name ) throw new RuntimeException('Could not find content object\'s type');
            $ph = $contentops->LoadContentType($type_name);
            if( !$ph ) throw new RuntimeException('Could not find content type named '.$type_name);
            if( !class_exists($ph->class) ) throw new RuntimeException('Could not find class for content type');

            $content_obj->SetId(-1);
            $content_obj->SetName('Copy of '.$content_obj->Name());
            $content_obj->SetMenuText('Copy of '.$content_obj->MenuText());
            $content_obj->SetAlias($content_obj->Alias().'_copy');
            $content_obj->SetOldItemOrder(-1);
            $content_obj->SetDefaultContent(false);
            $content_obj->SetURL('');
            $content_obj->SetOwner($user_id);
            $content_obj->SetLastModifiedBy($user_id);
            $content_type = $type_name;
        }
        else {
            throw new \LogicException('Missing session data');
        }
    }
    else if( $content_id === 0 ) {
        // creating a new content object
        if( isset($params['content_type']) ) $content_type = trim($params['content_type']);
        $content_obj = $contentops->CreateNewContent($content_type);
        $content_obj->SetOwner($user_id);
        $content_obj->SetLastModifiedBy($user_id);
        $content_obj->SetActive($pagedefaults['active']);
        $content_obj->SetSecure($pagedefaults['secure']);
        $content_obj->SetCachable($pagedefaults['cachable']);
        $content_obj->SetShowInMenu($pagedefaults['showinmenu']);
        $content_obj->SetPropertyValue('design_id',$pagedefaults['design_id']);
        $content_obj->SetTemplateId($pagedefaults['template_id']);
        $content_obj->SetPropertyValue('searchable',$pagedefaults['searchable']);
        $content_obj->SetPropertyValue('content_en',$pagedefaults['content']);
        $content_obj->SetMetaData($pagedefaults['metadata']);
        $content_obj->SetPropertyValue('extra1',$pagedefaults['extra1']);
        $content_obj->SetPropertyValue('extra2',$pagedefaults['extra2']);
        $content_obj->SetPropertyValue('extra3',$pagedefaults['extra3']);
        $content_obj->SetAdditionalEditors($pagedefaults['addteditors']);
        $dflt_parent = (int) \cms_userprefs::get('default_parent');
        if( $dflt_parent < 1 ) $dflt_parent = -1;
        if( !$this->CheckPermission('Modify Any Page') || !$this->CheckPermission('Manage All Content') ) {
            // we get the list of pages that this user has access to.
            // if she is not an editor of the default page, then use the first page she has access to, or -1
            $list = $contentops->GetPageAccessForUser($user_id);
            if( count($list) && !in_array($dflt_parent,$list) ) $dflt_parent = $list[0];
        }
        // check if this parent is valid. If not, use -1
        if( $dflt_parent > 0 ) {
            $node = $contentops->quickfind_node_by_id( $dflt_parent );
            if( !$node ) $dflt_parent = -1;
        }
        $content_obj->SetParentId($dflt_parent);
    }
    else {
        // editing an existing content object
        $content_obj = $contentops->LoadContentFromId($content_id);
        $content_type = $content_obj->Type();
        if( isset($params['content_type']) ) {
            $content_type = trim($params['content_type']);
        }
    }

    // validate the content type.
    if( $existingtypes && is_array($existingtypes) && !in_array($content_type,array_keys($existingtypes)) ) {
        $this->SetError($this->Lang('error_editpage_contenttype'));
        $this->RedirectToAdminTab();
    }
}
catch( Exception $e ) {
    // An error here means we can't display anything
    $this->SetError($e->getMessage());
    $this->RedirectToAdminTab();
}

//
// handle changing content types
// or a POST
//
try {
    if( $content_id > 0 && $content_type != $content_obj->Type() ) {
        // content type changed. create a new content object, but preserve the id.
        $tmpobj = $contentops->CreateNewContent($content_type);
        $tmpobj->SetId($content_obj->Id());
        $tmpobj->SetName($content_obj->Name());
        $tmpobj->SetMenuText($content_obj->MenuText());
        $tmpobj->SetTemplateId($content_obj->TemplateId());
        if( $tmpobj->TemplateId() < 1 ) $tmpobj->SetTemplateId($pagedefaults['template_id']);
        if( $tmpobj->GetPropertyValue('design_id') < 1 ) $tmpobj->SetPropertyValue('design_id',$pagedefaults['design_id']);

        $tmpobj->SetParentId($content_obj->ParentId());
        $tmpobj->SetAlias($content_obj->Alias());
        $tmpobj->SetOwner($content_obj->Owner());
        $tmpobj->SetActive($content_obj->Active());
        $tmpobj->SetItemOrder($content_obj->ItemOrder()); // hence next position
        $tmpobj->SetShowInMenu($content_obj->ShowInMenu());
        $tmpobj->SetCachable($content_obj->Cachable());
        $tmpobj->SetHierarchy($content_obj->Hierarchy()); // friendly-format converted before store
        $tmpobj->SetLastModifiedBy($content_obj->LastModifiedBy());
        $tmpobj->SetAdditionalEditors($content_obj->GetAdditionalEditors());
        $tmpobj->Properties();
        $content_obj = $tmpobj;
    }

    if( strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' ) {
        // if we're in a POST action, another item may have changed that requires reloading the page
        // filling the params will make sure that no edited content was lost.
        $content_obj->FillParams($_POST,($content_id > 0));
    }

    $active_tab = isset($params['active_tab']) ? trim($params['active_tab']) : '';
    if( isset($params['submit']) || isset($params['apply']) || isset($params['preview']) ) {
        $error = $content_obj->ValidateData();
        if( $error ) {
            if( isset($params['ajax']) ) {
                $tmp = array('response'=>'Error','details'=>$error);
                echo json_encode($tmp);
                exit;
            }
            // error, but no ajax... fall through
        }
        else if( isset($params['submit']) || isset($params['apply']) ) {
            $content_obj->SetLastModifiedBy($user_id);
            $content_obj->Save();
            $optype = ($content_id > 0) ? 'Edited' : 'Added';
            $tmp = $content_obj->Name();
            if( $tmp ) {
                $tmp = "$optype content page: $tmp";
            }
            else {
                $tmp = "$optype anonymous page";
            }
            audit($content_obj->Id(),$this->GetName(),$tmp);
            if( isset($params['submit']) ) {
                $this->SetMessage($this->Lang('msg_editpage_success'));
                $this->RedirectToAdminTab();
            }

            if( isset($params['ajax']) ) {
                $tmp = array('response'=>'Success','details'=>$this->Lang('msg_editpage_success'),'url'=>$content_obj->GetURL());
                echo json_encode($tmp);
                exit;
            }
        }
        else if( isset($params['preview']) && $content_obj->HasPreview() ) {
            $_SESSION['__cms_preview__'] = serialize($content_obj);
            $_SESSION['__cms_preview_type__'] = $content_type;
            if( CMS_DEBUG ) debug_to_log($_SESSION,'before preview');
            exit;
        }
    }
}
catch( CmsEditContentException $e ) {
/*
    if( isset($params['submit']) ) {
        $this->SetError($e->getMessage());
        $this->RedirectToAdminTab();
    };
*/
    $error = $e->GetMessage();
    if( isset($params['ajax']) ) {
        $tmp = array('response'=>'Error','details'=>$error);
        echo json_encode($tmp);
        exit;
    }
}
catch( CmsContentException $e ) {
    $error = $e->getMessage();
    if( isset($params['ajax']) ) {
        $tmp = array('response'=>'Error','details'=>$error);
        echo json_encode($tmp);
        exit;
    }
}

//
// BUILD THE DISPLAY
//
if( $content_id > 0 && CmsContentManagerUtils::locking_enabled() ) {
    try {
        $lock_id = 0;
        for( $i = 0; $i < 3; $i++ ) {
            // check if this thing is already locked.
            $lock_id = CmsLockOperations::is_locked('content',$content_id);
            if( $lock_id == 0 ) break;
            usleep(500);
        }
        if( $lock_id > 0 ) {
            // it's locked... by somebody, make sure it's expired before we allow stealing it.
            $lock = CmsLock::load('content',$content_id);
            if( !$lock->expired() ) throw new CmsLockException('CMSEX_L010');
            // lock is expired, we can just remove it.
            CmsLockOperations::unlock($lock_id,'content',$content_id);
        }
    }
    catch( CmsException $e ) {
        $this->SetError($e->getMessage());
        $this->RedirectToAdminTab();
    }
}

$tab_contents_array = [];
$tab_message_array = [];
try {
    $tab_names = $content_obj->GetTabNames();

    // the content object might have no main tab, but we require one
    $tmain = $content_obj::TAB_MAIN;
    if( !isset($tab_names[$tmain]) ) {
        $tab_names = array($tmain => lang($tmain)) + $tab_names;
    }

    foreach( $tab_names as $currenttab => $label ) {
        $tmp = $content_obj->GetTabMessage($currenttab);
        if( $tmp ) $tab_message_array[$currenttab] = $tmp;

        $contentarray = $content_obj->GetTabElements($currenttab, $content_obj->Id() < 1 );
        if( $currenttab == $tmain ) {
            // first tab... prepend a content-type selector.
            // unless the user is merely an additional-editor. TODO always display, but disabled for additional editor
            if( ($this->CheckPermission('Manage All Content') || $content_obj->Owner() == $user_id) ) {
                // accumulate defaultable content-types
                $dflttypes = [];
                $typeclasses = $contentops->ListContentTypes(true,true); //c.f. $existingtypes which has type-keys
                foreach( $typeclasses as $classname => $label ) {
                    $obj = new $classname();
                    if( $obj && $obj->IsDefaultPossible() ) {
                        $type = strtolower($classname); //OR array_search($label,$existingtypes)
                        $dflttypes[] = $type;
                    }
                }
                $dflt = $content_obj->DefaultContent();
                $selcount = 0;
                asort($existingtypes);
                $tmp2 = "<select id=\"content_type\" name=\"{$id}content_type\">";
                foreach( $existingtypes as $type => $label ) {
                    if( !$dflt || in_array($type, $dflttypes) ) {
                        $tmp2 .= CmsFormUtils::create_option(array('value'=>$type,'label'=>$label),$content_type);
                        $selcount++;
                    }
                }
                if( $selcount > 0 ) {
                    $tmp2 .= '</select>';
                    $help = cms_admin_utils::get_help_tag(array('key'=>'help_content_type','title'=>$this->Lang('help_title_content_type')));
                    $tmp = array('<label for="content_type">*'.$this->Lang('prompt_editpage_contenttype').':</label>&nbsp;'.$help,$tmp2);
                    if( $contentarray ) {
                        array_unshift($contentarray, $tmp);
                    }
                    else {
                        $contentarray = [$tmp];
                    }
                }
            }
        }
        $tab_contents_array[$currenttab] = $contentarray;
    }
}
catch( Exception $e ) {
    if( !isset($tab_names) ) $tab_names = [];
    $error = $e->GetMessage();
}

if( $error ) echo $this->ShowErrors($error);

// give stuff to smarty.
if( $content_obj->HasPreview() ) {
//  $config = cmsms()->GetConfig(); already set
    $smarty->assign('has_preview',1);
    $smarty->assign('preview_url',"{$config['root_url']}/index.php?{$config['query_var']}=".__CMS_PREVIEW_PAGE__);
}

if( $this->GetPreference('template_list_mode','designpage') != 'all') {
    $tmp = $this->create_url($id,'admin_ajax_gettemplates',$returnid);
    $url = str_replace('&amp;','&',$tmp).'&showtemplate=false';
    $smarty->assign('designchanged_ajax_url',$url);
}

$parms = array();
if( $content_id > 0 ) $parms['content_id'] = $content_id;
$url = str_replace('&amp;','&',$this->create_url($id,'admin_editcontent',$returnid,$parms)).'&showtemplate=false';
$smarty->assign('apply_ajax_url',$url);
$url = str_replace('&amp;','&',$this->create_url($id,'admin_editcontent',$returnid,array('preview'=>1)));
$smarty->assign('preview_ajax_url',$url);
$smarty->assign('lock_timeout',$this->GetPreference('locktimeout'));
$smarty->assign('lock_refresh',$this->GetPreference('lockrefresh'));
$smarty->assign('options_tab_name',$content_obj::TAB_OPTIONS);
$smarty->assign('active_tab',$active_tab);
$smarty->assign('content_id',$content_id);
$smarty->assign('content_obj',$content_obj);
$smarty->assign('tab_names',$tab_names);
$smarty->assign('tab_contents_array',$tab_contents_array);
$smarty->assign('tab_message_array',$tab_message_array);
$smarty->assign('userid',$user_id);
/*$factory = new ContentAssistantFactory($content_obj);
$assistant = $factory->getEditContentAssistant();
if( is_object($assistant) ) $smarty->assign('extra_content',$assistant->getExtraCode());*/

echo $this->ProcessTemplate('admin_editcontent.tpl');

#
# EOF
#
?>
