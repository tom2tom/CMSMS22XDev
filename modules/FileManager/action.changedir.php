<?php
#FileManager module action
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
if (!$this->CheckPermission("Modify Files") && !$this->AdvancedAccessAllowed()) exit;

if( $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['showtemplate']) && $_GET['showtemplate'] == 'false' ) {
  echo filemanager_utils::get_cwd();
  exit;
}

if( !isset($params["newdir"]) && !isset($params['setdir']) ) $this->RedirectToAdminTab();

$path = '';
if( isset($params['newdir']) ) {
    // set a relative directory.
    $newdir = trim($params["newdir"]);
    $path = filemanager_utils::join_path(filemanager_utils::get_cwd(),$newdir);
}
else if( isset($params['setdir']) ) {
    // set an explicit directory
    $path = trim($params['setdir']);
    if( $path == '::top::' ) $path = filemanager_utils::get_default_cwd();
}

try {
    filemanager_utils::set_cwd($path);
    if( !isset($params['ajax']) ) {
        filemanager_utils::set_cwd($path);
        $this->RedirectToAdminTab();
    }
}
catch( Exception $e ) {
    audit('','FileManager','Attempt to set working directory to an invalid location: '.$path);
    if( isset($params['ajax']) ) exit('ERROR');
    $this->SetError($this->Lang('invalidchdir',$path));
    $this->RedirectToAdminTab();
}

if( isset($params['ajax']) ) echo 'OK'; exit;
$this->RedirectToAdminTab();
