<?php
/*
CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Derived from 2018-2021 UserGuide2 module by Chris Taylor.
In turn derived from 2012-2014 UsersGuide module by Jean-Christophe Ghio.
In turn derived from 2011-2012 OwnersManual module by Wayne O'Neil.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA.
Or read it online at: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
class UserGuide extends CMSModule
{
    const MANAGE_PERM = 'Modify Userguides';
    const RESTRICT_PERM = 'View Restricted UserGuides';
    const SETTINGS_PERM = 'Modify Userguide Preferences';

    public function GetAdminDescription() { return $this->Lang('admindescription'); }
    public function GetAdminSection() { return $this->GetPreference('adminSection', 'content'); }
    public function GetAuthor() { return ''; } // N/A for core module
    public function GetAuthorEmail() { return ''; } // ditto
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetName() { return 'UserGuide'; }
    public function GetVersion() { return '2.0'; }
    public function HasAdmin() { return true; }
    public function IsPluginModule() { return true; }
    public function LazyLoadAdmin() { return true; }
    public function LazyLoadFrontend() { return true; }
    public function MinimumCMSVersion() { return '2.2'; }
    public function UninstallPreMessage() { return $this->Lang('ask_uninstall'); }
    public function VisibleToAdminUser() { return true; }

    public function GetFriendlyName()
    {
        $val = $this->GetPreference('customLabel');
        if (!$val) {
            $val = $this->Lang('friendlyname');
        }
        return $val;
    }

    public function GetHelp()
    {
        $this->CreateParameter('gid', 0, $this->Lang('param_guideid'));
        $this->CreateParameter('list', '', $this->Lang('param_list'));
        $this->CreateParameter('tplid', 0, $this->Lang('param_templateid'));
        $this->CreateParameter('template_name', '', $this->Lang('param_template_name'));
        $this->CreateParameter('sheetid', 0, $this->Lang('param_stylesheetid'));
        $this->CreateParameter('stylesheet_name', '', $this->Lang('param_stylesheet_name'));
        $licence = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'helplicense.htm'); // un-translated portion of help
        return $this->Lang('help', $licence);
    }

    public function GetHeaderHTML()
    {
        $me = $this->GetName();
        $baseurl = $this->GetModuleURLPath();
        $tmp = "<link rel=\"stylesheet\" href=\"{$baseurl}/lib/css/{$me}_admin.css\">\n"; // or .min for production
        if ($this->GetPreference('customCSS')) {
            $config = cms_config::get_instance();
            $astdir = $config['assets_dir'];
            $custom = cms_join_path(CMS_ROOT_PATH, $astdir, 'module_custom', $me, 'custom.css');
            if (file_exists($custom)) {
                $url = implode('/', CMS_ROOT_URL, $astdir, 'module_custom', $me, 'custom.css');
                $tmp .= "<link rel=\"stylesheet\" href=\"$url\">\n";
            }
        }
//if needed $tmp .= "<script src=\"$baseurl/lib/js/{$me}_admin.js\"></script>\n"; // or .min for production
        return $tmp;
    }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
            case CmsCoreCapabilities::PLUGIN_MODULE:
            case CmsCoreCapabilities::ADMINSEARCH:
                return true;
            default:
                return false;
        }
    }

    public function InitializeAdmin() {}

    public function InitializeFrontend()
    {
        $this->RestrictUnknownParams();
        $this->SetParameterType('gid', CLEAN_INT);
        $this->SetParameterType('list', CLEAN_STRING);
        $this->SetParameterType('tplid', CLEAN_INT);
        $this->SetParameterType('template_name', CLEAN_STRING);
        $this->SetParameterType('sheetid', CLEAN_INT);
        $this->SetParameterType('stylesheet_name', CLEAN_STRING);
/* TODO etc
        $this->SetParameterType('pagelength', CLEAN_INT);
        $this->SetParameterType(CLEAN_REGEXP.'/X.* NO WHITESPACE HERE /',CLEAN_STRING);
*/
    }

    public static function type_lang_callback($type)
    {
        $mod = cms_utils::get_module('UserGuide');
        return $mod->Lang('type_'.$type);
    }

    public static function template_help_callback($type)
    {
        $type = trim((string)$type);
        $fn = cms_join_path(__DIR__, 'doc', "tpltype_$type.htm");
        if (is_file($fn)) {
            return file_get_contents($fn);
        }
        return '';
    }

    public static function type_reset_defaults(CmsLayoutTemplateType $type)
    {
        if ($type->get_originator() != 'UserGuide') {
            throw new CmsLogicException('Cannot reset contents for this template type');
        }

        switch ($type->get_name()) {
            case 'oneguide':
                $fn = 'orig_guide_template.tpl';
                break;
            case 'listguides':
                $fn = 'orig_list_template.tpl';
                break;
            default:
                return '';
        }

        $fn = cms_join_path(__DIR__, 'templates', $fn);
        if (file_exists($fn)) {
            return file_get_contents($fn);
        }
        return '';
    }

    public function get_adminsearch_slaves()
    {
        return ['\UserGuide\UserGuideSearch_slave'];
    }
} // class
