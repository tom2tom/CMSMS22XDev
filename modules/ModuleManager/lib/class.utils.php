<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: modmgr_utils
# (c) 2011 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
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
#END_LICENSE

final class modmgr_utils
{
    protected function __construct() {}

    public static function get_installed_modules($include_inactive = FALSE, $as_hash = FALSE)
    {
        $modops = ModuleOperations::get_instance();
        $module_list = $modops->GetInstalledModules($include_inactive);

        $results = array();
        foreach( $module_list as $module_name ) {
            $inst = $modops->get_module_instance($module_name);
            if( !$inst ) continue;

            $details = array();
            $details['name'] = $inst->GetName();
            $details['description'] = $inst->GetDescription();
            $details['version'] = $inst->GetVersion();
            $details['active'] = $modops->IsModuleActive($module_name);

            if( $as_hash ) {
                $results[$module_name] = $details;
            }
            else {
                $results[] = $details;
            }
        }
        return array(true,$results);
    }

    private static function uasort_cmp_details( $e1, $e2 )
    {
        $n1 = $n2 = '';
        $v1 = $v2 = '';
        if( is_object($e1) ) {
            $n1 = $e1->name;
            $v1 = $e1->version;
        }
        else {
            $n1 = $e1['name'];
            $v1 = $e1['version'];
        }
        if( is_object($e2) ) {
            $n2 = $e2->name;
            $v2 = $e2->version;
        }
        else {
            $n2 = $e2['name'];
            $v2 = $e2['version'];
        }

        if( strcasecmp($n1,$n2) < 0 ) {
            return -1;
        }
        elseif( strcasecmp($n1,$n2) > 0 ) {
            return 1;
        }
        return version_compare( $e2['version'], $e1['version'] );
    }

    public static function build_module_data( &$xmldetails, &$installdetails, $newest = true )
    {
        if( !is_array($xmldetails) ) return [];

        // sort
        uasort( $xmldetails, array('modmgr_utils','uasort_cmp_details') );

        $mod = cms_utils::get_module('ModuleManager');

        //
        // Process the xmldetails, and only keep the latest version
        // of each (according to a preference)
        //
        // Note: should be redundant with 1.2, but kept in here for
        // a while just in case..
        if( $newest && $mod->GetPreference('onlynewest',1) == 1 ) {
            $thexmldetails = array();
            $prev = '';
            foreach( $xmldetails as $det ) {
                if( is_array($prev) && $prev['name'] == $det['name'] ) continue;

                $prev = $det;
                $thexmldetails[] = $det;
            }
            $xmldetails = $thexmldetails;
        }

        $results = array();
        foreach( $xmldetails as $det1 ) {
            $found = 0;
            foreach( $installdetails as $det2 ) {
                if( $det1['name'] == $det2['name'] ) {
                    $found = 1;
                    // if the version of the xml file is greater than that of the
                    // installed module, we have an upgrade
                    $res = version_compare( $det1['version'], $det2['version'] );
                    if( $res == 1 ) {
                        $det1['status'] = 'upgrade';
                    }
                    else if( $res == 0 ) {
                        $det1['status'] = 'uptodate';
                    }
                    else {
                        $det1['status'] = 'newerversion';
                    }

                    $results[] = $det1;
                    break;
                }
            }
            if( $found == 0 ) {
                // we don't have this module installed
                $det1['status'] = 'notinstalled';
                $results[] = $det1;
            }
        }

        //
        // Do a third loop
        // and check min and max cms version
        //
        global $CMS_VERSION;
        $results2 = array();
        foreach( $results as $oneresult ) {
            if( (!empty($oneresult['maxcmsversion']) && cmsversion_compare($CMS_VERSION,$oneresult['maxcmsversion']) > 0) ||
                (!empty($oneresult['mincmsversion']) && cmsversion_compare($CMS_VERSION,$oneresult['mincmsversion']) < 0) ) {
                $oneresult['status'] = 'incompatible';
            }
            $results2[] = $oneresult;
        }
        $results = $results2;

        // now we have everything
        // let's try sorting it
        uasort( $results, array('modmgr_utils','uasort_cmp_details') );
        return $results;
    }

