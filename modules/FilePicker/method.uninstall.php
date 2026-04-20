<?php
# BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module FilePicker uninstallation script
# (c) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
# END_LICENSE

use FilePicker\ProfileDAO;

$dict = NewDataDictionary($db);
$tbl = ProfileDAO::table_name();
$sqlarray = $dict->DropTableSQL($tbl);
$dict->ExecuteSQLArray($sqlarray);
$db->DropSequence("{$tbl}_seq");

$this->RemovePreference();
