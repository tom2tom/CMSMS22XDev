<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: CMSMS\Async\RegularTask
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program. If not, read the license online at:
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
#END_LICENSE
// deprecated since 2.2.23F2 instead use RegularJob class

namespace CMSMS\Async;

require_once __DIR__.DIRECTORY_SEPARATOR.'class.RegularJob.php';
class_alias('CMSMS\Async\RegularJob','CMSMS\Async\RegularTask',false);
