<?php
#Plugin handler: page_image
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

function smarty_function_page_image($params, $smarty)
{
    $get_bool = function(array $params,$key,$dflt) {
        if( !isset($params[$key]) ) return (bool) $dflt; // null/none value impossible here ?
//      if( empty($params[$key]) ) return (bool) $dflt; useless set and false-valued
        return cms_to_bool($params[$key]);
    };

    $full = $get_bool($params,'full',false); // whether to output an url string
    $tag = $get_bool($params,'tag',!$full); // whether to output a <img/> tag
    if( $tag && $full ) { $full = false; }
    elseif( !($tag || $full) ) { $full = true; }
    $thumbnail = $get_bool($params,'thumbnail',false);
    $assign = get_parameter_value($params,'assign');
    unset($params['assign'],$params['full'],$params['tag'],$params['thumbnail']);

    $contentobj = cms_utils::get_current_content();
    if( is_object($contentobj) ) {
        $propname = ($thumbnail) ? 'thumbnail' : 'image';
        $val = $contentobj->GetPropertyValue($propname);
        if( !$val || $val == -1 ) { $val = ''; }
        else { $val = trim($val); }
    }
    else {
        $val = '';
    }

    if( $val ) {
        if( $tag ) { $orig_val = $val; } // preserve for alt attr etc
        if( $val[0] == '/' ) {
            if( $val[1] != '/' ) {
                $val = CMS_ROOT_URL.$val;
            }
            //stet $val for url like '//host...'
            $found = true;
        }
        elseif( parse_url($val,PHP_URL_HOST) ) {
            // stet absolute url $val
            $found = true;
        }
        else {
            $found = false;
            $aspath = strtr($val,'\\/',DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
            $config = cms_config::get_instance();
            $prefname = ($thumbnail) ? 'content_thumbnailfield_path' : 'content_imagefield_path';
            $subpath = trim(cms_siteprefs::get($prefname),' \\/');
            //TODO also poll other places e.g. among assets, themes
            if( $subpath ) {
                $subpath = strtr($subpath,'\\/',DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
                $lookin = [
                    $config['image_uploads_path'].DIRECTORY_SEPARATOR.$subpath,
                    $config['image_uploads_path'],
                    $config['uploads_path'].DIRECTORY_SEPARATOR.$subpath,
                    $config['uploads_path']
                ];
            }
            else {
                $lookin = [
                    $config['image_uploads_path'],
                    $config['uploads_path']
                ];
            }
            foreach( $lookin as $place ) {
                $fp = $place.DIRECTORY_SEPARATOR.$aspath;
                if( is_file($fp) ) {
                    $tmp = str_replace([CMS_ROOT_PATH,'\\'],[CMS_ROOT_URL,'/'],$place);
                    $val = $tmp.'/'.ltrim($val,'/');
                    $found = true;
                    break;
                }
            }
        }
    }
    else {
        $found = false;
    }

    if( $tag ) {
        if( $found ) {
            if( empty($params['alt']) ) {
                $params['alt'] = 'Page image';
                if( !startswith($orig_val, 'data') ) {
                    $helper = new CMSMS\FileTypeHelper();
                    $tmp = strtr($orig_val, '/', DIRECTORY_SEPARATOR);
                    $ext = $helper->get_extension($tmp);
                    if( $ext ) {
                        $allexts = $helper->get_file_type_extensions('image');
                        if( in_array($ext, $allexts) ) {
                            $params['alt'] .= ' '.basename($tmp);
                        }
                    }
//                  else {
//                      $params['alt'] .= ' '.basename($tmp);
//                  }
                }
            }
            // build an img element
            $out = "<img src=\"$val\"";
            foreach( $params as $key => $val ) {
                $key = trim($key);
                if( !$key ) continue;
                $val = trim($val);
                $out .= " $key=\"$val\"";
            }
            $out .= '>';
        }
        else { // $tag && !$found
            $out = '<!-- No image source -->';
        }
    }
    elseif( $found ) { // $full && $found
/* an uploads-relative string reflects this tag's help, but not actual former code
        $tmp = $config['uploads_url'];
        if( startswith($val,$tmp) ) {
            $out = str_replace($tmp,'',$val);
        }
        else {
            $out = $val;
        }
*/
        $out = $val;
    }
    else {
        $out = ''; // OR $orig_val ?
    }

    if( $assign ) {
        $smarty->assign($assign,$out);
        return '';
    }
    return $out;
}

function smarty_cms_about_function_page_image() {
?>
    <p>Author: Ted Kulp&lt;ted@cmsmadesimple.org&gt;</p>

    <p>Change History:</p>
    <ul>
        <li>Jul 2025
          <ul>
            <li>Support absolute and relative and protocol-relative urls</li>
            <li>Support content-property-specific sub-folders for on-site files</li>
            <li>Support a search-path for populating relative urls</li>
          </ul>
        </li>
        <li>Jan 2016 <em>(Robert Campbell)</em> - Add the full param for CMSMS 2.2</li>
        <li>Fix for CMSMS 1.9</li>
    </ul>
<?php
}
?>