    public static function get_module_xml($filename,$size,$md5sum = '')
    {
        $mod = cms_utils::get_module('ModuleManager');
        $xml_filename = modulerep_client::get_repository_xml($filename,$size);
        if( !$xml_filename ) throw new CmsCommunicationException($mod->Lang('error_downloadxml',$filename));

        if( !$md5sum ) $md5sum = modulerep_client::get_module_md5($filename);
        $dl_md5 = md5_file($xml_filename);

        if( $md5sum != $dl_md5 ) {
            @unlink($xml_filename);
            throw new CmsInvalidDataException($mod->Lang('error_checksum',$md5sum,$dl_md5));
        }

        return $xml_filename;
    }

    public static function is_connection_ok()
    {
        static $ok = -1;
        if( $ok != -1 ) return $ok;

        $mod = cms_utils::get_module('ModuleManager');
        $url = $mod->GetPreference('module_repository');
        if( $url ) {
            $url .= '/version';
            $req = new modmgr_cached_request($url);
            $req->setTimeout(3);
            $req->execute($url);
            if( ($status = $req->getStatus()) == 200 ) { // or some 300's ok?
                $tmp = $req->getResult();
                if( !$tmp ) {
                    $req->clearCache();
                    $ok = FALSE;
                    return FALSE;
                }

                $data = json_decode($tmp,true);
                if( $data && version_compare($data,MINIMUM_REPOSITORY_VERSION) >= 0 ) {
                    $ok = TRUE;
                    return TRUE;
                }
                if( !$data ) {
                    audit('',$mod->GetName(),'Invalid data from module repository');
                    $req->clearCache();
                }
            }
            else {
                $req->clearCache();
                audit($status,$mod->GetName(),'Cannot connect to module repository');
            }
        }
        $ok = FALSE;
        return FALSE;
    }

    public static function get_status($date)
    {
        $ts = strtotime($date);
        $stale_ts = strtotime('-2 years');
        if( $ts <= $stale_ts ) return 'stale';
        $warn_ts = strtotime('-18 months');
        if( $ts <= $warn_ts ) return 'warn';
        $new_ts = strtotime('-1 month');
        if( $ts >= $new_ts ) return 'new';
        return '';
    }

    public static function get_images($tpl)
    {
        // this is a bit ugly.
        $mod = cms_utils::get_module('ModuleManager');
        $base_url = $mod->GetModuleURLPath();

        $tpl->assign('stale_img',
         '<img src="'.$base_url.'/images/error.png" title="'.$mod->Lang('title_stale').'" alt="stale" height="16">');

        $tpl->assign('missingdep_img',
         '<img src="'.$base_url.'/images/puzzle.png" title="'.$mod->Lang('title_missingdeps').'" alt="missingdeps" height="16">');

        $tpl->assign('warn_img',
         '<img src="'.$base_url.'/images/warn.png" title="'.$mod->Lang('title_warning').'" alt="warning" height="16">');

        $tpl->assign('new_img',
         '<img src="'.$base_url.'/images/new.png" title="'.$mod->Lang('title_new').'" alt="new" height="16">');

        $tpl->assign('star_img',
         '<img src="'.$base_url.'/images/star.png" title="'.$mod->Lang('title_star').'" alt="star" height="16">');

        $tpl->assign('system_img',
         '<img src="'.$base_url.'/images/system.png" title="'.$mod->Lang('title_system').'" alt="system" height="16">');

        $tpl->assign('deprecated_img',
         '<img src="'.$base_url.'/images/deprecate.png" title="'.$mod->Lang('title_deprecated').'" alt="deprecated" height="16">');
    }
} // end of class
