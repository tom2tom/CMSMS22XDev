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
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, read the license online at:
#https://www.gnu.org/licenses/#LicenseURLs

$CMS_ADMIN_PAGE = 1;
require_once("../lib/include.php");
check_login();

$realm = 'admin';
$key = 'help';
if( isset($_GET['key']) ) $key = cms_htmlentities(trim($_GET['key']));
if( strstr($key,'__') !== FALSE ) {
  list($realm,$key) = explode('__',$key,2);
  if( strtolower($realm) == 'core' ) $realm = 'admin';
}
$out = CmsLangOperations::lang_from_realm($realm,$key);

echo $out;
exit;

#
# EOF
#
?>
