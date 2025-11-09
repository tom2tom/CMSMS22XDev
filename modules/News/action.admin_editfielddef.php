<?php
#CMSMS News module action: admin_editfielddef
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

if (isset($params['cancel'])) $this->RedirectToAdminTab('customfields', '', 'admin_settings');

$fdid = (isset($params['fdid'])) ? $params['fdid'] : '';
$origname = (isset($params['origname'])) ? $params['origname'] : ''; //htmlspecialchar'd
$name = (isset($params['name'])) ? trim($params['name']) : ''; // sanitize ?
$type = (isset($params['type'])) ? $params['type'] : '';
$public = (isset($params['public'])) ? (int)$params['public'] : 0;
$options = (!empty($params['options'])) ? news_ops::execSpecialize(trim($params['options'])) : '';
$max_length = (isset($params['max_length'])) ? max(1, (int)$params['max_length']) : -1;

if (isset($params['submit'])) {
  $error = '';
  if ($name == '') $error = $this->Lang('nonamegiven');

  if (!$error) {
    $query = 'SELECT id FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE name = ? AND id != ?';
    $tmp = $db->GetOne($query, array($name, $fdid));
    if ($tmp) $error = $this->Lang('nameexists');
  }

  if (!$error) {
    $props = array();
    if ($options) {
      $props['options'] = news_admin_ops::optionstext_to_array($options);
    }
    if ($max_length > -1) {
      $props['max_length'] = $max_length;
    }
    $extra = ($props) ? serialize($props) : null;

    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET name = ?,type = ?,modified_date = '.$db->DBTimeStamp(time()).',public = ?,extra = ? WHERE id = ?';
    $res = $db->Execute($query, array($name, $type, $public, $extra, $fdid));

    if (!$res) exit( $db->ErrorMsg() );
    // put mention into the admin log
    audit($fdid, $this->GetName().' field definition', "Edited: $name");
    $this->SetMessage($this->Lang('fielddefupdated'));
    $this->RedirectToAdminTab('customfields', '', 'admin_settings');
  }
}
else {
  $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE id = ?';
  $row = $db->GetRow($query, array($fdid));
  if ($row) {
    $name = $row['name'];
    $type = $row['type'];
    $origname = $name;
    $public = $row['public'];
    if ($row['extra']) {
      $props = unserialize($row['extra'], array('allowed_classes' => false));
      if ($props !== false) {
        if (isset($props['options'])) {
          $options = news_admin_ops::array_to_optionstext($props['options']);
        }
        if (isset($props['max_length'])) {
          $max_length = (int)$props['max_length'];
        }
      }
    }
  }
}

//Display template
$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;editfielddef.tpl", null, $modname, $smarty);
$tpl->assign('title', $this->Lang('editfielddef'));
$tpl->assign('startform', $this->CreateFormStart($id, 'admin_editfielddef',
  $returnid, 'post', 'multipart/form-data', false, '', [
  'fdid' => $fdid,
  'type' => $type,
  'origname' => htmlspecialchars($origname)]));
$tpl->assign('endform', $this->CreateFormEnd());
$tpl->assign('showtypechooser', false);
$tpl->assign('nametext', $this->Lang('name'));
$tpl->assign('typetext', $this->Lang('type'));
$tpl->assign('maxlengthtext', $this->Lang('maxlength'));
$tpl->assign('info_maxlength', $this->Lang('info_maxlength'));
$tpl->assign('userviewtext', $this->Lang('public'));

$tpl->assign('name', $name);
$tpl->assign('fieldtypes', $this->GetFieldTypes());
$tpl->assign('type', $type);
if (isset($max_length)) $tpl->assign('max_length', $max_length);
$tpl->assign('public', $public);
$tpl->assign('options', $options);

$tpl->display();
