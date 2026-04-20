<?php
#Module News plugin: news_image
#(c) 2025 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>

#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.

#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
#Or read it online: https://www.gnu.org/licenses/gpl-2.0.html

/**
 * Smarty plugin function for generating a html image element
 *
 * @param array $params including:
 *  'image_url' or 'src' declaring the source-url for the image,
 *    absolute (on-site or off-site) or relative (on-site only)
 *  optional image-element attribute(s) other than 'src' e.g. 'class', 'width'
 *  optional 'assign'
 * @param object $template
 * @return string
 */
function smarty_function_news_image($params, $template)
{
    if (!empty($params['src'])) {
        $image_url = trim($params['src']);
    } elseif (!empty($params['image_url'])) {
        $image_url = trim($params['image_url']);
    } else {
        return '<!-- Missing image source -->';
    }
    unset($params['image_url'], $params['src']);

    if (isset($params['assign'])) {
        $assign = trim($params['assign']);
        unset($params['assign']);
    } else {
        $assign = false;
    }

    $found = true;
    if (($image_url[0] == '/' && $image_url[1] != '/') ||
        !parse_url($image_url, PHP_URL_HOST)) {
        // it's a relative url
        $found = false;
        $aspath = strtr(trim($image_url, ' \/'), '\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
        $config = cms_config::get_instance();
        foreach ([
            $config['image_uploads_path'].DIRECTORY_SEPARATOR.'News',
            $config['image_uploads_path'],
            $config['uploads_path'].DIRECTORY_SEPARATOR.'News',
            $config['uploads_path']
        ] as $place) {
            $fp = $place.DIRECTORY_SEPARATOR.$aspath;
            if (is_file($fp)) {
                $tmp = str_replace([CMS_ROOT_PATH, '\\'], [CMS_ROOT_URL, '/'], $place);
                $image_url = $tmp . '/' . ltrim($image_url, '/');
                $found = true;
                break;
            }
        }
    }
    if ($found) {
        if (empty($params['alt'])) {
            $params['alt'] = 'Article image';
            if (!startswith($image_url, 'data')) {
                $helper = new CMSMS\FileTypeHelper();
                $tmp = strtr($image_url, '/', DIRECTORY_SEPARATOR);
                $ext = $helper->get_extension($tmp);
                if ($ext) {
                    // we ignore News-module 'allowed_upload_types' preference
                    $allexts = $helper->get_file_type_extensions('image');
                    if (in_array($ext, $allexts)) {
                        $params['alt'] .= ' '.basename($tmp);
                        if ($ext == 'svg') {
                            if (empty($params['height']) && empty($params['width'])) {
                                $params['height'] = '10em';
                            }
                        }
//                  } else {
                        //nothing here - an offsite url might seem like anything ?!
                    }
                }
            }
        }

        // build an img tag
        $out = "<img src=\"$image_url\"";
        foreach ($params as $key => $val) {
            $key = trim($key);
            if ($key) {
                $val = trim($val);
                $out .= " $key=\"$val\"";
            }
        }
        $out .= '>';
    } else {
        $out = '<!-- Invalid image source -->';
    }

    if ($assign) {
        $template->assign($assign, $out);
        return '';
    }
    return $out;
}
