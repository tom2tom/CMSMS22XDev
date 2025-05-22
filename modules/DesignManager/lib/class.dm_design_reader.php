<?php
#-------------------------------------------------------------------------
# Module DesignManager class dm_design_reader
# (c) 2012 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

class dm_design_reader extends dm_reader_base
{
    private $_xml;
    private $_scanned;
    private $_raw_design_info = [];
    private $_tpl_info = [];
    private $_css_info = [];
    private $_file_map = [];
    private $_new_design_description;

    public function __construct($fn)
    {
        parent::__construct($fn);
        $this->_xml = new dm_xml_reader();
        $this->_xml->open($fn);
        $this->_xml->SetParserProperty(XMLReader::VALIDATE,TRUE);
    }

    public function validate()
    {
        while( $this->_xml->read() ) {
            if( !$this->_xml->isValid() ) {
                throw new CmsException('Invalid XML FILE ');
            }
        }
        // it validates.
    }

    private function _scan()
    {
        $in = [];
        $cur_key = '';

        $__get_in = function() use ($in) {
            if( ($n = count($in)) > 0 ) {
                return $in[$n-1];
            }
            return '';
        };

        if( !$this->_scanned ) {
            $this->_scanned = TRUE;
            while( $this->_xml->read() ) {
                switch( $this->_xml->nodeType ) {
                case XmlReader::ELEMENT:
                    switch( $this->_xml->localName ) {
                    case 'design':
                    case 'template':
                    case 'stylesheet':
                    case 'file':
                        $in[] = $this->_xml->localName;
                        break;

                    case 'name':
                        if( $__get_in() != 'design' ) {
                            // validity error.
                        }
                        $name = $this->_xml->localName;
                        $this->_xml->read();
                        $this->_raw_design_info[$name] = $this->_xml->value;
                        break;

                    case 'description':
                    case 'generated':
                    case 'cmsversion':
                        if( $__get_in() != 'design' ) {
                            // validity error.
                        }
                        $name = $this->_xml->localName;
                        $this->_xml->read();
                        $this->_raw_design_info[$name] = base64_decode($this->_xml->value);
                        break;

                    case 'tkey':
                        if( $__get_in() != 'template' ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $cur_key = $this->_xml->value;
                        $this->_tpl_info[$cur_key] = ['key' => $cur_key];
                        break;

                    case 'tname':
                        if( $__get_in() != 'template' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_tpl_info[$cur_key]['name'] = $this->_xml->value;
                        break;

                    case 'tdesc':
                        if( $__get_in() != 'template' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_tpl_info[$cur_key]['desc'] = $this->_xml->value;
                        break;

                    case 'tdata':
                        if( $__get_in() != 'template' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_tpl_info[$cur_key]['data'] = $this->_xml->value;
                        break;

                    case 'ttype_originator':
                    case 'ttype_name':
                        if( $__get_in() != 'template' || !$cur_key ) {
                            // validity error.
                        }
                        $key = $this->_xml->localName;
                        $this->_xml->read();
                        $this->_tpl_info[$cur_key][$key] = $this->_xml->value;
                        break;

                    case 'csskey':
                        if( $__get_in() != 'stylesheet' ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $cur_key = $this->_xml->value;
                        $this->_css_info[$cur_key] = ['key' => $cur_key];
                        break;

                    case 'cssname':
                        if( $__get_in() != 'stylesheet' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['name'] = $this->_xml->value;
                        break;

                    case 'cssdesc':
                        if( $__get_in() != 'stylesheet' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['desc'] = $this->_xml->value;
                        break;

                    case 'cssdata':
                        if( $__get_in() != 'stylesheet' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['data'] = $this->_xml->value;
                        break;

                    case 'cssmediatype':
                        if( $__get_in() != 'stylesheet' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['mediatype'] = $this->_xml->value;
                        break;

                    case 'cssmediaquery':
                        if( $__get_in() != 'stylesheet' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['mediaquery'] = $this->_xml->value;
                        break;

                    case 'fkey':
                        if( $__get_in() != 'file' ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $cur_key = $this->_xml->value;
                        $this->_file_map[$cur_key] = ['key' => $cur_key];
                        break;

                    case 'fvalue':
                        if( $__get_in() != 'file' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_file_map[$cur_key]['value'] = $this->_xml->value;
                        break;

                    case 'fdata':
                        if( $__get_in() != 'file' || !$cur_key ) {
                            // validity error.
                        }
                        $this->_xml->read();
                        $this->_file_map[$cur_key]['data'] = $this->_xml->value;
                        break;
                    } // localName switch
                    break;

                case XmlReader::END_ELEMENT:
                    switch( $this->_xml->localName ) {
                    case 'design':
                    case 'template':
                    case 'stylesheet':
                    case 'file':
                        if( $in ) {
                            array_pop($in);
                        }
                        $cur_key = '';
                        break;
                    } //localName switch
                } // nodeType switch
            } //while
        }
    }

    private function _get_name($key)
    {
        if( isset($this->_file_map[$key]) ) return $this->_file_map[$key]['value'];
        return '';
    }

    public function get_design_info()
    {
        $this->_scan();
        return $this->_raw_design_info;
    }

    public function set_new_description($description = '')
    {
      $this->_new_design_description = $description;
    }

    public function get_template_list()
    {
        $this->_scan();
        $out = [];
        foreach( $this->_tpl_info as $key => $one ) {
            $rec = [];
            $rec['name'] = base64_decode($one['name']);
            $rec['newname'] = \CmsLayoutTemplate::generate_unique_name($rec['name']);
            $rec['key'] = $key;
            $rec['desc'] = base64_decode($one['desc']);
            $rec['data'] = base64_decode($one['data']);
            $rec['type_originator'] = base64_decode($one['ttype_originator']);
            $rec['type_name'] = base64_decode($one['ttype_name']);
            $out[] = $rec;
        }
        return $out;
    }

    public function get_stylesheet_list()
    {
        $this->_scan();
        $out = [];
        foreach( $this->_css_info as $key => $one ) {
            $rec = [];
            $rec['name'] = base64_decode($one['name']);
            $rec['newname'] = \CmsLayoutStylesheet::generate_unique_name($rec['name']);
            $rec['key'] = $key;
            $rec['desc'] = base64_decode($one['desc']);
            $rec['data'] = base64_decode($one['data']);
            $rec['mediatype'] = base64_decode($one['mediatype']);
            $rec['mediaquery'] = base64_decode($one['mediaquery']);
            $out[] = $rec;
        }
        return $out;
    }

    protected function validate_template_names()
    {
        $this->_scan();

        $templates = CmsLayoutTemplate::template_query(['as_list'=>1]);
        $tpl_names = array_values($templates);

        foreach( $this->_file_map as $key => &$rec ) {
            if( !startswith($key,'__TPL,,') ) continue;

            if( in_array($rec['value'],$tpl_names) ) {
                // gotta come up with a new name
                $orig_name = $rec['value'];
                $n = 1;
                while( $n < 10 ) {
                    $n++;
                    $new_name = $orig_name.' '.$n;
                    if( !in_array($new_name,$tpl_names) ) {
                        $rec['old_value'] = $rec['value'];
                        $rec['value'] = $new_name;
                        break;
                    }
                }
            }
        }
        unset($rec);
    }

    protected function validate_stylesheet_names()
    {
        $this->_scan();

        $stylesheets = CmsLayoutStylesheet::get_all(TRUE);
        $css_names = array_values($stylesheets);

        foreach( $this->_file_map as $key => &$rec ) {
            if( !startswith($key,'__CSS,,') ) continue;

            if( in_array($rec['value'],$css_names) ) {
                // gotta come up with a new name
                $orig_name = $rec['value'];
                $n = 1;
                while( $n < 10 ) {
                    $n++;
                    $new_name = $orig_name.' '.$n;
                    if( !in_array($new_name,$css_names) ) {
                        $rec['old_value'] = $rec['value'];
                        $rec['value'] = $new_name;
                        break;
                    }
                }
            }
        }
        unset($rec);
    }

    public function get_destination_dir()
    {
        $config = cmsms()->GetConfig();
        $name = $this->get_new_name();
        $dirname = munge_string_to_url($name);
        $dir = cms_join_path($config['themes_path'],$dirname); // OR $config['assets_path'],'designs',$dirname
        if( !is_dir($dir) ) {
            @mkdir($dir,0777,TRUE);
            if( !is_dir($dir) || !is_writable($dir) ) {
                throw new CmsException('Could not create directory, or could not write in directory '.$dir);
            }
            else {
                touch($dir.DIRECTORY_SEPARATOR.'index.html');
            }
        }

        return $dirname;
    }

    public function import()
    {
        $this->validate_template_names();
        $this->validate_stylesheet_names();

        $newname = $this->get_new_name();
        $destdir = $this->get_destination_dir(); //basename only
        $info = $this->get_design_info();
        $owner_id = get_userid(FALSE);
        $sep = DIRECTORY_SEPARATOR;

        // create new design... fill it with info
        $design = new CmsLayoutCollection();
        $design->set_name($newname);
        $description = $this->get_suggested_description();

        if( empty($description) ) {
            $description = $info['description'];
            if( $description ) {
                $description = preg_replace(
                ['~uploads[/\\](designs[/\\])?(?!/images)~'],
                ["assets{$sep}themes{$sep}"], // OR ["assets{$sep}designs{$sep}"],
                $description);
                $description .= "\n----------------------------------------\n";
            }
            $description .= 'Generated '.\locale_ftime('%x %X',$info['generated'])."\n";
            $description .= 'By CMSMS version: '.$info['cmsversion']."\n";
            $description .= 'Imported '.\locale_ftime('%x %X');
        }
//        else {
//DEBUG   $here = 1;
//        }

        $design->set_description($description);

        $config = cmsms()->GetConfig();

        // expand URL items to actual files
        // don't have to worry about duplicated filenames (hopefully)
        // because the destinaton directory is unique.
        foreach( $this->_file_map as $key => &$rec ) {
            if( !startswith($key,'__URL,,') ) continue;
            if( empty($rec['data']) ) continue; //TODO allow empty files
            $destfile = cms_join_path($config['themes_path'],$destdir,$rec['value']); // OR $config['assets_path'],'designs',$destdir,$rec['value']
//          if( basename($rec['value']) != $rec['value'] ) { check folder presence }
            $parent = dirname($destfile);
            if( !is_dir($parent) ) {
                if( !is_file($parent) ) {
                    @mkdir($parent,0777,TRUE);
                }
                else {
                    throw new CmsException('Could not create directory, or could not write in directory '.$parent);
                }
            }
            file_put_contents($destfile,base64_decode($rec['data']));
            touch($parent.DIRECTORY_SEPARATOR.'index.html');
//          $rec['tpl_url'] = "{assets_root}/designs/$destdir/{$rec['value']}";
//          $rec['css_url'] = "[[assets_root]]/designs/$destdir/{$rec['value']}";
            $rec['tpl_url'] = "{themes _root}/$destdir/{$rec['value']}";
            $rec['css_url'] = "[[themes_root]]/$destdir/{$rec['value']}";
        }
        unset($rec);

        // expand stylesheets
        foreach( $this->get_stylesheet_list() as $css ) {
            $stylesheet = new CmsLayoutStylesheet();
            $stylesheet->set_name($css['newname']);
            if( !empty($css['desc']) ) {
                $stylesheet->set_description($css['desc']); //TODO any relocation ?
            }
            // relocate any uploads or designs usage in there
            $content = preg_replace(
              ['~\[\[uploads_url\]\](/designs)?(?!/images)~',
               '~\[\[root_url\]\]/uploads(?!/images)~'],
//            ['[[assets_root]]/designs',
//             '[[assets_root]]/designs'],
              ['[[themes_root]]',
               '[[themes_root]]'],
            $css['data']);
            foreach( $this->_file_map as $key => &$rec ) {
                if( !startswith($key,'__URL,,') ) continue;
                if( !isset($rec['css_url']) ) continue;
                $content = str_replace($key,$rec['css_url'],$content);
            }
            unset($rec);
            $stylesheet->set_content($content);

            if( $css['mediatype'] ) {
                $tmp = explode(',',$css['mediatype']);
                for( $i = 0,$n = count($tmp); $i < $n; $i++ ) {
                    $str = trim($tmp[$i]);
                    if( $str ) $stylesheet->add_media_type($str);
                }
            }

            if( $css['mediaquery'] ) {
                $stylesheet->set_media_query(trim($css['mediaquery']));
            }
            // save the stylesheet and add it to the design.
            $stylesheet->save();
            $design->add_stylesheet($stylesheet);
        }

        // expand templates
        $tpl_recs = $this->get_template_list();
        foreach( $tpl_recs as &$tpl ) {
            $template = new CmsLayoutTemplate();
            $template->set_name($tpl['newname']);
            if( !empty($tpl['desc']) ) {
                $template->set_description($tpl['desc']); //TODO any relocation ?
            }
            // relocate any uploads or designs usage in there
            $content = preg_replace(
              ['~\{uploads_url\}(/designs)?(?!/images)~',
               '~\{root_url\}/uploads(?!/images)~'],
//            ['{assets_root}/designs',
//             '{assets_root}/designs'],
              ['{themes_root}',
               '{themes_root}'],
            $tpl['data']);
            // substitute URL keys for the values.
            foreach( $this->_file_map as $key => &$rec ) {
                if( startswith($key,'__URL,,') ) {
                    // handle URL keys... handles image links etc.
                    if( !isset($rec['tpl_url']) ) continue;
                    $content = str_replace($key,$rec['tpl_url'],$content);
                }
                elseif( startswith($key,'__CSS,,') ) {
                    // handle CSS keys... for things like {cms_stylesheet name='xxxx'}
                    if( !isset($rec['value']) ) continue;
                    $content = str_replace($key,$rec['value'],$content);
                }
                elseif( startswith($key,'__TPL,,') ) {
                    // handle TPL keys... for things like {include file='xxxx'}
                    // or calling a module with a specific template.
                    if( !isset($rec['value']) ) continue;
                    $content = str_replace($key,$rec['value'],$content);
                }
            }
            unset($rec);

            // substitute other tpl keys in this content
            foreach( $tpl_recs as $tpl2 ) {
                if( $tpl['key'] == $tpl2['key'] ) continue;
                $content = str_replace($tpl2['key'],$tpl2['newname'],$content);
            }

            $template->set_content($content);

            // substitute CSS keys for their values.  This should handle
            // template type:
            // - try to find the template type
            // - if not, set the type to 'generic'.
            try {
                $typename = $tpl['type_originator'].'::'.$tpl['type_name'];
                $type_obj = CmsLayoutTemplateType::load($typename);
                $template->set_type($type_obj);
            }
            catch( CmsException $e ) {
                // should log something here.
                $type_obj = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::generic');
                $template->set_type($type_obj);
            }

            if( $owner_id > 0 ) $template->set_owner( $owner_id );
            $template->save();
            $tpl['newname'] = $template->get_name();
            $design->add_template($template);
        }
        unset($tpl);

        $design->save();
    } // function
}
