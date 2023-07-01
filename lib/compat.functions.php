<?php
#CMS - CMS Made Simple
#(c)2004-2015 by Ted Kulp (wishy@users.sf.net)
#Visit our homepage at: http://www.cmsmadesimple.org
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
#
#$Id$

/**
 * Miscellaneous support functions
 *
 * @package CMS
 * @license GPL
 */
// Global
namespace
{
  if(!\function_exists('gzopen') && \function_exists('gzopen64')){
    /**
     * Wrapper for gzopen in case it does not exist.
     * Some installs of PHP (after PHP 5.3 use a different zlib library, and therefore gzopen is not defined.
     * This method works past that.
     *
     * @since 2.0
     * @ignore
     */
    function gzopen( $filename, $mode, $use_include_path = 0 ) {
      return gzopen64($filename, $mode, $use_include_path);
    }
  }
}

namespace CMSMS
{
  /**
   * strftime replacement using IntlDateFormatter if available
   * This provides a cross-platform alternative to PHP's deprecated strftime()
   * This is an alias for locale_ftime()
   * @since 2.2.17
   *
   * @param string $format    Date format
   * @param mixed  $timestamp int timestamp | string | null
   * @param mixed  $locale    string | null optional locale to use instead of
   *  the current default (CMSMS extension of PHP strftime API)
   *
   * @return string
   */
  function strftime($format, $timestamp = null, $locale = '')
  {
    return \locale_ftime($format, $timestamp, $locale);
  }
} // end namespace

#
# EOF
#
