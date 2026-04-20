<?php
#-------------------------------------------------------------------------
# Module: DesignManager - A CMSMS addon module to provide template and
# stylesheet management.
# (c) 2015 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# Or read it online: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
if( !isset($gCms) ) exit;

final class DesignManager extends CMSModule
{
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetVersion() { return '1.3'; }
    public function MinimumCMSVersion()  { return '2.2.23F2'; } // uses additional design-object properties 
    public function LazyLoadAdmin() { return TRUE; }
    public function LazyLoadFrontend() { return TRUE; }
    public function IsPluginModule() { return FALSE; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return ''; }
    public function HasAdmin() { return TRUE; }
    public function GetAdminSection() { return 'layout'; }
    public function AllowAutoInstall() { return TRUE; }
    public function GetHelp() { return CmsLangOperations::lang_from_realm('help','help_designmanager_help'); }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }

    public function VisibleToAdminUser()
    {
        return (
            $this->CheckPermission('Add Templates') ||
            $this->CheckPermission('Modify Templates') ||
            $this->CheckPermission('Manage Stylesheets') ||
            $this->CheckPermission('Manage Designs') ||
            !empty(CmsLayoutTemplate::get_editable_templates(get_userid()))
        );
    }

    public function GetAdminMenuItems()
    {
        $out = [];
        if( $this->VisibleToAdminUser() ) $out[] = CmsAdminMenuItem::from_module($this);

        if( $this->CheckPermission('Modify Site Preferences') ) {
            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'siteadmin';
            $obj->title = $this->Lang('title_designmanager_settings');
            $obj->description = $this->Lang('desc_designmanager_settings');
            $obj->action = 'admin_settings';
            $out[] = $obj;
        }
        return $out;
    }

    public function GetEventHelp($eventname)
    {
        return lang('event_help_'.$eventname);
    }

    public function GetEventDescription($eventname)
    {
        return lang('event_desc_'.$eventname);
    }

    /**
     * Report whether the specified item is a directory and if so,
     * whether it does NOT contain (directly or indirectly) any file
     * (other than one or more index.html) i.e. can be presumed to have
     * no active usage.
     * Folders are ignored.
     * @since 1.2
     *
     * @param string $dirpath Absolute filepath
     * @return bool indicating it's unused
     */
    public function is_dir_unused($dirpath)
    {
        if( is_dir($dirpath) ) {
            $items = scandir($dirpath);
            if( $items ) {
                foreach( $items as $name ) {
                    if( !($name == '.' || $name == '..' || $name == 'index.html') ) {
                        $sp = "$dirpath/$name";
                        if( is_dir($sp) ) {
                            return $this->is_dir_unused($sp); // recurse
                        }
                        elseif( is_link($sp) ) {
                            $sp = readlink($sp);
                            if( $sp && is_dir($sp) ) {
                                return $this->is_dir_unused($sp);
                            }
                        }
                        return FALSE;
                    }
                }
            }
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Handle module response for AJAX actions.
     * The JSON representation of $data is echo'd, before exiting.
     *
     * @param  string $status The status of returned response, e.g. error, success, warning, info
     * @param  string $message The response
     * @param  mixed $data string or array of response data
     * @return bool false upon invalid $_SERVER property, or else void/not at all
     */
    public function GetJSONResponse($status, $message, $data = null) // mixed value
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

            $num = count(ob_list_handlers());
            for ($cnt = 0; $cnt < $num; $cnt++) { ob_end_clean(); }

            header('Content-Type:application/json; charset=utf-8');

            if ($data) {
                $json = json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
            } else {
                $json = json_encode(['status' => $status, 'message' => $message]);
            }

            echo $json;
            exit;
        }

        return FALSE;
    }
} // class
