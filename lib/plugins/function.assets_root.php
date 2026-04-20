<?php
#Plugin handler: assets_root
#(c) 2025 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

function smarty_function_assets_root($params, $smarty)
{
	$config = CmsApp::get_instance()->GetConfig();
	$ap = $config['assets_path'];

	if( isset($params['rel']) ) {
		$relative = cms_to_bool($params['rel']);
	} else {
		$relative = false;
	}

	if( isset($params['path']) ) {
		$aspath = cms_to_bool($params['path']);
	} else {
		$aspath = false;
	}

	if ($aspath) {
		$l = strlen(CMS_ROOT_PATH) + 1; //also omit separator
		$out = ($relative) ? substr($ap, $l) : $ap;
	} elseif ($relative) {
		$out = str_replace([CMS_ROOT_PATH, '\\'], ['', '/'], $ap);
		$out = ltrim($out, ' /');
	} else {
		$out = str_replace([CMS_ROOT_PATH, '\\'], [CMS_ROOT_URL, '/'], $ap);
	}

	if( isset($params['assign']) ) {
		$smarty->assign(trim($params['assign']),$out);
		return '';
	}
	return $out;
}

function smarty_cms_about_function_assets_root()
{
?>
	<p>Author: CMSMS devteam</p>
	<p>Change History:</p>
	<ul>
		<li>None</li>
	</ul>
<?php
}
