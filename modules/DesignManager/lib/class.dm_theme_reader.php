<?php
#-------------------------------------------------------------------------
# Module DesignManager class dm_theme_reader
# (c) 2015 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

class dm_theme_reader extends dm_reader_base
{
  private $_xml;
  private $_scanned = FALSE;
  private $_design_info = [];
  private $_tpl_info = [];
  private $_css_info = [];
  private $_ref_map = [];

  public function __construct($fn)
  {
    parent::__construct($fn);
    $this->_xml = new dm_xml_reader();
    if( !$this->_xml->open($fn) ) {
      throw new RuntimeException("Theme reader failed to open $fn");
    }
  }

  private function _scan()
  {
    if( $this->_scanned ) return;

    $in = [];
    $__get_in = function() use (&$in) {
      if( $in ) { return $in[count($in) - 1]; } //PHP 8.5+ array_last()
      return '';
    };

    $this->_xml->SetParserProperty(XMLReader::VALIDATE,TRUE);
    $cur_key = null; // aka unset
/* XML properties processed here
'assoc'
'cssdata'
'cssmediatype'
'cssname'
'mmtemplate'
'mmtemplate_data'
'mmtemplate_name'
'name'
'refdata'
'reference'
'reflocation'
'refname'
'stylesheet'
'tdata'
'template'
'theme'
'tname'
*/
    while( $this->_xml->read() ) {
      switch( $this->_xml->nodeType ) {
        case XmlReader::ELEMENT:
          switch( $this->_xml->localName ) {
          case 'theme':
          case 'template':
          case 'assoc':
          case 'stylesheet':
          case 'reference':
          case 'mmtemplate':
            $in[] = $this->_xml->localName;
            break 2;
          //TODO handle errors in the following
          case 'name':
            if( $__get_in() != 'theme' ) {
              // validity error
            }
            $this->_xml->read();
            $this->_design_info['name'] = $this->_xml->value;
            break 2;

          case 'tname':
            if( $__get_in() != 'template' ) {
              // validity error
            }
            $this->_xml->read();
            $cur_key = $this->_xml->value;
            if( !isset($this->_tpl_info[$cur_key]) ) $this->_tpl_info[$cur_key] = [];
            if( isset($this->_tpl_info[$cur_key]) ) {
              // duplicate template name in XML file
            }
            $this->_tpl_info[$cur_key]['name'] = $cur_key;
            $p = strpos($cur_key,'.');
            if( $p !== FALSE ) {
              $tmp = substr($cur_key,0,$p);
              $this->_tpl_info[$cur_key]['name'] = $cur_key;
            }
            break 2;

          case 'tdata':
            if( $__get_in() != 'template' ) {
              // validity error
            }
            $this->_xml->read();
//          $cur_key = $this->_xml->value;
            if( $cur_key ) {
              $this->_tpl_info[$cur_key]['data'] = $this->_xml->value;
              break 2;
            }
            else {
              throw new Exception('Invalid theme data structure');
            }
            //no break here
          case 'mmtemplate_name':
            if( $__get_in() != 'template' ) {
              // validity error
            }
            $this->_xml->read();
            $cur_key = $this->_xml->value;
            if( !isset($this->_tpl_info[$cur_key]) ) $this->_tpl_info[$cur_key] = [];
            if( isset($this->_tpl_info[$cur_key]) ) {
              // error, duplicate template name in XML file
            }
            $this->_tpl_info[$cur_key]['name'] = $cur_key;
            $this->_tpl_info[$cur_key]['type'] = 'MM';
            $p = strpos($cur_key,'.');
            if( $p !== FALSE ) {
              $tmp = substr($cur_key,0,$p);
              $this->_tpl_info[$cur_key]['name'] = $tmp;
            }
            break 2;

          case 'mmtemplate_data':
            if( $__get_in() != 'template' ) {
              // validity error
            }
            $this->_xml->read();
//          $cur_key = $this->_xml->value;
            if( $cur_key ) {
              $this->_tpl_info[$cur_key]['data'] = $this->_xml->value;
              break 2;
            }
            else {
              throw new Exception('Invalid theme data structure');
            }
            //no break here
          case 'cssname':
            if( $__get_in() != 'stylesheet' ) {
              // validity error
            }
            $this->_xml->read();
            $cur_key = $this->_xml->value;
            if( !isset($this->_css_info[$cur_key]) ) $this->_css_info[$cur_key] = [];
            if( isset($this->_css_info[$cur_key]) ) {
              // error, duplicate stylesheet name in XML file
            }
            $this->_css_info[$cur_key]['name'] = $cur_key;
            break 2;

          case 'cssdata':
            if( $__get_in() != 'stylesheet' ) {
              // validity error
            }
            $this->_xml->read();
//          $cur_key = $this->_xml->value;
            if( $cur_key ) {
              $this->_css_info[$cur_key]['data'] = $this->_xml->value;
              break 2;
            }
            else {
              throw new Exception('Invalid theme data structure');
            }
            //no break here
          case 'cssmediatype':
            if( $__get_in() != 'stylesheet' ) {
              // validity error.
            }
            $this->_xml->read();
//          $cur_key = $this->_xml->value;
            if( $cur_key ) {
              $this->_css_info[$cur_key]['mediatype'] = $this->_xml->value;
              break 2;
            }
            else {
              throw new Exception('Invalid theme data structure');
            }
            //no break here
          case 'refname':
            if( $__get_in() != 'reference' ) {
              // validity error
            }
            $this->_xml->read();
            $cur_key = $this->_xml->value;
            if( !isset($this->_ref_map[$cur_key]) ) $this->_ref_map[$cur_key] = [];
            if( isset($this->_ref_map[$cur_key]) ) {
              // error, duplicate reference name in XML file
            }
            $this->_ref_map[$cur_key]['name'] = $cur_key;
            break 2;

          case 'refdata':
            if( $__get_in() != 'reference' ) {
              // validity error
            }
            $this->_xml->read();
//          $cur_key = $this->_xml->value;
            if( $cur_key ) {
              $this->_ref_map[$cur_key]['data'] = $this->_xml->value;
              break 2;
            }
            else {
              throw new Exception('Invalid theme data structure');
            }
            //no break here
          case 'reflocation':
            if( $__get_in() != 'reference' ) {
              // validity error.
            }
            $this->_xml->read();
//          $cur_key = $this->_xml->value;
            if( $cur_key ) {
              $this->_ref_map[$cur_key]['location'] = $this->_xml->value;
              break 2;
            }
            else {
              throw new Exception('Invalid theme data structure');
            }
          }  // ELEMENT localName switch
          break;

        case XmlReader::END_ELEMENT:
          switch( $this->_xml->localName ) {
          case 'theme':
          case 'template':
          case 'stylesheet':
          case 'assoc':
          case 'reference':
          case 'mmtemplate':
            if( $in ) {
              array_pop($in);
            }
            $cur_key = null;
          } // END_ELEMENT localName switch
          break;
      } // nodeType switch
    } // while

    $this->_scanned = TRUE;
  }

