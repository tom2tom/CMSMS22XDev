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
#along with this program. If not, read the license online at
#https://www.gnu.org/licenses/#LicenseURLs
#
#$Id$

$CMS_ADMIN_PAGE = 1;
$CMS_LOGIN_PAGE = 1;

require_once '../lib/include.php';
//require_once '../lib/classes/class.User.php';

$themeObject = cms_utils::get_theme_object();
$theme = $themeObject->themeName;

header("Content-Type: text/css; charset=" . get_encoding());
$fp = cms_join_path(__DIR__,'themes',$theme,'css','style.css');
if (file_exists($fp)) {
    echo file_get_contents($fp);
}
else {
    echo file_get_contents(__DIR__."/themes/OneEleven/css/style.css");
}

$fp = cms_join_path(__DIR__,'themes',$theme,'extcss','style.css');
if (file_exists($fp)) {
    @ob_start(); //WHATFOR buffering?
    echo file_get_contents($fp);
    $result = @ob_get_contents();
    @ob_end_clean();
    if( $result ) {
        echo $result;
    }
}
