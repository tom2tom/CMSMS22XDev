<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module ModuleManager function: search
# (c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#-------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
#END_LICENSE

use ModuleManager\utils;

if( !isset($gCms) ) exit;

global $CMS_VERSION;

$caninstall = can_admin_upload();
$search_data = [];
$term = '';
$advanced = 0;
$nonemsg = '';
// see if there are saved results
if( isset($_SESSION['modmgr_search']) ) $search_data = unserialize($_SESSION['modmgr_search']);
if( isset($_SESSION['modmgr_searchterm']) ) $term = (string)$_SESSION['modmgr_searchterm'];
if( isset($_SESSION['modmgr_searchadv']) ) $advanced = (int)$_SESSION['modmgr_searchadv'];

$clear_search = function() use (&$search_data) {
    unset($_SESSION['modmgr_search']);
    $search_data = [];
};

// get the modules that are already installed
$instmodules = '';
{
    $result = utils::get_installed_modules();
    if( ! $result[0] ) {
        $this->_DisplayErrorPage( $id, $params, $returnid, $result[1] );
        return;
    }
    $instmodules = $result[1];
}

if( isset($params['submit']) ) {
    try {
        $url = $this->GetPreference('module_repository'); // UNUSED
        $error = 0;
        $term = cleanvalue(trim($params['term']));
        if( strlen($term) < 3 ) throw new \Exception($this->Lang('error_searchterm'));
        $advanced = (int)$params['advanced'];

        $res = ModuleManager\modulerep_client::search($term,$advanced);
        if( !is_array($res) || !$res[0] ) throw new \Exception($this->Lang('error_search').' '.$res[1]);
        if( !is_array($res[1]) ) throw new \Exception($this->Lang('search_noresults'));

//      $data = array(); UNUSED
        if( $res[1] ) {
            $res = utils::build_module_data($res[1], $instmodules);
            $moduledir = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'modules';
            $writable = is_writable($moduledir);
        }
        else {
            $res = [];
            $nonemsg = $this->Lang('search_noresults');
        }

        for( $i = 0; $i < count($res); $i++ ) {
            $row =& $res[$i];
            $obj = new stdClass();
            foreach( $row as $k => $v ) {
                $obj->$k = $v;
            }
            $obj->name = $this->CreateLink( $id, 'modulelist', $returnid, $row['name'],array('name'=>$row['name']));
            $obj->version = $row['version'];
            $obj->help_url = $this->create_url( $id, 'modulehelp', $returnid,
                                                array('name'=>$row['name'],'version'=>$row['version'],'filename'=>$row['filename']) );
            $obj->helplink = $this->CreateLink( $id, 'modulehelp', $returnid, $this->Lang('helptxt'),
                                                array('name'=>$row['name'],'version'=>$row['version'],'filename'=>$row['filename']) );
            $obj->depends_url = $this->create_url( $id, 'moduledepends', $returnid,
                                                   array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']));
            $obj->dependslink = $this->CreateLink( $id, 'moduledepends', $returnid,
                                                   $this->Lang('dependstxt'),
                                                   array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']));
            $obj->about_url = $this->create_url( $id, 'moduleabout', $returnid,
                                                 array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']));

            $obj->aboutlink = $this->CreateLink( $id, 'moduleabout', $returnid,
                                                 $this->Lang('abouttxt'),
                                                 array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']));
            $obj->age = utils::get_status($row['date']);
            $obj->date = $row['date'];
            $obj->downloads = isset($row['downloads'])?$row['downloads']:$this->Lang('unknown');
            $obj->candownload = FALSE;

            switch( $row['status'] ) {
            case 'incompatible':
                $obj->status = $this->Lang('incompatible');
                break;
            case 'uptodate':
                $obj->status = $this->Lang('uptodate');
                break;
            case 'newerversion':
                $obj->status = $this->Lang('newerversion');
                break;
            case 'notinstalled':
                $modpath = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
                if( (($writable && is_dir($modpath) && is_directory_writable($modpath)) ||
                     ($writable && !file_exists($modpath) )) && $caninstall ) {
                    $obj->candownload = TRUE;
                    $obj->status = $this->CreateLink( $id, 'installmodule', $returnid,
                                                      $this->Lang('download'),
                                                      array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'],
                                                            'size' => $row['size']));
                }
                else {
                    $obj->status = $this->Lang('cantdownload');
                }
                break;

            case 'upgrade':
                $modpath = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
                if( (($writable && is_dir($modpath) && is_directory_writable($modpath)) ||
                     ($writable && !file_exists($modpath) )) && $caninstall ) {
                    $obj->candownload = TRUE;
                    $obj->status = $this->CreateLink( $id, 'installmodule', $returnid,
                                                      $this->Lang('upgrade'),
                                                      array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'],
                                                            'size' => $row['size']));
                }
                else {
                    $obj->status = $this->Lang('cantdownload');
                }
                break;
            } // case

            $obj->size = (int)((float) $row['size'] / 1024.0 + 0.5);
            if( isset( $row['description'] ) )  $obj->description=$row['description'];
            $search_data[] = $obj;
        }
        $_SESSION['modmgr_search'] = serialize($search_data);
        $_SESSION['mogmgr_searchterm'] = $term;
        $_SESSION['modmgr_searchadv'] = $advanced;
    }
    catch( Exception $e ) {
        $clear_search();
        $nonemsg = $e->GetMessage();
    }
}

if( $search_data ) {
    $tpl->assign('search_data',$search_data);
    if( !$caninstall ) {
        $tpl->assign('permsmsg',$this->Lang('error_permissions'));
    }
}
elseif( $nonemsg ) {
    $tpl->assign('nofindmsg',$nonemsg);
}
$tpl->assign('term',$term);
$tpl->assign('advanced',$advanced);
$tpl->assign('formstart',$this->CreateFormStart($id,'defaultadmin','','post','',false,'',array('__activetab'=>'search')));
$tpl->assign('formend',$this->CreateFormEnd());
?>
