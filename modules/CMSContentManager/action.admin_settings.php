<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module CMSContentManager action
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#-------------------------------------------------------------------------
#
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
#
#-------------------------------------------------------------------------
#END_LICENSE
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

$modname = $this->GetName();
$tpl = $smarty->CreateTemplate("module_file_tpl:$modname;admin_settings.tpl", null, $modname, $smarty);

require __DIR__.DIRECTORY_SEPARATOR.'function.admin_general_tab.php';
require __DIR__.DIRECTORY_SEPARATOR.'function.admin_listsettings_tab.php';
require __DIR__.DIRECTORY_SEPARATOR.'function.admin_pagedefaults_tab.php';

if( empty($seetab) ) {
    $seetab = (!empty($params['__activetab'])) ? $params['__activetab'] : '';
}
$tpl->assign('tab', $seetab);
$tpl->assign('endform', $this->CreateFormEnd());

$tpl->display();
