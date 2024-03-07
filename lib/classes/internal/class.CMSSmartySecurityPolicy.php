<?php
#CMS Made Simple class CMSSmartySecurityPolicy
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
 * Generic smarty security policy.
 *
 * @since   1.11
 * @package CMS
 * @internal
 * @ignore
 */
final class CMSSmartySecurityPolicy extends Smarty_Security
{
    public $smarty; // used by ancestor, prevent undeclared property
    //if Smarty5 is used, this property is N/A upstream
//  public $php_functions = ['count', 'empty', 'in_array', 'is_array', 'isset', 'sizeof', 'time'];

    public function __construct($smarty)
    {
        parent::__construct($smarty);
        /*
        CMSMS 2.0 to 2.2.18 did not apply policy to admin requests,
        although such a policy is defined here
        CMSMS 2.0 to 2.2.18 enabled overriding some of these policy
        settings by modules, possibly narrowing or expanding the scope
        of what Smarty allowed for frontend requests. For now at least,
        that capability remains. As of 2.2.19, also for admin requests.
        */
//Smarty 2,3 only $this->php_handling = Smarty::PHP_REMOVE;
        $this->secure_dir = null; // don't trust anywhere not explicitly whitelisted
        $this->php_modifiers = [];  // allow any php function BUT any such modifier deprecated since 4.3.0 TODO consider same as php_functions (breaker)
        $this->streams = null; // no usable streams
//Smarty 2,3 only $this->allow_php_tag = false;
        $gCms = CmsApp::get_instance();
        if( $gCms->is_frontend_request() ) {
            $this->allow_constants = false; // prohibit php const's (2.2.16) prob (2.2.17) >> true when permissive, false otherwise
            $config = $gCms->GetConfig();
            if( $config['permissive_smarty'] ) {
                // some permissive settings
                $this->static_classes = []; // allow all classes' static method-calls
                $this->php_functions = []; // allow any php functions
            }
            else {
                $this->static_classes = null; // no class static-method calls
                // allow most methods that do modification of data to be displayed.
                // e.g. string searches, array searches, string comparison, formatting, sorting, etc.
                $this->php_functions = [
                    'array_sum','array_combine','array_diff','array_flip','array_rand','array_reverse','array_search','asort',
                    'cms_html_entity_decode','cms_to_bool','count',
                    'date','debug_display',
                    'empty','endswith','explode',
                    'file_exists','function_exists',
                    'getimagesize',
                    'htmlspecialchars','htmlspecialchars_decode',
                    'implode','in_array','is_array','is_dir','is_email','is_file','is_object','is_string','isset',
                    'json_decode','json_encode',
                    'ksort',
                    'lang','locale_ftime', //lang for when admin realm enabled for frontend
                    'max','min',
                    'nl2br','number_format',
                    'print_r',
                    'rawurlencode',
                    'shuffle','sizeof','sort','startswith','str_replace','strcasecmp','strcmp','strftime','CMSMS\strftime','strlen','strpos','strtolower','strtotime','strtoupper','substr',
                    'time',
                    'trim','ltrim','rtrim', //since 2.2.17
                    'urlencode',
                    'var_dump'
                ];
            }
        }
        else {
            $this->allow_constants = true;
            $this->php_functions = []; // allow any php function
            $this->static_classes = []; // allow any static-method call (Smarty default)
        }
    }
} // end of class
