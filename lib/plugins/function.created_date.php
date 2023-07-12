<?php
#Plugin handler: created_date
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

function smarty_function_created_date($params, $smarty)
{
	$content_obj = CmsApp::get_instance()->get_content_object();

	if (is_object($content_obj) && $content_obj->GetCreationDate() > -1) {
		$time = $content_obj->GetCreationDate();

		$format = "%x %X";
		if(!empty($params['format'])) $format = $params['format'];
		if( strpos($format, '%') !== false ) {
			require_once __DIR__.DIRECTORY_SEPARATOR.'modifier.localedate_format.php';
			$str = smarty_modifier_localedate_format($time, $format);
		}
		else {
			$str = date($format, $time);
		}
		$str = cms_htmlentities($str);

		if( isset($params['assign']) ) {
			$smarty->assign(trim($params['assign']),$str);
			return '';
		}
		return $str;
	}
}

function smarty_cms_about_function_created_date() {
	?>
	<p>Author: Ted Kulp&lt;ted@cmsmadesimple.org&gt;</p>

	<p>Change History:</p>
	<ul>
		<li>None</li>
	</ul>
<?php
}
?>
