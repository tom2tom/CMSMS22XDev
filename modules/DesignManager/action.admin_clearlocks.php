<?php
#-------------------------------------------------------------------------
# Module: DesignManager - A CMSMS addon module to provide template management.
# (c) 2014 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

$type = ( isset($params['type']) ) ? trim($params['type']) : '';
if( $type ) {
    $type = strtolower($type);
}
else {
    $type = 'template';
}
switch( $type ) {
case 'tpl':
case 'templates':
case 'template':
    $type = 'template';
    $this->SetCurrentTab('templates');
    break;
case 'css':
case 'stylesheets':
case 'stylesheet':
    $type = 'stylesheet';
    $this->SetCurrentTab('stylesheets');
    break;
default:
    $this->Redirect($id,'defaultadmin');
}

$uid = get_userid(FALSE);
$selfheld = !empty($params['self']);
if( $selfheld ) {
    // clear self-held locks of the specified type
    CmsLockOperations::delete_for_user($type);
    audit($uid,$this->GetName(),"Cleared her/his own $type locks");
    $this->SetMessage($this->Lang('msg_lockscleared'));
}
elseif( UserOperations::get_instance($uid,1) ) {
    // clear all locks of the specified type
    $sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLock::LOCK_TABLE.' WHERE type = ?';
    $db->Execute($sql,array($type));
    audit('',$this->GetName(),"Cleared all $type locks");
    $this->SetMessage($this->Lang('msg_lockscleared'));
}

$this->RedirectToAdminTab();
