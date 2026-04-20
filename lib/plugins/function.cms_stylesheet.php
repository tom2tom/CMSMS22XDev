<?php
#Plugin handler: cms_stylesheet
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT withOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

// $template is a Smarty_Internal_Template
function smarty_function_cms_stylesheet($params, $template)
{
	global $CMS_LOGIN_PAGE;
	global $CMS_STYLESHEET;

	//--------------------------------------------
	// Trivial Exclusion
	//--------------------------------------------

	if( isset($CMS_LOGIN_PAGE) ) return '';

	//--------------------------------------------
	// Initials
	//--------------------------------------------

	$gCms = CmsApp::get_instance();
	$config = $gCms->GetConfig();

	$CMS_STYLESHEET = 1;
	$name = ''; // might become array
	$id = -1; // ditto
	$design_id = -1;
	$use_https = false;
	$cache_dir = $config['css_path'];
	$stylesheet = '';
	$combine_stylesheets = true;
	$minimize = false; //since 2.2.22F2
	$fnsuffix = '';
	$trimbackground = false;
	$root_url = $config['css_url'];
	$auto_https = true;
	//TODO support $params['templatetype'] related to a theme

	//--------------------------------------------
	// Read parameters
	//--------------------------------------------

	try {
		if( !empty($params['name']) ) {
			$name = cms_stylesheet_checkProparray($params['name']);
		} elseif (!empty($params['id']) ) {
			$id = cms_stylesheet_checkProparray($params['id'], 0);
		} elseif (!empty($params['designid']) ) {
			$design_id = (int)$params['designid']; // only 1 design
		} else {
			$content_obj = $gCms->get_content_object();
			if( !is_object($content_obj) ) return '';
			$design_id = (int)$content_obj->GetPropertyValue('design_id');
			$use_https = (bool)$content_obj->Secure();
		}
		if( !$name && is_numeric($id) && $id < 1 && $design_id < 1 ) throw new RuntimeException('Cannot identify stylesheet(s) for page');

		// @todo: change this stuff to just use // instead of protocol-specific URL.
		if( isset($params['auto_https']) && $params['auto_https'] == 0 ) $auto_https = false;
		if( $auto_https && $gCms->is_https_request() ) $use_https = true;
		elseif( isset($params['https']) ) $use_https = cms_to_bool($params['https']);
		if( $use_https && isset($config['ssl_url']) ) $root_url = $config['ssl_css_url']; //deprecated

		if( isset($params['nocombine']) && !is_array($name) && !is_array($id) ) $combine_stylesheets = !cms_to_bool($params['nocombine']);

		if( isset($params['stripbackground']) ) {
			$trimbackground = cms_to_bool($params['stripbackground']);
			$fnsuffix = '_e_';
		}

		if( isset($params['min']) ) {
			$minimize = cms_to_bool($params['min']);
		} elseif( isset($params['minify']) ) {
			$minimize = cms_to_bool($params['minify']);
		} else {
			$minimize = !constant('CMS_DEBUG');
		}

		//--------------------------------------------
		// Build query
		//--------------------------------------------

		$query = null; // no object
		if( $name ) {
			// stylesheet(s) by name
			$query = new CmsLayoutStylesheetQuery([ 'fullname'=>$name ]);
		} elseif( is_array($id) || $id > 0 ) {
			// stylesheet(s) by id
			$query = new CmsLayoutStylesheetQuery([ 'id'=>$id ]);
		} elseif( $design_id > 0 ) {
			// stylesheet by design id
			$query = new CmsLayoutStylesheetQuery([ 'design'=>$design_id ]);
		}
		if( !$query ) throw new RuntimeException('Problem: Could not build a stylesheet query with the provided data');

		//--------------------------------------------
		// Execute
		//--------------------------------------------

		$nrows = $query->TotalMatches();
		if( $nrows == 0 ) {
			throw new RuntimeException('No stylesheet matched the criterion specified');
		}
		$res = $query->GetMatches();
		$algo = (PHP_VERSION_ID >= 80100 && in_array('xxh64',hash_algos())) ? 'xxh64' : 'fnv164';

		// we have some output, and the stylesheet objects have already been loaded.

		// combine stylesheets
		if( $combine_stylesheets ) {
			// group queries & types
			$all_media = array();
			$all_timestamps = array();
			foreach( $res as $one ) {
				$mq = $one->get_media_query();
				$mt = implode(',',$one->get_media_types());
				if( !empty($mq) ) {
					$key = hash($algo,$mq);
					$all_media[$key][] = $one;
					$all_timestamps[$key][] = $one->get_modified();
				} elseif( !$mt ) {
					$all_media['all'][] = $one;
					$all_timestamps['all'][] = $one->get_modified();
				} else {
					$key = hash($algo,$mt);
					$all_media[$key][] = $one;
					$all_timestamps[$key][] = $one->get_modified();
				}
			}

			// media parameter...
			if( isset($params['media']) && strtolower($params['media']) != 'all' ) {
				// media parameter is deprecated.

				// combine all matches into one stylesheet
				$filename = 'stylesheet_combined_'.hash($algo,$design_id.$use_https.serialize($params).serialize($all_timestamps).$fnsuffix).'.css';
				$fn = cms_join_path($cache_dir,$filename);

				if( !file_exists($fn) ) {
					$list = array();
					foreach( $res as $one ) {
						if( in_array($params['media'],$one->get_media_types()) ) $list[] = $one->get_name();
					}
					cms_stylesheet_writeCache($fn, $list, $trimbackground, $minimize, $template);
				}

				cms_stylesheet_toString($filename, $params['media'], '', $root_url, $stylesheet, $params);
			} else {
				foreach( $all_media as $hash=>$onemedia ) {
					// combine all matches into one stylesheet.
					$filename = 'stylesheet_combined_'.hash($algo,$design_id.$use_https.serialize($params).serialize($all_timestamps[$hash]).$fnsuffix).'.css';
					$fn = cms_join_path($cache_dir,$filename);

					// get media_type and media_query
					$media_query = $onemedia[0]->get_media_query();
					$media_type = implode(',',$onemedia[0]->get_media_types());

					if( !is_file($fn) ) {
						$list = array();

						foreach( $onemedia as $one ) {
							$list[] = $one->get_name();
						}
						cms_stylesheet_writeCache($fn, $list, $trimbackground, $minimize, $template);
					}

					cms_stylesheet_toString($filename, $media_query, $media_type, $root_url, $stylesheet, $params);
				}
			}
		} else { // do not combine stylesheets
			foreach( $res as $one ) {
				if (isset($params['media'])) {
					if( !in_array($params['media'],$one->get_media_types()) ) continue;
					$media_query = '';
					$media_type = $params['media']; // possibly deprecated
				} else {
					$media_query = $one->get_media_query();
					$media_type  = implode(',',$one->get_media_types()); // possibly includes deprecated
				}

				$filename = 'stylesheet_'.hash($algo,'single'.$one->get_id().$use_https.$one->get_modified().$fnsuffix).'.css';
				$fn = cms_join_path($cache_dir,$filename);

				if (!file_exists($fn) ) cms_stylesheet_writeCache($fn, $one->get_name(), $trimbackground, $minimize, $template);

				cms_stylesheet_toString($filename, $media_query, $media_type, $root_url, $stylesheet, $params);
			}
		}

		//--------------------------------------------
		// Cleanup & output
		//--------------------------------------------

		if( strlen($stylesheet) ) {
			$stylesheet = preg_replace("/\{\/?php\}/", "", $stylesheet);

			// Remove last comma at the end when $params['nolinks'] is set
			if( isset($params['nolinks']) && cms_to_bool($params['nolinks']) && endswith($stylesheet,',') ) {
				$stylesheet = substr($stylesheet,0,-1);
			}
		}
	} catch( Exception $e ) {
		audit('','Plugin:cms_stylesheet',$e->GetMessage());
		$stylesheet = '<!-- cms_stylesheet error: '.$e->GetMessage().' -->';
	}

	// Notify core that we are no longer at stylesheet, pretty ugly way to do this. -Stikki-
	$CMS_STYLESHEET = 0;
	unset($CMS_STYLESHEET);
	unset($GLOBALS['CMS_STYLESHEET']);

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $stylesheet);
		return '';
	}

	return $stylesheet;
} // main

