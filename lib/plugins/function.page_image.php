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
        if( !isset($params[$key]) ) return (bool) $dflt;
        if( empty($params[$key]) ) return (bool) $dflt;
        return cms_to_bool($params[$key]);
    };

    $full = $get_bool($params,'full',false);
    $thumbnail = $get_bool($params,'thumbnail',false);
    $tag = $get_bool($params,'tag',false);
    $assign = trim(get_parameter_value($params,'assign'));
    unset($params['full'], $params['thumbnail'], $params['tag'], $params['assign']);

    $propname = 'image';
    if( $thumbnail ) $propname = 'thumbnail';
    if( $tag ) $full = true;

    $contentobj = cms_utils::get_current_content();
    $val = '';
    if( is_object($contentobj) ) {
        $val = $contentobj->GetPropertyValue($propname);
        if( !$val || $val == -1 ) $val = '';
    }

    $out = '';
    if( $val ) {
        $orig_val = $val;
        if( $full ) {
            if( $val[0] == '/' ) {
                $val = CMS_ROOT_URL.$val;
            }
            else {
                $config = cms_config::get_instance();
                $val = $config['image_uploads_url'].'/'.$val; //TODO c.f. cms_siteprefs::get('content_imagefield_path')) relative to url
            }
        }
        if( !$tag ) {
            $out = $val;
        } else {
            if( !isset($params['alt']) ) $params['alt'] = $orig_val;
            // build a tag.
            $out = "<img src=\"$val\"";
            foreach( $params as $key => $val ) {
                $key = trim($key);
                $val = trim($val);
                if( !$key ) continue;
                $out .= " $key=\"$val\"";
            }
            $out .= ">";
        }
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
        <li>Support absolute and relative image-urls</li>
        <li>Fix for CMSMS 1.9</li>
        <li>Jan 2016 <em>(calguy1000)</em> - Adds the full param for CMSMS 2.2</li>
    </ul>
<?php
}
?>
