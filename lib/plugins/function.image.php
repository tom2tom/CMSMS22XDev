<?php
#Plugin handler: image
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

function smarty_function_image($params, $template)
{
	if( !empty($params['src']) ) {
		$config = cms_config::get_instance();
		$relurl = strtr(trim($params['src'],' \\/'),'\\','/'); // possibly a subdir e.g. one of cms_siteprefs::get('content * _path')
		$text = '<img src="'.$config['image_uploads_url'].'/'.$relurl.'"';
		$fullpath = $config['image_uploads_path'].DIRECTORY_SEPARATOR.strtr($relurl,'\\/',DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);

		if( !empty($params['width']) ) {
			$text .= ' width="'.trim($params['width'],' "\'').'"';
		} else {
			$size = @getimagesize($fullpath);
			if( $size && $size[0] > 0 ) {
				$text .= ' width="'.$size[0].'"';
			}
		}

		if( !empty($params['height']) ) {
			$text .= ' height="'.trim($params['height'],' "\'').'"';
		} else {
			if( !isset($size) ) $size = @getimagesize($fullpath);
			if( $size && $size[1] > 0 ) {
				$text .= ' height="'.$size[1].'"';
			}
		}

		if( !empty($params['alt']) ) {
			$alt = trim($params['alt'],' "\'');
		} else {
			$alt = basename($fullpath);
		}
		$text .= ' alt="'.$alt.'"';

		if( !empty($params['title']) ) {
			$text .= ' title="'.$params['title'].'"';
		} else {
			$text .= ' title="'.$alt.'"';
		}

		if( !empty($params['class']) ) $text .= ' class="'.$params['class'].'"';
		if( !empty($params['addtext']) ) $text .= ' ' . $params['addtext'];
		$text .= '>';
	} else {
		$text = '<!-- empty results from image plugin -->';
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']),$text);
		return '';
	}
	return $text;
}


function smarty_cms_about_function_image()
{
?>
	<p>Author: Robert Campbell</p>

	<p>Change History</p>
	<ul>
		<li>Initial release</li>
		<li>Added alt param and removed the &lt;/img&gt;</li>
		<li>Added default width, height and alt <small>(contributed by Walter Wlodarski)</small></li>
		<li>Deprecated 2017-06-10, instead use page_image tag</li>
		<li>Undeprecated 2025-01-20, the page_image tag does not sufficiently replace image</li>
	</ul>
<?php
}
?>