/**********************************************************
	Support functions
**********************************************************/

function cms_stylesheet_writeCache($filename, $list, $trimbackground, $minimize, $tpl)
{
	$_contents = '';
	if( !is_array($list) ) $list = array($list);
	$nlast = count($list);
	$idx = 0;

	// Smarty processing
	$ol = $tpl->smarty->left_delimiter;
	$or = $tpl->smarty->right_delimiter;
	$tpl->smarty->left_delimiter = '[[';
	$tpl->smarty->right_delimiter = ']]';

	try {
		foreach( $list as $name ) {
			// force the stylesheet to compile because of smarty bug: https://github.com/smarty-php/smarty/issues/72
			$tmp = $tpl->smarty->force_compile;
			$tpl->smarty->force_compile = 1;
			$_contents .= $tpl->fetch('cms_stylesheet:'.$name);
			if( ++$idx < $nlast ) {
				$_contents .= "\n"; // TODO preserve separator during minimisation
			}
			$tpl->smarty->force_compile = $tmp;
		}
	}
	catch (SmartyException $e) {
		// why not just re-throw the exception as it may have a smarty error in it ?
		$tpl->smarty->left_delimiter = $ol;
		$tpl->smarty->right_delimiter = $or;
		debug_to_log('Error Processing Stylesheet');
		debug_to_log($e->GetMessage());
		audit('','Plugin:cms_stylesheet', 'Smarty compilation failed, an error in the template?');
		return '';
	}

	$tpl->smarty->left_delimiter = $ol;
	$tpl->smarty->right_delimiter = $or;

	if( $trimbackground ) {
		// Replace/remove background properties
		// Note $_contents might have been strip'd by Smarty, hence a
		// single line with less whitespace
		$_contents = preg_replace(
		['/background *:.*?(#[0-9A-Fa-f]{3,8}|rgba?|hsla?).*?([;}]|$)(.*)/',
		 '/background\-color *:([^;}]*?)([;}]|$)(.*)/',
		 '/background\-\w+ *:.*?([;}]|$)(.*)/',
		 '/;\s*;/'],
		['background-color:transparent$2$3',
		 'background-color:transparent$2$3',
		 '$1$2',
		 ';'],
		$_contents);
	}

	CMSMS\HookManager::do_hook('Core::StylesheetPostRender', [ 'content' => &$_contents ]);

	if( $minimize ) {
		// Compress (quite a bit)
		$str = preg_replace(
			['~^\s+~', '~\s+$~', '~\s+~', '~/\*[^!](\*(?!\/)|[^*])*\*/\s*~'],
			[''      , ''      , ' '    , ''],
			$_contents);
		$str = strtr($str, ['\r' => '', '\n' => '']);
		$_contents = str_replace(
			['  ', ': ', ', ', '{ ', '; ', '( ', '} ', ' :', ' {', '; }', ';}', ' }', ' )', '*/'  , '/*!'],
			[' ' , ':' , ',' , '{' , ';' , '(' , '}' , ':' , '{' , '}'  , '}' , '}' , ')' , "*/\n", "\n/*!"],
			$str);
	}

	// Write file
	$fh = fopen($filename,'w');
	fwrite($fh, $_contents);
	fclose($fh);
} // writeCache

