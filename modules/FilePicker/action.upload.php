<?php
# BEGIN_LICENSE
# #-------------------------------------------------------------------------
# Module FilePicker action
# (c) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#-------------------------------------------------------------------------
# This file is part of FilePicker
# FilePicker is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# FilePicker is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#-------------------------------------------------------------------------
# END_LICENSE

if( !defined('CMS_VERSION') ) exit;

# just for tests purposes
#this will be handled differently
$fn = cms_join_path($this->GetModulePath(), 'lib', 'class.UploadHandler.php');
require_once($fn);

$UploadHandler = new \FilePicker\UploadHandler();

header('Pragma: no-cache');
header('Cache-Control: private, no-cache');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

switch ($_SERVER['REQUEST_METHOD'])
{
	case 'OPTIONS':
        break;
	case 'HEAD':
	case 'GET':
        $UploadHandler->get();
        break;
	case 'POST':
        $UploadHandler->post();
        break;
	case 'DELETE':
        $UploadHandler->delete();
        break;
	default:
        header('HTTP/1.1 405 Method Not Allowed');
}

exit;

#
# EOF
#
?>
