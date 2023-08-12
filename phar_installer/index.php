<?php
#---------------------------------------------------------------------------
# CMS Made Simple - Power for the professional, Simplicity for the end user.
# (c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#---------------------------------------------------------------------------
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
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#---------------------------------------------------------------------------

//
// initialization
//
namespace cms_autoinstaller;

try {
    function _detect_bad_ioncube()
    {
        if( extension_loaded('ionCube Loader') ) {
            if( function_exists('ioncube_loader_version') ) {
                $ver = ioncube_loader_version();
                if( version_compare($ver,'4.1') < 0 ) throw new \Exception('An old version of ioncube loader was detected.  Older versions are known to have problems with PHAR files. Sorry, but we cannot continue.');
            }
        }
    }

    // some basic system wide pre-requisites
    if(php_sapi_name() == "cli") throw new \Exception("We are sorry but:\n\nCLI based execution of this script is not supported.\nPlease browse to this script with a compatible browser");
    if( version_compare(phpversion(),'7.1.0') < 0 ) throw new \Exception('We are sorry, but this installer requires at least PHP 7.1.0'); //Smarty4 at least needs this
    _detect_bad_ioncube();

    // clear opcache before disabling it
    if( function_exists( 'opcache_get_status' ) && opcache_get_status() ) opcache_reset();
    // disable some stuff.
    @ini_set('opcache.enable',0); // disable zend opcode caching.
    @ini_set('apc.enabled',0); // disable apc opcode caching (for later versions of APC)
    @ini_set('xcache.cacher',0); // disable xcache opcode caching

    require_once __DIR__.'/app/class.cms_install.php';
    $app = new cms_install();
    $app->run();
}
catch( \Exception $e ) {
    // this handles fatal, serious errors.
    // cannot use stylesheets, scripts, or images here, as the problem may be a phar based problem
    $out = <<<EOT
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>CMS Made Simple Installer : Fatal Error</title>
  </head>
  <body>
    <div style="border-radius: 3px; max-width: 85%; margin: 10% auto; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; padding: 15px;">
      <h1>Fatal Error</h1>
      <p>[message]</p>
    </div>
  </body>
</html>
EOT;
    echo str_replace('[message]',$e->GetMessage(),$out);
}

?>