  public function validate()
  {
    $this->_scan();
    if( !isset($this->_design_info['name']) || $this->_design_info['name'] == '' ) {
      throw new CmsException('Invalid XML FILE (test1)');
    }
    if( count($this->_tpl_info) == 0 ) {
      throw new CmsException('Invalid XML FILE (test2)');
    }
    if( count($this->_css_info) == 0 ) {
      throw new CmsException('Invalid XML FILE (test3)');
    }
    // it validates.
  }

  public function get_design_info()
  {
    $this->_scan();

    $mod = cms_utils::get_module('DesignManager');
    $out = $this->_design_info;
    $out['description'] = 'TODO - set theme description';
    $out['generated'] = 0; // not known.
    $out['cmsversion'] = $mod->Lang('unknown'); // a good, early version number.
    return $out;
  }

  public function get_template_list()
  {
    $this->_scan();

    $out = [];
    foreach( $this->_tpl_info as $key => $one ) {
      $rec = [];
      $rec['name'] = $one['name'];
      $rec['desc'] = '';
      $rec['data'] = base64_decode($one['data']);
      if( isset($one['type']) && $one['type'] == 'MM' ) {
        $rec['type_originator'] = 'MenuManager'; //deprecated, unlikely
        $rec['type_name'] = 'navigation';
      }
/*    elseif( 0 ) { TODO if other-module (e.g. Navigator) template is in the file
        $rec['type_originator'] = X
        $rec['type_name'] = Y;
      }
*/
      else {
        $rec['type_originator'] = CmsLayoutTemplateType::CORE; //TODO might be some other module
        $rec['type_name'] = 'page';
      }
      $out[$key] = $rec;
    }
    return $out;
  }

