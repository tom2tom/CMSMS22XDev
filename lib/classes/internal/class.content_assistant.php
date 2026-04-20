<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: content_assistant
# (c) 2010 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# A content managment assister class.
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

/**
 * @package CMS
 */

/**
 * A simple class for assisting with content management
 *
 * @package CMS
 * @internal
 * @author Robert Campbell
 * @since 1.9
 */
class content_assistant
{
  /**
   * A utility function to test if we are allowed to auto create url paths
   *
   * @return bool
   */
  public static function auto_create_url()
  {
    return cms_siteprefs::get('content_autocreate_urls',0);
  }


  /**
   * A utility function to test if the supplied urlpath is acceptable
   * for the supplied content id. Checks content and uniqueness.
   * @see also cms_utils::validate_url()
   *
   * @param string The url path to test
   * @param mixed int | numeric string | '' $content_id Default ''
   * @return bool
   */
  public static function is_valid_url($url,$content_id = '')
  {
    if( $url[0] == '/') return FALSE; // trailing '/' ok

    // check for invalid char(s).
    if( $url != cms_utils::cleanUrlPath($url) ) return FALSE;

    cms_route_manager::load_routes();
    // check for duplicate. See also $contentops->CheckAliasUsed($url,(int)$content_id)
    $route = cms_route_manager::find_match($url,TRUE);
    if( !$route ) return TRUE;
    if( $route->is_content() ) {
      if( $content_id == '' || $content_id == $route->get_content() ) return TRUE;
    }
    return FALSE;
  }
} // end of class

?>