function cms_stylesheet_toString($filename, $media_query = '', $media_type = '', $root_url = '', &$stylesheet = '', &$params = [])
{
	if( !endswith($root_url,'/') ) $root_url .= '/';
	if( isset($params['nolinks']) ) {
		$stylesheet .= $root_url.$filename.',';
	} elseif( $media_query ) {
		$stylesheet .= '<link rel="stylesheet" href="'.$root_url.$filename.'" media="'.$media_query.'">'."\n";
	} elseif( $media_type ) {
		$stylesheet .= '<link rel="stylesheet" href="'.$root_url.$filename.'" media="'.$media_type.'">'."\n";
	} else {
		$stylesheet .= '<link rel="stylesheet" href="'.$root_url.$filename.'">'."\n";
	}
}

function cms_stylesheet_checkProparray($val, $type='')
{
	$retas = gettype($type);
	if( is_string($val) ) {
		$val = trim($val, ' "\'');
		if( strpos($val, ',') !== false ) {
			$val = explode(',', $val);
		}
	}
	if( is_array($val) ) {
		foreach( $val as $i => &$one ) {
			switch( $retas ) {
				case 'integer':
				case 'double':
					$one = (int)$one;
					if( $one == 0 ) {
						unset($val[$i]);
					}
					break;
				case 'boolean':
					$one = cms_to_bool($one);
					break;
//				case 'string':
				default:
					$one = trim($one, ' "\'');
					if( !$one ) {
						unset($val[$i]);
					}
			}
		}
		unset($one);
		if( $val && count($val) == 1 ) {
			$val = reset($val);
		}
		return $val;
	}
	switch( $retas ) {
		case 'integer':
		case 'double':
			return (int)$val;
		case 'boolean':
			return cms_to_bool($val);
		default:
			return $val; // already trim()'d
	}
}

/**********************************************************
	Help function
**********************************************************/

function smarty_cms_about_function_cms_stylesheet()
{
?>
	<p>Author: jeff&lt;jeff@ajprogramming.com&gt;</p>

	<p>Change History:</p>
	<ul>
		<li>Rework from {stylesheet}</li>
		<li>(Stikki and Robert Campbell) Code cleanup, Added grouping by media type / media query, Fixed cache issues</li>
		<li>Correct background-strip regexes</li>
		<li>Support optional 'min' (or alias 'minify') parameter</li>
		<li>Support 'id' and 'name' parameters as array or comma-separated string</li>
	</ul>
<?php
} // about
?>
