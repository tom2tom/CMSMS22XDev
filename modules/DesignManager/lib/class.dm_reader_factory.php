<?php
#-------------------------------------------------------------------------
# Module DesignManager class dm_reader_factory
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
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

final class dm_reader_factory
{
  private function __construct() {}

  /**
   * @param string $xmlfile filepath
   * @return mixed dm_theme_reader object or dm_design_reader object
   * @throws CmsFileSystemException
   * @throws CmsException
   */
  public static function get_reader($xmlfile)
  {
    $mod = cms_utils::get_module('DesignManager');
    if( !is_readable($xmlfile) ) throw new CmsFileSystemException($mod->Lang('error_filenotfound',$xmlfile));
    $fh = fopen($xmlfile,'rb');
    if( $fh ) {
        $str = fread($fh,200);
        fclose($fh);
        if( !$str || strpos($str,'<!DOCTYPE') === FALSE ) throw new CmsException($mod->Lang('error_readxml'));
    } else {
        throw new CmsException($mod->Lang('error_fileopen',$xmlfile));
    }
    // get the first element
    $x = '<!ELEMENT ';
    $p = strpos($str,$x);
    if( $p === FALSE ) throw new CmsException($mod->Lang('error_readxml'));
    $str = substr($str,$p+strlen($x));
    $p = strpos($str,' ');
    if( $p === FALSE ) throw new CmsException($mod->Lang('error_readxml'));  // highly unlikely.
    $word = substr($str,0,$p);

    switch( $word ) {
    case 'theme':
      return new dm_theme_reader($xmlfile);
    case 'design':
      return new dm_design_reader($xmlfile);
    }
    throw new CmsException($mod->Lang('error_upload_filetype'));
  }
} // class
