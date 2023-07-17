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
if (!$this->CheckPermission('Modify Files')) exit;

if (!empty($params["fmmessage"])) {
    // gotta get rid of this stuff.
    $count="";
    if (!empty($params["fmmessagecount"])) $count = $params["fmmessagecount"];
    echo $this->ShowMessage($this->Lang($params["fmmessage"],$count));
}

if (!empty($params["fmerror"])) {
    // gotta get rid of this stuff
    $count="";
    if (!empty($params["fmerrorcount"])) $count = $params["fmerrorcount"];
    echo $this->ShowErrors($this->Lang($params["fmerror"],$count));
}

if (isset($params["newsort"])) $this->SetPreference("sortby",$params["newsort"]);

$path = trim(ltrim(filemanager_utils::get_cwd(),'/'));
if( \filemanager_utils::can_do_advanced() && $this->GetPreference('advancedmode',0) ) {
    $path = '::top::/'.$path;
}
$tmp_path_parts = explode('/',$path);
$path_parts = [];
for( $i = 0; $i < count($tmp_path_parts); $i++ ) {
    $obj = new stdClass();
    if( !$tmp_path_parts[$i] ) continue;
    $obj->name = $tmp_path_parts[$i];
    if( $obj->name == '::top::' ) {
        $obj->name = 'root';
    }
    if( $i < count($tmp_path_parts) - 1 ) {
        // not the last entry
        $fullpath = implode('/',array_slice($tmp_path_parts,0,$i+1));
        if( startswith($fullpath,'::top::') ) $fullpath = substr($fullpath,7);
        $obj->url = $this->create_url( $id, 'changedir', '',[ 'setdir' => $fullpath ] );
    } else {
        // the last entry... no link
    }
    $path_parts[] = $obj;
}
$smarty->assign('path',$path);
$smarty->assign('path_parts',$path_parts);
echo $this->ProcessTemplate('fmpath.tpl');

include(__DIR__."/uploadview.php");
include(__DIR__."/action.admin_fileview.php"); // this is also a standalone action
