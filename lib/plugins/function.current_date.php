<?php
#Plugin handler: current_date
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

// this plugin is redundant, deprecated and should be removed.
// historically, this plugin has been specially handled
// (triggered by its name smarty_cms_function...)
// to ensure that it's never cached
function smarty_cms_function_current_date($params, $smarty)
{
	$format = '%b j, Y';
	if( isset($params['format']) && !empty($params['format']) ) $format = trim($params['format']);

	if( strpos($format, '%') !== false ) {
		require_once __DIR__.DIRECTORY_SEPARATOR.'modifier.localedate_format.php';
		$string = smarty_modifier_localedate_format(time(), $format);
	}
	else {
		$string = date($format, time());
	}

	if(isset($params['ucwords']) && $params['ucwords'] != '') $string = ucwords($string);

	$out = cms_htmlentities($string);
	if( isset($params['assign']) ) {
		$smarty->assign(trim($params['assign']),$out);
		return '';
	}
	return $out;
}

function smarty_cms_about_function_current_date() {
?>
	<p>Author: Ted Kulp&lt;ted@cmsmadesimple.org&gt;</p>
	<p>Version: 1.0</p>
	<p>
	Change History:<br/>
	None
	</p>
<?php
}
?>
