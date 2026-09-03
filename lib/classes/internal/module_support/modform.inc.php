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
 * Backend functions for modules to do form related functions.
 * Included in the module class when needed.
 * @see CMSModule
 * @since   1.0
 * @package CMS
 * @license GPL
 */

/**
 * @access private
 */
function cms_module_CreateFormStart($modinstance, $id, $action='default', $returnid='', $method='post', $enctype='', $inline=false, $idsuffix='', $params = array(), $extra='')
{
  $gCms = CmsApp::get_instance();
  static $_formcount = 1;

  $id = cms_htmlentities($id);
  $action = cms_htmlentities($action);
  $returnid = cms_htmlentities($returnid);
  $method = cms_htmlentities($method);
  $enctype = cms_htmlentities($enctype);
  $idsuffix = cms_htmlentities($idsuffix);

  if( $idsuffix == '' ) { $idsuffix = $_formcount++; }

  $goto = 'moduleinterface.php';
  if( $returnid ) {
    $hm = $gCms->GetHierarchyManager();
    $node = $hm->sureGetNodeById($returnid);
    if( $node ) {
        $content_obj = $node->getContent();
        if( $content_obj ) { $goto = $content_obj->GetURL(); }
    }
  }
  if( $gCms->is_https_request() && strpos($goto,':') !== FALSE ) { $goto = str_replace('http:','https:',$goto); }
  $goto = ' action="'.$goto.'"';

  $text = '<form id="'.$id.'moduleform_'.$idsuffix.'" method="'.$method.'"'.$goto;
  $text .= ' class="cms_form"';
  if( $enctype ) { $text .= ' enctype="'.$enctype.'"'; }
  if( $extra ) { $text .= ' '.$extra; }
  $text .= '>'."\n".'<div class="hidden">'."\n".'<input type="hidden" name="mact" value="'.$modinstance->GetName().','.$id.','.$action.','.($inline == true?1:0).'">'."\n";
  if( $returnid ) {
    $text .= '<input type="hidden" name="'.$id.'returnid" value="'.$returnid.'">'."\n";
    if( $inline ) { $text .= '<input type="hidden" name="'.$gCms->config['query_var'].'" value="'.$returnid.'">'."\n"; }
  }
  else {
    $text .= '<input type="hidden" name="'.CMS_SECURE_PARAM_NAME.'" value="'.$_SESSION[CMS_USER_KEY].'">'."\n";
  }

  foreach( $params as $key=>$value ) {
    $value = cms_htmlentities($value);
    if( !($key == 'module' || $key == 'action' || $key == 'id') ) {
      if( is_array($value) ) {
        foreach( $value as $one ) {
          $text .= '<input type="hidden" name="'.$id.$key.'[]" value="'.$one.'">'."\n";
        }
      }
      else {
        $text .= '<input type="hidden" name="'.$id.$key.'" value="'.$value.'">'."\n";
      }
    }
  }

//  foreach( $params as $key=>$value ) {
//    $value = cms_htmlentities($value);
//    if( !($key == 'module' || $key == 'action' || $key == 'id') ) {
//      $text .= '<input type="hidden" name="'.$id.$key.'" value="'.$value.'">'."\n";
//    }
//  }
  $text .= "</div>\n";

  return $text;
}

/**
 * @access private
 */
