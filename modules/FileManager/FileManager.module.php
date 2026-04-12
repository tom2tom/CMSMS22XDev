<?php
# FileManager module for CMS Made Simple
# Copyright (c) 2006-12 Morten Poulsen <morten@poulsen.org>
# (c) 2012 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

include_once __DIR__.DIRECTORY_SEPARATOR.'fileinfo.php';

final class FileManager extends CMSModule
{
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'content'; }
    public function GetAuthor() { return 'Morten Poulsen (Silmarillion)'; }
    public function GetAuthorEmail() { return 'morten@poulsen.org'; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetEventDescription($name) { return $this->Lang('eventdesc_'.$name); }
    public function GetEventHelp($name) { return $this->Lang('eventhelp_'.$name); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetName() { return 'FileManager'; }
    public function GetVersion() { return '1.6.16'; }
    public function HasAdmin() { return true; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function IsAdminOnly() { return false; }
    public function IsPluginModule() { return true; }
    public function LazyLoadFrontend() { return true; }
    public function MinimumCMSVersion() { return '2.2.2'; }
    public function UninstallPostMessage() { return $this->Lang('uninstalled'); }
    public function UninstallPreMessage() { return $this->Lang('really_uninstall'); }

    public function AccessAllowed() { return $this->CheckPermission('Modify Files'); }
    public function AdvancedAccessAllowed() { return $this->CheckPermission('Use FileManager Advanced'); }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Modify Files') ||
               $this->CheckPermission('Use FileManager Advanced');
    }

    public function HasCapability($capability,$params = array())
    {
        switch( $capability ) {
            case CmsCoreCapabilities::PLUGIN_MODULE:
            case CmsCoreCapabilities::TASKS:
            case 'upload':
                return true;
            default:
                return false;
        }
    }

    public function GetFileIcon($extension,$isdir = false)
    {
        $iconsize = $this->GetPreference('iconsize',24);
        $iconsize = str_replace('px','',$iconsize); //adjust deprecated format
        $emsize =  $iconsize / 16;
        $baseurl = $this->GetModuleURLPath();
        // TODO migrate the following to the template
        if ($isdir) {
            $result = '<img src="'.$baseurl.'/icons/themes/default/extensions/32px/dir.png" alt="directory" style="height:'.$emsize.'em;vertical-align:middle;border:0">';
        } else {
            if ($extension) {
                $extension = strtolower($extension);
                if ($extension[0] == '.') { $extension = substr($extension,1); }
            } else {
                $extension = 'other'; // unmatched among icons
            }
            if (file_exists(cms_join_path($this->GetModulePath(),'icons','themes','default','extensions','32px',$extension.'.png'))) {
                $iname = $extension;
            } else {
                $iname = '0';
            }
            $result = '<img src="'.$baseurl.'/icons/themes/default/extensions/32px/'.$iname.'.png" alt="'.$extension.'-file" style="height:'.$emsize.'em;vertical-align:middle;border:0">';
        }
        return $result;
    }

    protected function Slash($str,$str2 = '',$str3 = '')
    {
        // three strings not supported yet...
        if ($str == "") return $str2;
        if ($str2 == "") return $str;
        if ($str[strlen($str)-1] != "/") {
            if ($str2[0] != "/") {
                return $str."/".$str2;
            } else {
                return $str.$str2;
            }
        } elseif ($str2[0] != "/") {
            return $str.$str2;
        } else {
            return $str.substr($str2,1); //trim away one of the slashes
        }
    }

    public function GetPermissions($path,$file)
    {
        $realpath = $this->Slash(CMS_ROOT_PATH,$path);
        $statinfo = stat($this->Slash($realpath,$file));
        return $statinfo["mode"];
    }

/*  //@deprecated since CMSMS 2.0
    //this method used only in FileManager actions which are unused in CMSMS2+
    public function GetMode($path,$file)
    {
        $realpath = $this->Slash(CMS_ROOT_PATH,$path);
        $statinfo = stat($this->Slash($realpath,$file));
        return filemanager_utils::format_permissions($statinfo["mode"]);
    }

    //@deprecated since CMSMS 2.0
    //this method used only in FileManager actions which are unused in CMSMS2+
    public function GetModeWin($path,$file)
    {
        $realpath = $this->Slash($realpath,$file);
        if (is_writable($realpath)) {
            return "777";
        } else {
            return "444";
        }
    }

    //@deprecated since CMSMS 2.0
    //this method used only in FileManager actions which are unused in CMSMS2+
    public function GetModeTable($id,$permissions)
    {
        $smarty = cmsms()->GetSmarty();
        $modname = $this->GetName();
        $tpl = $smarty->createTemplate("module_file_tpl:$modname;modetable.tpl",null,$modname); // no parent
        $tpl->assign('ownertext', $this->Lang('owner'));
        $tpl->assign('groupstext', $this->Lang('group'));
        $tpl->assign('otherstext', $this->Lang('others'));

        $ownerr = ($permissions & 0400)?'1':'0';
        $tpl->assign('ownerr', $this->CreateInputCheckbox($id,'ownerr','1',$ownerr));

        $ownerw = ($permissions & 0200)?'1':'0';
        $tpl->assign('ownerw', $this->CreateInputCheckbox($id,'ownerw','1',$ownerw));

        $ownerx = ($permissions & 0100)?'1':'0';
        $tpl->assign('ownerx', $this->CreateInputCheckbox($id,'ownerx','1',$ownerx));

        $groupr = ($permissions & 0040)?'1':'0';
        $tpl->assign('groupr', $this->CreateInputCheckbox($id,'groupr','1',$groupr));

        $groupw = ($permissions & 0020)?'1':'0';
        $tpl->assign('groupw', $this->CreateInputCheckbox($id,'groupw','1',$groupw));

        $groupx = ($permissions & 0010)?'1':'0';
        $tpl->assign('groupx', $this->CreateInputCheckbox($id,'groupx','1',$groupx));

        $othersr = ($permissions & 0004)?'1':'0';
        $tpl->assign('othersr', $this->CreateInputCheckbox($id,'othersr','1',$othersr));

        $othersw = ($permissions & 0002)?'1':'0';
        $tpl->assign('othersw', $this->CreateInputCheckbox($id,'othersw','1',$othersw));

        $othersx = ($permissions & 0001)?'1':'0';
        $tpl->assign('othersx', $this->CreateInputCheckbox($id,'othersx','1',$othersx));

        return $tpl->fetch();
    }

    //@deprecated since CMSMS 2.0
    //this method used only in actions which are unused in CMSMS2+
    public function GetModeFromTable($params)
    {
        $owner = 0;
        if (isset($params['ownerr'])) $owner += 4;
        if (isset($params['ownerw'])) $owner += 2;
        if (isset($params['ownerx'])) $owner++;
        $group = 0;
        if (isset($params['groupr'])) $group += 4;
        if (isset($params['groupw'])) $group += 2;
        if (isset($params['groupx'])) $group++;
        $others = 0;
        if (isset($params['othersr'])) $others += 4;
        if (isset($params['othersw'])) $others += 2;
        if (isset($params['othersx'])) $others++;
        return $owner.$group.$others;
    }
*/
    public function GetThumbnailLink($file,$path)
    {
        $path = trim($path, ' \\/');
        $path = strtr($path,'\\/',DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
        $imagepath = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.'thumb_'.$file['name'];
        if (file_exists($imagepath)) {
            $url = CMS_ROOT_URL.'/'.strtr($path,'\\','/');
            $imageurl = $url.'/thumb_'.$file['name'];
            $image = '<img src="'.$imageurl.'" alt="'.$file['name'].'" title="'.$file['name'].'">';
            $url = $this->create_url('m1_','view','',['file' => $this->encodefilename($file['name'])]);
//          $result = '<a href="'.$file['url'].'" target="_blank">'.$image.'</a>';
            $result = '<a href="'.$url.'" target="_blank">'.$image.'</a>';
            return $result;
        }
        return '';
    }

    public function WinSlashes($path)
    {
        return str_replace("/","\\",$path);
    }

    public function Slashes($path)
    {
        $result = strtr($path,"\\","/");
        return str_replace("//","/",$result);
    }

    protected function SetupHeadtext($action)
    {
        $out = '';
        $urlpath = $this->GetModuleURLPath();
        $cssfiles = array();
        switch ($action) {
            case 'defaultadmin':
                $jsfiles = array('jquery-file-upload/jquery.fileupload.min.js');
                break;
            case 'resizecrop':
                $jsfiles = array('jrac/jquery.jrac.min.js');
                $cssfiles = array('js/jrac/style.jrac.css');
                break;
            case 'rotate':
                $jsfiles = array('jqueryrotate/jQueryRotate-2.3.min.js');
                break;
/*          case 'admin_fileview':
            case 'newdir':
            case 'rename':
            case 'delete':
            case 'copy':
            case 'move':
            case 'unpack':
            case 'thumb':
*/
            default:
                $jsfiles = array();
        }
        $cssfiles += array(999=>'lib/filemanager.css'); // always last

        foreach( $jsfiles as $one ) {
            $out .= sprintf("<script src=\"$urlpath/js/%s\" defer></script>\n",$one);
        }
        foreach( $cssfiles as $one ) {
            $out .= sprintf("<link rel=\"stylesheet\" href=\"$urlpath/%s\">\n",$one);
        }

        if( $out ) {
            CMSMS\HookManager::add_hook('admin_add_headtext',function() use($out) {
               return $out;
            });
        }
    }

    protected function encodefilename($filename)
    {
        $config = cms_config::get_instance();
        return base64_encode(sha1(__FILE__.$config['dbpassword'].$filename).'|'.$filename); //TODO another less-important entropy-source
    }

    protected function decodefilename($encodedfilename)
    {
        $config = cms_config::get_instance();
        list($sig,$filename) = explode('|',base64_decode($encodedfilename),2);
        if( sha1(__FILE__.$config['dbpassword'].$filename) == $sig ) return $filename; //TODO another less-important entropy-source
        return '';
    }

    public function get_tasks()
    {
        $fp = cms_join_path(__DIR__,'lib','class.ClearUserDirsJob.php');
        if( is_file($fp) ) {
            if( func_num_args() > 0 && func_get_arg(0) ) { // want filepath(s)
                return [$fp];
            } else {
                require_once $fp;
                return [new FileManager\ClearUserDirsJob()];
            }
        }
        return [];
    }

    public function GetAdminMenuItems()
    {
        $out = array();

        if( $this->CheckPermission('Modify Files') ) {
            $out[] = CmsAdminMenuItem::from_module($this);
        }

        if( $this->CheckPermission('Modify Site Preferences') ) {
            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'siteadmin';
            $obj->title = $this->Lang('title_filemanager_settings');
            $obj->description = $this->Lang('desc_filemanager_settings');
            $obj->action = 'admin_settings';
            $obj->url = $this->create_url('m1_','admin_settings');
            $out[] = $obj;
        }
        return $out;
    }
} // end of class