  public function get_stylesheet_list()
  {
    $this->_scan();

    $out = [];
    foreach( $this->_css_info as $one ) {
      $rec = [];
      $rec['name'] = $one['name'];
      $rec['desc'] = '';
      $rec['data'] = base64_decode($one['data']);
      $rec['mediatype'] = base64_decode($one['mediatype']);
      $rec['mediaquery'] = '';
      $out[] = $rec;
    }
    return $out;
  }

  protected function get_destination_dir()
  {
    $config = cmsms()->GetConfig();
    $name = $this->get_new_name();
    $dirnm = dm_utils::munge_name_to_dir($name);
    $dir = cms_join_path($config['themes_path'],$dirnm); // OR $config['assets_path'],'themes',$dirnm
    if( !is_dir($dir) ) {
      @mkdir($dir,0777,TRUE);
    }
    if( !is_dir($dir) || !is_writable($dir) ) {
      throw new CmsException('Could not create directory, or could not write in directory '.$dir);
    } else {
      touch($dir.DIRECTORY_SEPARATOR.'index.html');
    }
    return $dirnm;
  }

  private function validate_template_names()
  {
    $this->_scan();

    $templates = CmsLayoutTemplate::template_query(['as_list'=>1]);

    // rename if template name already in use
    foreach( $this->_tpl_info as &$rec ) {
      $name = $rec['name'];
      if( in_array($name,$templates) ) {
        // replacement name must conform to CmsAdminUtils::ITEMNAME_REGEX
        $orig_name = $name;
        for( $n = 2; $n < 12; $n++ ) {
          $new_name = "$orig_name $n";
          if( !in_array($new_name,$templates) ) {
            $rec['name'] = $new_name;
            $rec['old_name'] = $orig_name;
            break;
          }
        }
        if( $n == 12 ) {
          throw new RuntimeException('Could not determine a new name for template '.$orig_name);
        }
      }
    }
    unset($rec);
  }

  private function validate_stylesheet_names()
  {
    $this->_scan();

    $stylesheets = CmsLayoutStylesheet::get_all(TRUE);
    $css_names = array_values($stylesheets);

    // rename if sheet name already in use
    foreach( $this->_css_info as &$rec ) {
      if( in_array($rec['name'],$css_names) ) {
        // gotta come up with a new name
        $orig_name = $rec['name'];
        for( $n = 1; $n < 11; $n++ ) {
          // replacement must conform to CmsAdminUtils::ITEMNAME_REGEX
          $new_name = "$orig_name $n";
          if( !in_array($new_name,$css_names) ) {
            $rec['old_name'] = $orig_name;
            $rec['name'] = $new_name;
            break;
          }
        }
        if( $n == 11 ) {
          throw new RuntimeException('Could not determine a new name for stylesheet '.$orig_name);
        }
      }
    }
    unset($rec);
  }

