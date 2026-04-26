<?php
#Plugin handler: form_start
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
#
#You should have received a copy of the GNU General Public License
#along with this program. If not, read the license online at:
#https://www.gnu.org/licenses/#LicenseURLs

function smarty_function_form_start($params, $tpl)
{
    $mactparms = array();
    $mactparms['module'] = $tpl->getTemplateVars('actionmodule');
    $mactparms['mid'] = $tpl->getTemplateVars('actionid');
    $mactparms['returnid'] = $tpl->getTemplateVars('returnid');
    $mactparms['inline'] = 0;

    $tagparms = ['method' => 'post','enctype' => 'multipart/form-data'];
    $gCms = CmsApp::get_instance();
    if( $gCms->test_state(CmsApp::STATE_ADMIN_PAGE) ) {
        // check if it's a module action
        if( $mactparms['module'] ) {
            $tmp = $tpl->getTemplateVars('actionparams');
            if( is_array($tmp) && isset($tmp['action']) ) $mactparms['action'] = $tmp['action'];

            $tagparms['action'] = 'moduleinterface.php';
            if( !isset($mactparms['action']) ) $mactparms['action'] = 'defaultadmin';
            $mactparms['returnid'] = '';
            if( !$mactparms['mid'] ) $mactparms['mid'] = 'm1_';
        }
    }
    elseif( $gCms->is_frontend_request() ) {
        if( $mactparms['module'] ) {
            $tmp = $tpl->getTemplateVars('actionparams');
            if( is_array($tmp) && isset($tmp['action']) ) $mactparms['action'] = $tmp['action'];

            $tagparms['action'] = 'moduleinterface.php';
            if( !$mactparms['returnid'] ) $mactparms['returnid'] = CmsApp::get_instance()->get_content_id();
            $hm = $gCms->GetHierarchyManager();
            $node = $hm->sureGetNodeById($mactparms['returnid']);
            if( $node ) {
                $content_obj = $node->getContent();
                if( $content_obj ) $tagparms['action'] = $content_obj->GetURL();
            }
        }
    }

    $parms = array();
    foreach( $params as $key => $value ) {
        switch( $key ) {
        case 'module':
        case 'action':
        case 'mid':
        case 'returnid':
        case 'inline':
            $mactparms[$key] = trim($value);
            break;

        case 'inline':
            $mactparms[$key] = (bool) $value;
            break;

        case 'prefix':
            $mactparms['mid'] = trim($value);
            break;

        case 'method':
            $tagparms[$key] = strtolower(trim($value));
            break;

        case 'url':
            $key = 'action';
            if( dirname($value) == '.' ) {
                $config = $gCms->GetConfig();
                $value = $config['admin_url'].'/'.trim($value);
            }
            $tagparms[$key] = trim($value);
            break;

        case 'enctype':
        case 'id':
        case 'class':
            $tagparms[$key] = trim($value);
            break;

        case 'extraparms':
            if( $value && is_array($value) ) {
                foreach( $value as $key => $value2 ) {
                    $parms[$key] = $value2;
                }
            }
            break;

        case 'assign':
            break;

        default:
            if( startswith($key,'form-') ) {
                $key = substr($key,5);
                $tagparms[$key] = $value;
            } else {
                $parms[$key] = $value;
            }
            break;
        }
    }

    $htmlit = function($value)
    {
        if( is_string($value) ) {
            $tmp = trim($value,"\" \n\r\t\v\0");
            return ($tmp && !is_numeric($tmp) ) ? addcslashes($tmp,'"') : $tmp;
        }
        if( is_scalar($value) || $value === null ) {
            return (string)$value;
        }
        if( is_object($value) && method_exists($value,'__toString') ) {
            $tmp = trim((string)$value,"\" \n\r\t\v\0");
            return ($tmp && !is_numeric($tmp) ) ? addcslashes($tmp,'"') : $tmp;
        }
        return 'Unusable value';
    };

    $out = "\n<form";
    foreach( $tagparms as $key => $value ) {
        if( $value ) {
            $out .= " $key=\"".$htmlit($value).'"';
        } else {
            $out .= " $key";
        }
    }
    $out .= ">\n <div class=\"hidden\">\n";
    if( $mactparms['module'] && $mactparms['action'] ) {
        $mact = $mactparms['module'].','.$mactparms['mid'].','.$mactparms['action'].','.(int)$mactparms['inline'];
        $out .= '  <input type="hidden" name="mact" value="'.$mact."\">\n";
        if( $mactparms['returnid'] ) {
            $out .= '  <input type="hidden" name="'.$mactparms['mid'].'returnid" value="'.$mactparms['returnid']."\">\n";
        }
    }
    if( !$gCms->is_frontend_request() ) {
        if( !isset($mactparms['returnid']) || $mactparms['returnid'] == '' ) {
            $out .= '  <input type="hidden" name="'.CMS_SECURE_PARAM_NAME.'" value="'.$_SESSION[CMS_USER_KEY]."\">\n";
        }
    }
    foreach( $parms as $key => $value ) {
        if( !is_array($value) ) {
            $out .= '  <input type="hidden" name="'.$mactparms['mid'].$key.'" value="'.$htmlit($value)."\">\n";
        } else {
            foreach( $value as $value2 ) {
                $out .= '  <input type="hidden" name="'.$mactparms['mid'].$key.'"[] value="'.$htmlit($value2)."\">\n";
            }
        }
    }
    $out .= ' </div>'."\n";

    if( !empty($params['assign']) ) {
        $tpl->assign(trim($params['assign']),$out);
        return '';
    }
    return $out;
}
?>