function cms_module_CreateLabelForInput($modinstance, $id, $name, $labeltext='', $addtext='')
{
  $text = '<label class="cms_label" for="'.cms_htmlentities($id.$name).'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= '>'.$labeltext.'</label>'."\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputText($modinstance, $id, $name, $value='', $size='10', $maxlength='255', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $size = (int)$size;
  $maxlength = (int)$maxlength;

  $value = str_replace('"', '&quot;', $value);

  $text = '<input type="text" class="cms_textfield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputTextWithLabel($modinstance, $id, $name, $value='', $size='10', $maxlength='255', $addtext='', $label='', $labeladdtext='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $value = cms_htmlentities($value);
  $size = (int)$size;
  $maxlength = (int)$maxlength;

  if( $label == '' ) { $label = $name; }
  $text = '<label class="cms_label" for="'.$id.$name.'" '.$labeladdtext.'>'.$label.'</label>'."\n";
  $text .= $modinstance->CreateInputText($id, $name, $value, $size, $maxlength, $addtext);
  $text .= "\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputColor($modinstance, $id, $name, $value='', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="color" class="cms_colorfield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputDate($modinstance, $id, $name, $value='', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="date" class="cms_datefield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 * Obsolete
 */
function cms_module_CreateInputDatetime($modinstance, $id, $name, $value='', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="datetime" class="cms_datefield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputDatetimeLocal($modinstance, $id, $name, $value='', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="datetime-local" class="cms_datefield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputMonth($modinstance, $id, $name, $value='', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="month" class="cms_datefield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputWeek($modinstance, $id, $name, $value='', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="week" class="cms_datefield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputTime($modinstance, $id, $name, $value='', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="time" class="cms_datefield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputNumber($modinstance, $id, $name, $value='', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="number" class="cms_numberfield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputRange($modinstance, $id, $name, $value='', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="range" class="cms_numberfield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputEmail($modinstance, $id, $name, $value='', $size='10', $maxlength='255', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $size = (int)$size;
  $maxlength = (int)$maxlength;

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="email" class="cms_emailfield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputTel($modinstance, $id, $name, $value='', $size='10', $maxlength='255', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $size = (int)$size;
  $maxlength = (int)$maxlength;

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="tel" class="cms_telfield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputSearch($modinstance, $id, $name, $value='', $size='10', $maxlength='255', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $size = (int)$size;
  $maxlength = (int)$maxlength;

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="search" class="cms_searchfield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputUrl($modinstance, $id, $name, $value='', $size='10', $maxlength='255', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $size = (int)$size;
  $maxlength = (int)$maxlength;

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="url" class="cms_urlfield" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 * @see also cms_module_CreateFileUploadInput()
 */
function cms_module_CreateInputFile($modinstance, $id, $name, $accept='', $addtext='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $accept = cms_htmlentities($accept);

  $text='<input type="file" class="cms_browse" name="'.$id.$name.'"';
  if( $accept ) { $text .= ' accept="' . $accept.'"'; }
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputPassword($modinstance, $id, $name, $value='', $size='10', $maxlength='255', $addtext='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $value = cms_htmlentities($value);
  $size = (int)$size;
  $maxlength = (int)$maxlength;

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="password" class="cms_password" id="'.$id.$name.'" name="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputHidden($modinstance, $id, $name, $value='', $addtext='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $value = cms_htmlentities($value);

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="hidden" id="'.$id.$name.'" name="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputCheckbox($modinstance, $id, $name, $value='', $selectedvalue='', $addtext='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $value = cms_htmlentities($value);
  $selectedvalue = cms_htmlentities($selectedvalue);

  $text = '<input type="checkbox" class="cms_checkbox" name="'.$id.$name.'" value="'.$value.'"';
  if( $selectedvalue == $value ) { $text .= ' checked'; }
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputSubmit($modinstance, $id, $name, $value='', $addtext='', $image='', $confirmtext='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $image = cms_htmlentities($image);

  $text = '<input class="cms_submit" name="'.$id.$name.'" id="'.$id.$name.'" value="'.$value.'" type=';

  if( $image ) {
    $text .= '"image"';
    $img = CMS_ROOT_URL . '/' . $image;
    $text .= ' src="'.$img.'"';
  }
  else {
    $text .= '"submit"';
  }
  if( $confirmtext ) { $text .= ' onclick="return confirm(\''.$confirmtext.'\');"'; }
  if( $addtext ) { $text .= ' ' . $addtext; }

  $text .= '>';
  return $text . "\n";
}

/**
 * @access private
 */
function cms_module_CreateInputReset($modinstance, $id, $name, $value='Reset', $addtext='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $text = '<input type="reset" class="cms_reset" name="'.$id.$name.'" value="'.$value.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= '>';
  return $text . "\n";
}

/**
 * @access private
 * @see also cms_module_CreateInputFile()
 */
function cms_module_CreateFileUploadInput($modinstance, $id, $name, $addtext='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);

  $text = '<input type="file" class="cms_browse" name="'.$id.$name.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= '>';
  return $text . "\n";
}

/**
 * @access private
 */
function cms_module_CreateInputDropdown($modinstance, $id, $name, $items, $selectedindex, $selectedvalue, $addtext)
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $selectedindex = cms_htmlentities($selectedindex);
  $selectedvalue = cms_htmlentities($selectedvalue);

  $text = '<select class="cms_dropdown" name="'.$id.$name.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";
  $count = 0;
  if( is_array($items) && count($items) > 0 ) {
    foreach( $items as $key=>$value ) {
      $text .= '<option value="'.$value.'"';
      if( $selectedindex == $count || $selectedvalue == $value ) { $text .= ' selected'; }
      $text .= '>';
      $text .= $key;
      $text .= "</option>\n";
      $count++;
    }
  }
  $text .= "</select>\n";

  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputDataList($modinstance, $id, $name, $value='', $items = [], $size='10', $maxlength='255', $addtext='')
{
  $value = cms_htmlentities($value);
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $size = (int)$size;
  $maxlength = (int)$maxlength;

  $value = str_replace('"', '&quot;', $value);
  $text = '<input type="text" class="cms_datalistfield" name="'.$id.$name.'" list="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= ">\n";

  $text .= '<datalist class="cms_datalist" id="'.$id.$name.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  $text .= '>';
  $count = 0;
  if( is_array($items) && count($items) > 0 ) {
    foreach( $items as $key=>$value ) {
      $text .= '<option value="'.$value.'"';
      $text .= '>';
      $text .= $key;
      $text .= '</option>';
      $count++;
    }
  }
  $text .= '</datalist>'."\n";
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputSelectList($modinstance, $id, $name, $items, $selecteditems=array(), $size=3, $addtext='', $multiple = true)
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $size = (int)$size;
  $multiple = (bool)$multiple;

  if( strstr($name,'[]') === FALSE && $multiple ) { $name.='[]'; }
  $text = '<select class="cms_select" name="'.$id.$name.'"';
  if( $addtext ) { $text .= ' ' . $addtext; }
  if( $multiple ) { $text .= ' multiple'; }
  $text .= ' size="'.$size."\">\n";
  $count = 0;
  foreach( $items as $key=>$value ) {
    $value = cms_htmlentities($value);

    $text .= '<option value="'.$value.'"';
    if( is_array($selecteditems) && in_array($value, $selecteditems) ) { $text .= ' selected'; }
    $text .= '>';
    $text .= $key;
    $text .= "</option>\n";
    $count++;
  }
  $text .= "</select>\n";

  return $text;
}

/**
 * @access private
 */
function cms_module_CreateInputRadioGroup($modinstance, $id, $name, $items, $selectedvalue='', $addtext='', $delimiter='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $selectedvalue = cms_htmlentities($selectedvalue);

  $text = '';
  $counter = 0;
  foreach( $items as $key=>$value ) {
    $value = cms_htmlentities($value);

    $counter = $counter + 1;
    $text .= '<input class="cms_radio" type="radio" name="'.$id.$name.'" id="'.$id.$name.$counter.'" value="'.$value.'"';
    if( $addtext ) { $text .= ' ' . $addtext; }
    if( $selectedvalue == $value ) { $text .= ' checked'; }
    $text .= '>';
    $text .= '<label class="cms_label" for="'.$id.$name.$counter.'">'.$key .'</label>' . $delimiter;
  }

  return $text;
}

/**
 * @access private
 */
function cms_module_CreateLink($modinstance, $id, $action, $returnid='', $contents='', $params=array(), $warn_message='',
                               $onlyhref=false, $inline=false, $addtext='', $targetcontentonly=false, $prettyurl='')
{
  if( !is_array($params) && $params == '' ) { $params = array(); }
  $id = cms_htmlentities($id);
  $action = cms_htmlentities($action);
  $returnid = cms_htmlentities($returnid);
  $prettyurl = cms_htmlentities($prettyurl);

  // create url
  //TODO $text = cms_module_CreateUrl($modinstance,$id,$action,$returnid,params,$inline,$targetcontentonly,$prettyurl);
  $text = $modinstance->create_url($id,$action,$returnid,$params,$inline,$targetcontentonly,$prettyurl); //TODO deprecated use ->GetUrl()

  if( !$onlyhref ) {
    $beginning = '<a';
    if( !empty($params['class']) ) {
      $beginning .= ' class="' . cms_htmlentities($params['class']) . '"';
    }
    $beginning .= ' href="';
    $text = $beginning . $text . '"';
    if( $warn_message ) { $text .= ' onclick="return confirm(\''.$warn_message.'\');"'; }
    if( $addtext ) { $text .= ' ' . $addtext; }
    $text .= '>'.$contents.'</a>';
  }
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateUrl($modinstance,$id,$action,$returnid='',$params=array(),
                               $inline=false,$targetcontentonly=false,$prettyurl='')
{
  $config = \cms_config::get_instance();
  if( !$prettyurl && $config['url_rewriting'] != 'none' ) {
    // attempt to get a pretty url from the module... this is useful
    // if this method is being called from outside the source module
    // e.g. comments module wants a link to the article the comments are about
    // or something.
    $prettyurl = $modinstance->GetPrettyUrl($id,$action,$returnid,$params,$inline);
  }

  $base_url = CMS_ROOT_URL;

  // get the destination content object
  if( $returnid ) { // could it be < 0?
    $content_obj = CmsApp::get_instance()->GetContentOperations()->LoadContentFromId($returnid);
    if( is_object($content_obj) && $content_obj->Secure() ) { $base_url = $config['ssl_url']; }
  }

  $text = '';
  if( $prettyurl && $config['url_rewriting'] == 'mod_rewrite' ) {
    $text = $base_url . '/' . $prettyurl . $config['page_extension'];
  }
  elseif( $prettyurl && $config['url_rewriting'] == 'internal' ) {
    $text = $base_url . '/index.php/' . $prettyurl . $config['page_extension'];
  }
  else {
    if( $targetcontentonly || ($returnid && !$inline) ) { $id = 'cntnt01'; }
    $goto = ($returnid) ? 'index.php' : 'moduleinterface.php';
    $text = ($returnid <= 0) ? $config['admin_url'] : $base_url;

    $secureparam = '';
    if( $returnid == '' ) { $secureparam='&amp;'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]; }
    $text .= '/'.$goto.'?mact='.$modinstance->GetName().','.$id.','.$action.','.($inline == true?1:0).$secureparam;
    if( isset($params['returnid']) && $returnid != '' ) { unset($params['returnid']); }
    foreach( $params as $key=>$value ) {
      if( in_array($key,array('assign','id','returnid','action','module')) ) continue;
      $key = cms_htmlentities($key);
      if( is_scalar($value) ) {
        $text .= '&amp;'.$id.$key.'='.rawurlencode(cms_htmlentities($value));
      }
      else {
        $text .= '&amp;'.$id.$key.'='.rawurlencode(json_encode($value)); //the receiver needs be able to deal with such things!
      }
    }
    if( $returnid ) {
      $text .= '&amp;'.$id.'returnid='.$returnid;
      if( $inline ) { $text .= '&amp;'.$config['query_var'].'='.$returnid; }
    }
  }

  return $text;
}

/**
 * @access private
 */
function cms_module_CreateContentLink($modinstance, $pageid, $contents='')
{
  $pageid = cms_htmlentities($pageid);
  $contents = cms_htmlentities($contents);

  $gCms = CmsApp::get_instance();
  $config = $gCms->GetConfig();
  $text = '<a href="';
  if( $config["url_rewriting"] == 'mod_rewrite' ) {
    // mod_rewrite
    $contentops = $gCms->GetContentOperations();
    $alias = $contentops->GetPageAliasFromID( $pageid );
    if( $alias == false ) {
      return '<!-- ERROR: could not get an alias for pageid='.$pageid.'-->';
    }
    else {
      $text .= $config["root_url"]."/".$alias.(isset($config['page_extension'])?$config['page_extension']:'.shtml');
    }
  }
  else {
    // not mod rewrite
    $text .= $config["root_url"]."/index.php?".$config["query_var"]."=".$pageid;
  }
  $text .= '">'.$contents.'</a>';
  return $text;
}

/**
 * @access private
 */
function cms_module_CreateReturnLink($modinstance, $id, $returnid, $contents='', $params=array(), $onlyhref=false)
{
  $id = cms_htmlentities($id);
  $returnid = cms_htmlentities($returnid);
  $contents = $contents;

  $text = '';
  $gCms = CmsApp::get_instance();
  $config = $gCms->GetConfig();
  $manager = $gCms->GetHierarchyManager();
  $node = $manager->sureGetNodeById($returnid);
  if( $node ) {
    $content = $node->GetContent();

    if( isset($content) ) {
      if( $content->GetURL() != '' ) {
        if( !$onlyhref ) $text .= '<a href="';
        $text .= $content->GetURL();
        $count = 0;
        foreach( $params as $key=>$value ) {
          $key = cms_htmlentities($key);
          $value = cms_htmlentities($value);
          if( $count == 0 ) {
            if( $config["url_rewriting"] != 'none' ) {
              $text .= '?';
            }
            else {
              $text .= '&amp;';
            }
          }
          else {
            $text .= '&amp;';
          }
          $text .= $id.$key.'='.$value;
          $count++;
        }
        if( !$onlyhref ) {
          $text .= "\"";
          $text .= '>'.$contents.'</a>';
        }
      }
    }
  }

  return $text;
}

/**
 * @access private
 */
function cms_module_CreateFieldsetStart($modinstance, $id, $name, $legend_text='', $addtext='', $addtext_legend='')
{
  $id = cms_htmlentities($id);
  $name = cms_htmlentities($name);
  $legend_text = cms_htmlentities($legend_text);

  $text = '<fieldset class="cms_fieldset" '. $addtext. '>'."\n";
  if( $legend_text ) {
    $text .= '<legend class="cms_legend" '. $addtext_legend .'>'."\n";
    $text .= $legend_text;
    $text .= '</legend>';
  }
  return $text;
}

?>