  public function import()
  {
    $this->validate();
    $this->validate_template_names();
    $this->validate_stylesheet_names();

    $config = cmsms()->GetConfig();
    $newname = $this->get_new_name();
    $destdir = $this->get_destination_dir();
    $ref_map =& $this->_ref_map;

    // part1 .. start creating design..
    $design = new CmsLayoutCollection();
    $design->set_name($newname);
    $description = $this->get_suggested_description();

    if( !$description ) {
      $description = $info['description'];
      if( $description ) $description .= "\n----------------------------------------\n";
      $description .= 'Generated '.\locale_ftime('%x %X',$info['generated'])."\n";
      $description .= 'By CMSMS version: '.$info['cmsversion']."\n";
      $description .= 'Imported '.\locale_ftime('%x %X');
    }

    $design->set_description($description);

    // part2 .. expand files.
    foreach( $this->_ref_map as &$rec ) {
      $destfile = cms_join_path($config['themes_path'],$destdir,$rec['name']);
//    if( basename($rec['name']) != $rec['name'] ) { check for parent dir }
      $parent = dirname($destfile);
      if( !is_dir($parent) ) {
        if( !is_file($parent) ) {
          @mkdir($parent,0777,TRUE);
        }
        else {
          throw new RuntimeException('Could not create directory, or could not write in directory '.$parent);
        }
      }
      // old theme property values are always base64 encoded, never htmlspecialchar'd
      file_put_contents($destfile,base64_decode($rec['data']));
      $rec['tpl_url'] = "{themes_root}/$destdir/{$rec['name']}";
      $rec['css_url'] = "[[themes_root]]/$destdir/{$rec['name']}";
    }
    unset($rec);

    // part3 .. process stylesheets
    $css_info = $this->get_stylesheet_list();
    foreach( $css_info as $name => &$css_rec ) {
      $stylesheet = new CmsLayoutStylesheet();
      $stylesheet->set_name($css_rec['name']);

      $ob = $this;
      $regex='/url\s*\(\"*(.*)\"*\)/i';
      $css_rec['data'] = preg_replace_callback($regex,
        function($matches) use ($ob,$ref_map,$destdir) {
          $url = $matches[1];
          if( !startswith($url,'http') || startswith($url,CMS_ROOT_URL) ||
              startswith($url,'[[root_url]]') ) {
            $bn = basename($url);
            if( isset($ref_map[$bn]) ) {
              $out = $ref_map[$bn]['css_url'];
              return 'url('.$out.')';
            }
          }
          return $matches[0];
        },$css_rec['data']);
      if( isset($css_rec['media_type']) ) $stylesheet->add_media_type($css_rec['mediatype']);
      $stylesheet->set_content($css_rec['data']);
      $stylesheet->save();
      $design->add_stylesheet($stylesheet);
    }
    unset($css_rec);

    // part4 .. process templates
    $fn1 = function($matches) use ($ob,&$tpl_info) {
      $out = preg_replace_callback("/template\s*=[\\\"']{0,1}([a-zA-Z0-9._\ \:\-\/]+)[\\\"']{0,1}/i",
        function($matches) use ($ob,&$tpl_info) {
          if( isset($tpl_info[$matches[1]]) ) {
            $rec = $tpl_info[$matches[1]];
            $out = str_replace($matches[1],$rec['name'],$matches[0]);
            return $out;
          }
          // find the new name and do a substitution
          return $matches[0];
        },$matches[0]);
      return $out;
    };

    $fn2 = function($matches) use ($ob,&$type,$ref_map,$destdir) {
      $url = $matches[2];
      if( !startswith($url,'http') || startswith($url,CMS_ROOT_URL) || startswith($url,'{root_url}') ) {
        $bn = basename($url);
        if( isset($ref_map[$bn]) ) {
          $out = $ref_map[$bn]['tpl_url'];
          $out = " $type=\"$out\"";
          return $out;
        }
      }
      return $matches[0];
    };

    $tpl_info = $this->get_template_list();
    $have_mm_template = FALSE;
    foreach( $tpl_info as $name => &$tpl_rec ) {
      if( $tpl_rec['type_originator'] == 'MenuManager' ) $have_mm_template = TRUE; //TODO treatment for Navigator

      $template = new CmsLayoutTemplate();
      $template->set_owner(get_userid(FALSE));
      $template->set_name($tpl_rec['name']);

      $types = ['href', 'src', 'url'];
      $content = $tpl_rec['data'];
      foreach( $types as $type ) {
        $tmp_type = $type;
        $innerT = '[a-z0-9:?=&@/._-]+?';
        $content = preg_replace_callback("|$type\=([\"'`])(".$innerT.")\\1|i", $fn2,$content);
      }

      $content = preg_replace('/\{stylesheet/','{cms_stylesheet',$content);

      $regex='/\{menu.*\}/';
      $content = preg_replace_callback( $regex, $fn1, $content );

      $regex='/\{.*MenuManager.*\}/';
      $content = preg_replace_callback( $regex, $fn1, $content );

      $tpl_rec['data'] = $content;
      $template->set_content($content);
      $template->set_type($tpl_rec['type_originator'].'::'.$tpl_rec['type_name']);
      $template->save();
      $design->add_template($template);
    }
    unset($tpl_rec);

    // part5 ... save design
    $design->save();

    // part6 ... ensure MenuManager is activated, if installed.
    if( $have_mm_template ) {
      $modops = ModuleOperations::get_instance();
      $modops->ActivateModule('MenuManager',1); //TODO if installed
    }
  }
} // class
