<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# CMSMS module ModuleManager class cached_http_request
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
use cms_siteprefs;
use cms_utils;
use Exception;
use const PUBLIC_CACHE_LOCATION;
use function audit;
use function cmsms;

/**
 * This class extends core class cms_http_request.
 * The result from executing a request via that class is cached, and
 * subsequent identical requests use that cached result instead of doing
 * a fresh request to the target.
 * @since 2.1.12 Formerly modmgr_cached_request class
 */
final class cached_http_request extends cms_http_request
{
  private $signature;
  private $age; // cache-file timeout (minutes)

  public function __construct($params = [])
  {
    if( $params ) {
      if( isset($params['age']) ) {
        $this->age = max(1,(int)$params['age']);
        unset($params['age']);
      }
    }
    parent::__construct($params);
  }

  /**
   * @since 2.1.12 formerly execute()
   * @param string $target Default ''
   * @param array $data default []
   * @return void
   */
  public function send($target = '',$data = [])
  {
    // build a signature
    $this->signature = md5(serialize([$target,$data]));
    $fn = $this->getCacheFile();
    if( !$fn ) return '';

    if( empty($this->age) ) {
      $age = cms_siteprefs::get('browser_cache_expiry',60);
    }
    else {
      $age = $this->age;
    }
    $age = max(1,(int)$age);
    $atime = time() - ($age * 60);
    $mod = cms_utils::get_module('ModuleManager');
    $config = cmsms()->GetConfig();
    // check for the cached file
    if( (!file_exists($fn) || filemtime($fn) <= $atime) ||
        (!empty($config['developer_mode']) && $mod->GetPreference('disable_caching',false)) ) {
      $this->setMethod('POST');
      parent::send($target,$data);

      if( file_exists($fn) ) @unlink($fn);
      if( $this->status == 200 ) {
        // record a cache file
        $fh = fopen($fn,'wb');
        fwrite($fh,serialize(array($this->status,$this->result)));
        fclose($fh);
      } else {
        audit('',$mod->GetName(),'Request to module repository resulted in status '.$this->status);
      }
    }
    else {
      // get cached request-data
      $data = unserialize(file_get_contents($fn),['allowed_classes'=>[]]);
      if( $data ) {
        $this->status = $data[0];
        $this->result = $data[1];
      }
      else {
        throw new Exception('Failed to retrieve cached request-data');
      }
    }
    return $this->result;
  }

  public function setTimeout($seconds)
  {
    $this->timeout = max(1,min(1000,(int)$seconds));
  }

  public function setAge($minutes)
  {
    $this->age = max(1,(int)$minutes);
  }

  public function clearCache()
  {
    $fn = $this->getCacheFile();
    if( $fn && is_file($fn) ) { @unlink($fn); }
  }

  private function getCacheFile()
  {
    if( $this->signature ) {
      return PUBLIC_CACHE_LOCATION.DIRECTORY_SEPARATOR.'modmgr_'.$this->signature.'.dat';
    }
    return '';
  }
}

?>
