<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: modulerep_client
# (c) 2011 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program. If not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
#END_LICENSE

namespace ModuleManager;

use cms_http_request;
use cms_utils;
use CmsCommunicationException;
//use CmsException;
use CmsInvalidDataException;
use ModuleOperations;
use const CMS_VERSION;
use const PUBLIC_CACHE_LOCATION;

/**
 * All results from upstream are json-encoded except for: 'modulegetpart' 'modulexml'
 * Some status codes from upstream are 400 when no suitable data are found, which might be correct, not 'bad'
 */
final class modulerep_client
{
    private static $_latest_installed_modules;

    private function __construct() {}

    /**
     * @return array tuple [0] bool [1] message | request result
     */
    public static function get_repository_version()
    {
        $mod = cms_utils::get_module('ModuleManager');
        $url = $mod->GetPreference('module_repository');
        if( !$url ) return [false,$mod->Lang('error_norepositoryurl')];
        $url .= '/version';

        $req = new cached_http_request();
        $req->send($url);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status != 200 || $result == '' ) { // or some 300's ok?
            $req->clear();
            return array(false,$mod->Lang('error_request_problem'));
        }
        $data = json_decode($result,true);
        return array(true,$data);
    }

    /**
     * Given an array of hashes with name/version members return module info for all matches.
     * maximum of 25 rows, and no guarantee that there will be results for each request.
     *
     * @param mixed $input should be array
     * @return mixed array (maybe empty, maybe tuple[false,error-message] | null
     * @throws CmsInvalidDataException or CmsCommunicationException
     */
    public static function get_multiple_moduleinfo($input)
    {
        $mod = cms_utils::get_module('ModuleManager');
        if( !$input || !is_array($input) ) throw new CmsInvalidDataException($mod->Lang('error_missingparam'));

        $out = [];
        foreach( $input as $key => $data ) {
            if( is_array($data) && !empty($data['name']) && !empty($data['version']) ) {
                $out[] = array('name' => $data['name'],'version' => $data['version']);
            }
            elseif( is_string($key) && (int)$key == 0 ) {
                $out[] = array('name' => $key,'version' => $data);
            }
            else {
                throw new CmsInvalidDataException($mod->Lang('error_missingparam'));
            }
        }
        if( !$out ) throw new CmsInvalidDataException($mod->Lang('error_missingparam'));

        $url = self::get_repourl($mod);
        if( !$url ) return [false,$mod->Lang('error_norepositoryurl')];
        $url .= '/multimoduleinfo';
        $qparms = ['data' => json_encode($out)];

        $req = new cached_http_request();
        $req->send($url,$qparms);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 200 && $result == '' ) {
            return [];
        }
        if( $status == 400 ) {
            return [];
        }
        if( $status != 200 || $result == '' ) { // or some 300's ok? empty result ok?
            $req->clear();
            throw new CmsCommunicationException($mod->Lang('error_request_problem'));
        }
        return json_decode($result,true);
    }

    /**
     * @return array tuple [0] bool [1] error-message | request result
     */
    public static function get_repository_modules($prefix = '',$newest = true,$exact = false)
    {
        $mod = cms_utils::get_module('ModuleManager');
        $url = self::get_repourl($mod);
        if( !$url ) return [false,$mod->Lang('error_norepositoryurl')];
        $url .= '/moduledetailsgetall';

        $qparms = [
         'newest' => ($newest) ? 1:0,
         'clientcmsversion' => CMS_VERSION //TODO adjust if needed for version_compare()
        ];
        if( $prefix ) $qparms['prefix'] = ltrim($prefix);
        if( $exact ) $qparms['exact'] = 1;

        $req = new cached_http_request();
        $req->send($url,$qparms);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 200 && $result == '' ) {
            return [true,[]];
        }
        if( $status == 400 ) {
            return [true,[]];
        }
        if( $status != 200 || $result == '' ) { // some 300's ok? empty result ok?
            $req->clear();
            return [false,$mod->Lang('error_request_problem')];
        }

        $data = json_decode($result,true);
        return array(true,$data);
    }

    public static function get_module_dependencies($module_name,$module_version = '')
    {
        $mod = cms_utils::get_module('ModuleManager');
        if( !$module_name ) throw new CmsInvalidDataException($mod->Lang('error_missingparams'));
        $url = self::get_repourl($mod);
        if( !$url ) throw new CmsInvalidDataException($mod->Lang('error_norepositoryurl'));
        $url .= '/moduledependencies';

        $qparms = ['name' => $module_name];
        if( $module_version ) $qparms['version'] = $module_version;
        $req = new cached_http_request();
        $req->send($url,$qparms);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 200 && $result == '' ) {
            return [true,'']; // no dep
        }
        if( $status == 400 ) { //  not found
            return [false,'']; // failed but don't throw
        }
        if( $status != 200 || $result == '' ) { // or some 300's ok? empty result ok if no dep?
            $req->clear();
            throw new CmsCommunicationException($mod->Lang('error_request_problem'));
        }

        $data = json_decode($result,true);
        return $data;
    }

    public static function get_module_depends($xmlfile)
    {
        $mod = cms_utils::get_module('ModuleManager');
        if( !$xmlfile ) throw new CmsInvalidDataException($mod->Lang('error_nofilename'));
        $url = self::get_repourl($mod);
        if( $url == '' ) throw new CmsInvalidDataException($mod->Lang('error_norepositoryurl'));
        $url .= '/moduledepends';

        $req = new cached_http_request();
        $req->setMethod('POST');
        $req->send($url,array('name'=>$xmlfile));
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 200 && $result == '' ) { //400 might be not found or no dep?
            return []; // no dep
        }
        if( $status != 200 || $result == '' ) {
            $req->clear();
            throw new CmsCommunicationException($mod->Lang('error_request_problem'));
        }
        $data = json_decode($result,true);
        return $data;
    }

    /**
     * @return mixed filename string | false
     */
    public static function get_repository_xml($xmlfile, $size = -1)
    {
        if( !$xmlfile ) return false;

        // this is 'manually' cached - no age-related cleanup
        $tmpname = PUBLIC_CACHE_LOCATION.DIRECTORY_SEPARATOR.'modmgr_'.md5(__DIR__.$xmlfile).'.dat';
        $mod = cms_utils::get_module('ModuleManager');
        if( !file_exists($tmpname) || $mod->GetPreference('disable_caching',0) || (time() - filemtime($tmpname)) > 7200 ) {
            @unlink($tmpname);

            // must download
            $orig_chunksize = $mod->GetPreference('dl_chunksize',256);
            $chunksize = $orig_chunksize * 1024;
            $url = self::get_repourl($mod);
            if( $url == '' ) return false;

            if( $size <= $chunksize ) {
                // downloading the whole file at one shot.
                $url .= '/modulexml';
                $req = new cms_http_request();
                $req->setMethod('POST');
                $req->send($url,array('name'=>$xmlfile));
                $status = $req->GetStatus();
                $result = $req->GetResult();
                if( $status != 200 || $result == '' ) { // or some 300's ok?
                    return false;
                }
                $fh = fopen($tmpname,'wb');
                fwrite($fh,$result);
                fclose($fh);
                return $tmpname;
            }

            // download in chunks
            $url .= '/modulegetpart';
            $nchunks = (int)ceil($size / $chunksize);
            $req = new cms_http_request();
            $req->setMethod('POST');
            for( $i = 0; $i < $nchunks; $i++ ) {
                $result = $req->send($url, array('name'=>$xmlfile,'partnum'=>$i,'sizekb'=>$orig_chunksize));
                $status = $req->GetStatus();
                if( $status != 200 || $result == '' ) { // or some 300's ok?
                    unlink($tmpname);
                    return false;
                }

                $fh = fopen($tmpname,'a');
                fwrite($fh,base64_decode($result));
                fclose($fh);
            }
        }
        return $tmpname;
    }

    /**
     * @return string
     * @throws CmsInvalidDataException or CmsCommunicationException
     */
    public static function get_module_md5($xmlfile)
    {
        $mod = cms_utils::get_module('ModuleManager');
        if( !$xmlfile ) throw new CmsInvalidDataException($mod->Lang('error_nofilename'));
        $url = self::get_repourl($mod);
        if( $url == '' ) throw new CmsInvalidDataException($mod->Lang('error_norepositoryurl'));
        $url .= '/modulemd5sum';

        $req = new cached_http_request();
        $req->send($url,['name'=>$xmlfile]);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status != 200 || $result == '' ) { // or some 300's ok?
            $req->clear();
            throw new CmsCommunicationException($mod->Lang('error_request_problem'));
        }
        $data = json_decode($result,true);
        return $data;
    }

    public static function search($term, $advanced)
    {
        $mod = cms_utils::get_module('ModuleManager');
        $url = self::get_repourl($mod);
        if( $url == '' ) {
            return [false,$mod->Lang('error_norepositoryurl')];
        }
        $url .= '/modulesearch';

        $qparms = [
         'filter' => [
          'term' => $term,
          'advanced' => (int)$advanced,
          'newest' => 1,
          'sortby' => 'score'
          ],
         'clientcmsversion' => CMS_VERSION //TODO adjust if needed for version_compare()
        ];

        $req = new cached_http_request();
        $req->send($url,array('json'=>json_encode($qparms)));
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 200 && $result == '' ) {
            return array(TRUE,''); // no result
        }
        if( $status != 200 || $result == '' ) {
            $req->clear();
            return array(false,$mod->Lang('error_request_problem'));
        }

        $data = json_decode($result,true);
        return array(TRUE,$data);
    }

    /**
     * Return the latest info about specified modules.
     *
     * @param array $modules module-names
     * @return array info about modules or empty
     * @throws CmsInvalidDataException or CmsCommunicationException
     */
    public static function get_modulelatest($modules)
    {
        $mod = cms_utils::get_module('ModuleManager');
        if( !$modules || !is_array($modules) ) throw new CmsInvalidDataException($mod->Lang('error_missingparam'));

        $url = self::get_repourl($mod);
        if( $url == '' ) throw new CmsInvalidDataException($mod->Lang('error_norepositoryurl'));
        $url .= '/upgradelistgetall';
        $qparms = [
         'names' =>  implode(',',$modules),
         'newest' => 1,
         'clientcmsversion' => CMS_VERSION //TODO adjust if needed for version_compare()
        ];

        $req = new cached_http_request();
        $req->send($url,$qparms);
        $status = $req->getStatus();
        if( $status != 200 ) { // 400 aka nil upgrades can be valid
            throw new CmsCommunicationException($mod->Lang('error_request_problem'));
        }
        $result = $req->getResult();
        if( !$result ) { // nil upgrades can be valid
            return []; //throw new ModuleNoDataException();
        }

        $data = json_decode($result,true);
        if( !$data || !is_array($data) ) {
            throw new CmsInvalidDataException($mod->Lang('error_nomatchingmodules'));
        }
        return $data;
    }

    /**
     * Return the latest info about installed modules.
     * on success returns associative array of info about modules
     * on error throw exception.
     * @return array
     */
    public static function get_allmoduleversions()
    {
        if( is_array(self::$_latest_installed_modules) ) {
            return self::$_latest_installed_modules;
        }
        $modules = ModuleOperations::get_instance()->GetInstalledModules();
        self::$_latest_installed_modules = self::get_modulelatest($modules);
        return self::$_latest_installed_modules;
    }

    /**
     * Return info about installed modules that have newer versions available.
     * @return mixed false on error, associative array maybe empty on success
     */
    public static function get_newmoduleversions()
    {
        $versions = self::get_allmoduleversions();
        if( !is_array($versions) ) return false;
        if( count($versions) == 2 && $versions[0] === false ) return false;

        $out = array();
        foreach( $versions as $row ) {
            $info = ModuleInfo::get_module_info($row['name']);
            if( version_compare($row['version'],$info['version']) > 0 ) {
                $out[$row['name']] = $row;
            }
        }
        return $out;
    }

    /**
     * @return mixed array | false
     */
    public static function get_upgrade_module_info($module_name)
    {
        $versions = self::get_allmoduleversions();
        if( !is_array($versions) ) return false;
        if( count($versions) == 2 && $versions[0] == false ) return false;

        foreach( $versions as $row ) {
            if( $row['name'] == $module_name ) return $row;
        }
        return false;
    }

    /**
     * @return string maybe empty
     */
    private static function get_repourl($mod = null)
    {
        static $url = null;
        if( $url == null ) {
            if( !$mod ) { $mod = cms_utils::get_module('ModuleManager'); }
            $url = $mod->GetPreference('module_repository');
        }
        return $url;
    }
} // class
?>
