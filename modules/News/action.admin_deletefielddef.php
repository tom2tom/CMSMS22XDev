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

$fdid = '';
if (isset($params['fdid'])) $fdid = $params['fdid'];

//Get the field details
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE id = ?';
$row = $db->GetRow($query, array($fdid));
if (!$row) {
    $this->SetError(lang('missingparams'));
    $this->RedirectToAdminTab('customfields','','admin_settings');
}

//Now remove the field
$query = "DELETE FROM ".CMS_DB_PREFIX."module_news_fielddefs WHERE id = ?";
$db->Execute($query, array($fdid));

//And remove it from any entries
$query = "DELETE FROM ".CMS_DB_PREFIX."module_news_fieldvals WHERE fielddef_id = ?";
$db->Execute($query, array($fdid));

$db->Execute('UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order - 1) WHERE item_order > ?', array($row['item_order']));

$params = array('tab_message'=> 'fielddefdeleted', 'active_tab' => 'customfields');
// put mention into the admin log
audit($fdid,$this->GetName().' field definition',"Deleted: {$row['name']}");
$this->Setmessage($this->Lang('fielddefdeleted'));
$this->RedirectToAdminTab('customfields','','admin_settings');
