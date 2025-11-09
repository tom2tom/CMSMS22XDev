<?php
#CMSMS News module action: admin_addfielddef
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

if (isset($params['cancel'])) $this->RedirectToAdminTab('customfields', '', 'admin_settings');

$name = (isset($params['name'])) ? trim($params['name']) : ''; // sanitize ?
$type = (isset($params['type'])) ? $params['type'] : '';
$public = (isset($params['public'])) ? (int)$params['public'] : 1;
$options = (!empty($params['options'])) ? news_ops::execSpecialize(trim($params['options'])) : '';
$max_length = (isset($params['max_length'])) ? max(1, (int)$params['max_length']) : -1;

if (isset($params['submit'])) {
    $error = false;
    if ($name == '') $error = $this->Lang('nonamegiven');

    if( !$error && $type == 'dropdown' && !$options ) $error = $this->Lang('error_nooptions');

    if( !$error ) {
        $query = 'SELECT id FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE name = ?';
        $exists = $db->GetOne($query,array($name));
        if( $exists ) $error = $this->Lang('nameexists');
    }

    if( !$error ) {
        $order = $db->GetOne('SELECT MAX(item_order) + 1 FROM ' . CMS_DB_PREFIX . 'module_news_fielddefs');
        if( $order == null ) { $order = 1; } //sql return value null

        $props = array();
        if( $options ) {
            $props['options'] = news_admin_ops::optionstext_to_array($options);
        }
        if( $max_length > 0 ) {
            $props['max_length'] = $max_length;
        }
        $extra = ($props) ? serialize($props) : null;

        $now = $db->DBTimeStamp(time());
        $query = 'INSERT INTO '.CMS_DB_PREFIX."module_news_fielddefs (name,type,item_order,create_date,modified_date,public,extra) VALUES (?,?,$order,$now,$now,$public,?)";
        $db->Execute($query, array($name, $type, $extra));

        // put mention into the admin log
        $fdid = $db->Insert_ID();
        audit($fdid, $this->GetName().' field definition', "Added: $name");

        // done.
        $params = array('tab_message'=> 'fielddefadded', '__activetab' => 'customfields');
        $this->SetMessage($this->Lang('fielddefadded'));
        $this->RedirectToAdminTab('customfields','','admin_settings');
    }

    if( $error ) echo $this->ShowErrors($error);
}

//Display template
$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;editfielddef.tpl", null, $modname, $smarty);
$tpl->assign('title', $this->Lang('addfielddef'));
$tpl->assign('startform', $this->CreateFormStart($id, 'admin_addfielddef', $returnid));
$tpl->assign('endform', $this->CreateFormEnd());
$tpl->assign('showtypechooser', true);
$tpl->assign('nametext', $this->Lang('name'));
$tpl->assign('typetext', $this->Lang('type'));
$tpl->assign('maxlengthtext', $this->Lang('maxlength'));
$tpl->assign('info_maxlength', $this->Lang('info_maxlength'));
$tpl->assign('userviewtext', $this->Lang('public'));

$tpl->assign('name', $name);
$tpl->assign('fieldtypes', $this->GetFieldTypes());
$tpl->assign('type', $type);
if( $max_length > 0 ) { $tpl->assign('max_length', $max_length); }
$tpl->assign('public', $public);
$tpl->assign('options', $options);
$tpl->display();
