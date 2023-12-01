<?php
#FileManager module action
#(c) 2006-8 Morten Poulsen <morten@poulsen.org>
#(c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

use FileManager\UploadHandler;

if (!function_exists("cmsms")) exit;
if (!$this->CheckPermission("Modify Files") && !$this->AdvancedAccessAllowed()) exit;

require_once cms_join_path(__DIR__, 'lib', 'class.UploadHandler.php');

$options = array('param_name'=>$id.'files');
$upload_handler = new UploadHandler($options);

header('Pragma: no-cache');
header('Cache-Control: private, no-cache');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

switch ($_SERVER['REQUEST_METHOD']) {
  case 'OPTIONS':
    break;
  case 'HEAD':
  case 'GET':
    $upload_handler->get();
    break;
  case 'POST':
    $upload_handler->post();
    break;
  case 'DELETE':
    $upload_handler->delete();
    break;
  default:
    header('HTTP/1.1 405 Method Not Allowed');
}

exit;

#
# EOF
#
?>
