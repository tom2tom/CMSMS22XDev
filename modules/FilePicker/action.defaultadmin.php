<?php
# BEGIN_LICENSE
#-------------------------------------------------------------------------
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

//use FilePicker\ProfileDAO;
if( !defined('CMS_VERSION') ) exit;
if( !$this->VisibleToAdminUser() ) return;

$profiles = $this->_dao->loadAll();
$smarty->assign('dflt_profile_id',$this->_dao->getDefaultProfileId());
$smarty->assign('profiles',$profiles);
echo $this->ProcessTemplate('defaultadmin.tpl');
