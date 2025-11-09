<?php
#CMS Made Simple functions
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
#$Id$

/**
 * Methods for modules to do miscellaneous functions
 *
 * @since   1.0
 * @package CMS
 * @license GPL
 */

/**
 * @access private
 */
function cms_module_GetAbout($modinstance)
{
	$str = '';
	if (($val = $modinstance->GetAuthor())) {
		$str = '<br>'.lang('author').': ' . $val;
		if (($val = $modinstance->GetAuthorEmail())) $str .= ' &lt;' . $val . '&gt;';
	}
	if ($str) $str .= '<br><br>';
	$str .= lang('version').': ' .$modinstance->GetVersion();

	if (($val = $modinstance->GetChangeLog())) {
		$str .= '<br><br>'.lang('changehistory').':<br>' .$val;
	}
	return $str;
}

/**
 * @access private
 */
function cms_module_GetHelpPage($modinstance)
{
	$str = '';
	@ob_start();
	echo $modinstance->GetHelp();
	$str .= @ob_get_clean();
	$dependencies = $modinstance->GetDependencies();
	if ($dependencies) {
		$str .= "\n".'<h3>'.lang('dependencies').'</h3><ul>';
		foreach( $dependencies as $dep => $ver ) {
			$str .= "<li>$dep =&gt; $ver</li>";
		}
		$str .= '</ul>';
	}
	$parameters = $modinstance->GetParameters();
	if ($parameters) {
		usort($parameters, function($a, $b) {
			$ret = strcmp($a['name'], $b['name']);
			return ($ret != 0) ? $ret : strcmp($a['help'], $b['help']);
		});
		$done = [];
		$str .= "\n".'<h3>'.lang('frontendparameters').'</h3><ul>';
		foreach ($parameters as $oneparam) {
			if( isset($done[$oneparam['name']]) ) {
				continue; // ignore duplicates by 'name' property
			}
			$done[$oneparam['name']] = 1;
			$str .= '<li>';
			if ($oneparam['optional']) { $str .= '<em>(optional)</em> '; }
			$dflt = $oneparam['default'];
			if (!(is_numeric($dflt) ||
				strcasecmp($dflt, 'null') == 0 //CHECKME
// || strcasecmp($dflt, 'true') == 0 || strcasecmp($dflt, 'false') == 0 nicer but incompatible
				) ) {
				$dflt = "'$dflt'";
			}
			$help = (!empty($oneparam['help'])) ? ' - '.$oneparam['help'] : '';
			$str .= $oneparam['name'].'='.$dflt.$help.'</li>';
		}
		$str .= '</ul>';
	}
	return $str;
}

?>
