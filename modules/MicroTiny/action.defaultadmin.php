<?php
#Module MicroTiny action
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
if( !cmsms() ) exit;
if(!$this->VisibleToAdminUser() ) return;

$modname = $this->GetName();
$tpl = $smarty->CreateTemplate("module_file_tpl:$modname;defaultadmin.tpl", null, $modname, $smarty);

//require __DIR__.DIRECTORY_SEPARATOR.'function.admin_example.php'; nothing in there
require __DIR__.DIRECTORY_SEPARATOR.'function.admin_settings.php';

$tpl->assign('tab', ''); // no tab-selection

$tpl->display();
