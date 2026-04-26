<?php
#CMS Made Simple class CmsAdminUtils
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT withOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, read the license online at
#https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

/**
 * A class of static utility-methods for assisting with admin requests
 * @since 2.0
 * @final
 * @see also cms_admin_utils class having fewer methods and more usage
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 */
final class CmsAdminUtils
{
   /**
    * @ignore
    */
    private function __construct() {}

    /**
     * A regular expression to use when testing if an item has a valid name.
     */
    const ITEMNAME_REGEX = '#^[a-zA-Z0-9_~\x80-\xff][a-zA-Z0-9_ /+\-,.~\x80-\xff]*$#';
    //TODO exclude non-letters & non-numbers >= \x80 i.e.
    //#^[a-zA-Z0-9_~\x8c\x8e\x9c\x9e\x9f\xc0-\xd6\xd8-\xf6\xf8-\xff\pL\p{Nd}\p{Po}]
    //[a-zA-Z0-9_ /+\-,.~\x8c\x8e\x9c\x9e\x9f\xc0-\xd6\xd8-\xf6\xf8-\xff\pL\p{Nd}\p{Po}]*$#u

    /**
     * Test if a string is suitable for use as a name of an item in CMSMS.
     * For use by various modules and the core.
     * The name must begin with an alphanumeric character (but some extended characters are allowed).  And must be followed by the same alphanumeric characters
     * note the name is not necessarily guaranteed to be usable in smarty without backticks.
     *
     * @param string $str The string to test
     * @return bool|string FALSE on error or the validated string.
     */
    public static function is_valid_itemname($str)
    {
        if( !is_string($str) ) return FALSE;
        $t_str = trim($str);
        if( !$t_str ) return FALSE;
        if( !preg_match(self::ITEMNAME_REGEX,$t_str) ) return FALSE;
        return $str;
    }

    /**
     * Convert an admin request URL to a generic form that is suitable for saving to a database.
     * This is useful for things like bookmarks and homepages.
     * Note it only works for admin urls with user key of the current admin user
     * @depecated since 2.2.19 admin urls can bypass the permissions mechanism,
     * and should be supported only with permission checking akin that used
     * for admin-menu generation
     *
     * @param string $in_url The input URL that has the session key in it.
     * @return string A URL that is converted to a generic form.
     */
    public static function get_generic_url($in_url)
    {
        if( !defined('CMS_USER_KEY') ) throw new \LogicException('This method can only be called for admin requests');//TODO defined every request, when include.php is processed
        if( !isset($_SESSION[CMS_USER_KEY]) || !$_SESSION[CMS_USER_KEY] ) throw new \LogicException('This method can only be called for admin requests');

        $in_p = CMS_SECURE_PARAM_NAME. '=' . $_SESSION[CMS_USER_KEY];
        $out_p = '[SECURITYTAG]';
        $out = str_replace($in_p,$out_p,$in_url);
        $config = \cms_config::get_instance();
        if( startswith($out,$config['admin_url'] . '/') ) {
            $out = str_replace($config['admin_url'] . '/','',$out);
        }
        return $out;
    }

    /**
     * Convert a generic URL into something that is suitable for this user's session.
     * @depecated since 2.2.19 admin urls can bypass the permissions mechanism,
     * and should be supported only with permission checking akin that used
     * for admin-menu generation
     *
     * @param string $in_url The generic url.  usually retrieved from a preference or from the database
     * @return string A URL that has a session key in it.
     */
    public static function get_session_url($in_url)
    {
        if( !defined('CMS_USER_KEY') ) throw new \LogicException('This method can only be called for admin requests'); //TODO defined every request, when include.php is processed
        if( !isset($_SESSION[CMS_USER_KEY]) || !$_SESSION[CMS_USER_KEY] ) throw new \LogicException('This method can only be called for admin requests');

        $in_p = '[SECURITYTAG]';
        $out_p = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
        return str_replace($in_p,$out_p,$in_url);
    }

    /**
     * Get the latest available CMSMS version.
     * This method does a remote request to the version-check URL, at most once per day.
     *
     * @return string
     */
    public static function fetch_latest_cmsms_ver()
    {
        $remote_ver = cms_siteprefs::get('last_remotever');
        $last_fetch = (int) cms_siteprefs::get('last_remotever_check');
        if( $last_fetch < (time() - 24 * 3600) ) {
            $req = new cms_http_request(['method' => 'POST','timeout' => 3]);
            $req->send(CMS_DEFAULT_VERSIONCHECK_URL);
            if( $req->getStatus() == 200 ) {
                $remote_ver = trim($req->getResult());
                if( strpos($remote_ver,':') !== FALSE ) {
                    list($tmp,$remote_ver) = explode(':',$remote_ver,2);
                    $remote_ver = trim($remote_ver);
                }
                cms_siteprefs::set('last_remotever',$remote_ver);
                cms_siteprefs::set('last_remotever_check',time());
            }
        }
        return $remote_ver;
    }

    /**
     * Test if the current site could be upgraded (a later version of CMSMS is available)
     * Unused across the CMSMS-core
     *
     * @return bool
     */
    public static function site_needs_updating()
    {
        $remote_ver = self::fetch_latest_cmsms_ver();
        return $remote_ver && cmsversion_compare(CMS_VERSION,$remote_ver) < 0;
    }
}
?>
