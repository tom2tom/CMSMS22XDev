<?php
#Plugin handler: cms_module
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

function smarty_function_cms_module($params, $smarty)
{
	return cms_module_plugin($params, $smarty);
}

function smarty_cms_about_function_cms_module() {
?>
	<p>Author: Ted Kulp&lt;ted@cmsmadesimple.org&gt;</p>

	<p>Change History:</p>
	<ul>
		<li>None</li>
	</ul>
<?php
}
?>
