<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module ModuleManager action
# (c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

if( isset($params['modulehelp']) ) {
    // this is done before permissions checks
    $params['mod'] = $params['modulehelp'];
    unset($params['modulehelp']);
    require __DIR__.DIRECTORY_SEPARATOR.'action.local_help.php';
    return;
}

if( !$this->VisibleToAdminUser() ) exit;
$tmp = ModuleOperations::get_instance()->GetQueueResults();
if( $tmp && is_array($tmp) ) {
    $tmp2 = array();
    foreach( $tmp as $key => $data ) {
        $msg = $data[1];
        if( !$msg ) {
            $msg = $this->Lang('unknown');
            if( $data[0] ) $msg = $this->Lang('success');
        }
        $tmp2[] = $key.': '.$msg;
    }
    $this->ShowMessage($tmp2);
}

$connection_ok = modmgr_utils::is_connection_ok();

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;defaultadmin.tpl",null,$modname,$smarty);

// this is a bit ugly
modmgr_utils::get_images($tpl);

$newversions = [];
if( $connection_ok ) {
    try {
        $newversions = modulerep_client::get_newmoduleversions(); // note downstream module-processing might clobber assigned $mod value!
    }
    catch( Exception $e ) {
        $this->ShowErrors($e->GetMessage());
    }
}
else {
    $this->ShowErrors($this->Lang('error_request_problem'));
}

$pmod = $this->CheckPermission('Modify Modules');
$pset = $this->CheckPermission('Modify Site Preferences');

$tpl->assign('connected', $connection_ok);
$tpl->assign('pmod', $pmod);
$tpl->assign('pset', $pset);
$tpl->assign('mod', $this); // re-assign, in case the normal default assignment was clobbered by another module, downstream
$tpl->parent->assign('mod', $this); // also in the global-Smarty vars

$num = ( is_array($newversions) ) ? count($newversions) : 0;
$label = $num.' '.$this->Lang('tab_newversions');
$tpl->assign('newcount', $label);

if( $pmod ) {
    require __DIR__.DIRECTORY_SEPARATOR.'function.admin_installed.php';
    if( $connection_ok ) {
        require __DIR__.DIRECTORY_SEPARATOR.'function.newversionstab.php';
        require __DIR__.DIRECTORY_SEPARATOR.'function.search.php';
        require __DIR__.DIRECTORY_SEPARATOR.'function.admin_modules_tab.php';
    }
}
if( $pset ) {
    require __DIR__.DIRECTORY_SEPARATOR.'function.admin_prefs_tab.php';
}

if( empty($seetab) ) {
    $seetab = (!empty($params['__activetab'])) ? $params['__activetab'] : '';
}
$tpl->assign('tab', $seetab);
$tpl->assign('endform', $this->CreateFormEnd());

$tpl->display();
