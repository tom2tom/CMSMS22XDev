<?php
#FileManager module unistallation script
#(c) 2006-8 Morten Poulsen <morten@poulsen.org>
#(c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

if (!function_exists("cmsms")) exit;
//$db = $this->GetDb();

// remove the database table
/*$dict = NewDataDictionary( $db );
 $sqlarray = $dict->DropTableSQL( CMS_DB_PREFIX."module_filemanager_thumbs" );
 $dict->ExecuteSQLArray($sqlarray);
 */
// remove the sequence
//$db->DropSequence( CMS_DB_PREFIX."module_skeleton_seq" );

$this->RemovePermission('Use Filemanager Advanced');
$this->RemoveEvent('OnFileUploaded');
$this->RemoveEvent('OnFileDeleted');
$this->RemovePreference();
$this->RemoveSmartyPlugin();

?>
