<?php
#CMS Made Simple admin console script
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANthe TY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$

$CMS_ADMIN_PAGE=1;
require_once("../lib/include.php");
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
check_login();

$realm = 'admin';
$key = 'help';
$out = 'NO HELP KEY SPECIFIED';
if( isset($_GET['key']) ) $key = cms_htmlentities(trim($_GET['key']));
if( strstr($key,'__') !== FALSE ) {
  list($realm,$key) = explode('__',$key,2);
}
if( strtolower($realm) == 'core' ) $realm = 'admin';
$out = CmsLangOperations::lang_from_realm($realm,$key);

echo $out;
exit;

#
# EOF
#
?>
