<?php
#Module News action
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$
if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

if (isset($params['cancel'])) $this->RedirectToAdminTab('customfields','','admin_settings');

$fdid = '';
if (isset($params['fdid'])) $fdid = $params['fdid'];

$name = '';
if (isset($params['name'])) $name = trim($params['name']);

$arr_options = array();
$options = '';
if( isset($params['options']) ) {
  $options = trim($params['options']);
  $arr_options = news_admin_ops::optionstext_to_array($options);
}

$type = '';
if (isset($params['type'])) $type = $params['type'];

$max_length = 255;
if (isset($params['max_length'])) $max_length = max(0,(int)$params['max_length']);

$origname = '';
if (isset($params['origname'])) $origname = $params['origname'];

$public = 0;
if( isset($params['public']) ) $public = (int)$params['public'];

if (isset($params['submit'])) {
  // @todo: sanitizing input
  $error = '';
  if ($name == '') $error = $this->Lang('nonamegiven');

  if( !$error ) {
    $query = 'SELECT id FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE name = ? AND id != ?';
    $tmp = $db->GetOne($query,array($name,$fdid));
    if( $tmp ) $error = $this->Lang('nameexists');
  }

  if( !$error ) {
    $extra = array('options'=>$arr_options);
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET name = ?, type = ?, max_length = ?, modified_date = '.$db->DBTimeStamp(time()).', public = ?, extra = ? WHERE id = ?';
    $res = $db->Execute($query, array($name, $type, $max_length, $public, serialize($extra), $fdid));

    if( !$res ) die( $db->ErrorMsg() );
    // put mention into the admin log
    audit($fdid, $this->GetName().' field definition',"Edited: $name");
    $this->SetMessage($this->Lang('fielddefupdated'));
    $this->RedirectToAdminTab('customfields','','admin_settings');
  }
}
else {
   $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE id = ?';
   $row = $db->GetRow($query, array($fdid));

   if ($row) {
     $name = $row['name'];
     $type = $row['type'];
     $max_length = $row['max_length'];
     $origname = $row['name'];
     $public = $row['public'];
     $extra = unserialize($row['extra']);
     if( isset($extra['options']) ) {
       $options = news_admin_ops::array_to_optionstext($extra['options']);
     }
   }
}

//Display template
$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;editfielddef.tpl", null, $modname, $smarty);
$tpl->assign('title', $this->Lang('editfielddef'));
$tpl->assign('startform', $this->CreateFormStart($id, 'admin_editfielddef', $returnid));
$tpl->assign('endform', $this->CreateFormEnd());
$tpl->assign('nametext', $this->Lang('name'));
$tpl->assign('typetext', $this->Lang('type'));
$tpl->assign('maxlengthtext', $this->Lang('maxlength'));
$tpl->assign('showinputtype', false);
$tpl->assign('inputtype', $this->CreateInputHidden($id, 'type', $type));
$tpl->assign('info_maxlength', $this->Lang('info_maxlength'));
$tpl->assign('userviewtext', $this->Lang('public'));

$tpl->assign('name', $name);
$tpl->assign('fieldtypes', $this->GetFieldTypes());
$tpl->assign('type', $type);
$tpl->assign('max_length', $max_length);
$tpl->assign('public', $public);
$tpl->assign('options', $options);

//$tpl->assign('mod',$this);
$tpl->assign('hidden',
	$this->CreateInputHidden($id, 'fdid', $fdid).
	$this->CreateInputHidden($id, 'origname', htmlspecialchars($origname))); //TODO startform $params

$tpl->display();

// EOF
