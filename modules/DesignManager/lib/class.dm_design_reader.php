<?php
#-------------------------------------------------------------------------
# Module DesignManager class dm_design_reader
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
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

class dm_design_reader extends dm_reader_base
{
    private $_xml;
    private $_scanned = FALSE;
    private $_raw_design_info = [];
    private $_tpl_info = [];
    private $_css_info = [];
    private $_file_map = [];
    private $_new_design_description;
    private $_versionid = 0;
//  private $_pages_info;
//  private $_pageprops_info;
//  private $_settings_info; // module and/or global

    public function __construct($fn)
    {
        parent::__construct($fn);
        $this->_xml = new dm_xml_reader();
        if( !$this->_xml->open($fn) ) {
            throw new RuntimeException("Design reader failed to open $fn");
        }
    }

    public function validate()
    {
        $this->_xml->SetParserProperty(XMLReader::VALIDATE,TRUE);
        while( $this->_xml->read() ) {
            if( !$this->_xml->isValid() ) {
                throw new RuntimeException('Invalid XML file');
            }
        }
        // it validates.
    }

    private function _scan()
    {
        if( $this->_scanned ) {
            return;
        }
        $in = [];
        $cur_key = '';

        $__get_in = function() use (&$in) {
            if( $in ) { return $in[count($in) - 1]; } //PHP 8.5+ array_last()
            return '';
        };

        while( $this->_xml->read() ) {
            switch( $this->_xml->nodeType ) {
            case XmlReader::ELEMENT:
                switch( $this->_xml->localName ) {
                case 'file':
                case 'template':
                case 'stylesheet':
                case 'requires':
                case 'design':
                    $in[] = $this->_xml->localName;
                    break 2;
                //TODO handle errors in the following
                case 'name':
                    if( $__get_in() != 'design' ) {
                        break 2; // validity error
                    }
                    $name = $this->_xml->localName;
                    $this->_xml->read();
                    $this->_raw_design_info[$name] = $this->_xml->value;
                    break 2;

                case 'dtdversion':
                    if( $__get_in() != 'design' ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $str = $this->_xml->value;
                    if( $str ) {
                        $t = [10000,100,1]; // aka 10**(4-$i*2)
                        $n = 0;
                        $parts = explode('.',$str);
                        for( $i = 0; $i < 3; $i++ ) {
                            if( isset($parts[$i]) ) {
                                $d = preg_replace('/^.*?(\d{1,2}).*$/','$1',$parts[$i]);
                                $n += (int)$d * $t[$i];
                            }
                            else {
                                break;
                            }
                        }
                        $this->_versionid = $n;
                    }
                    break 2;

                case 'description':
                case 'generated':
                case 'cmsversion':
                case 'version':
                case 'notes':
                    if( $__get_in() != 'design' ) {
                        break 2; // validity error
                    }
                    $name = $this->_xml->localName;
                    $this->_xml->read();
                    $this->_raw_design_info[$name] = $this->_xml->value;
                    break 2;

                case 'tkey':
                    if( $__get_in() != 'template' ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $cur_key = $this->_xml->value;
                    $this->_tpl_info[$cur_key] = ['key' => $cur_key];
                    break 2;

                case 'tname':
                    if( $__get_in() != 'template' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_tpl_info[$cur_key]['name'] = $this->_xml->value;
                    break 2;

                case 'tdesc':
                    if( $__get_in() != 'template' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_tpl_info[$cur_key]['desc'] = $this->_xml->value;
                    break 2;

                case 'tdata':
                    if( $__get_in() != 'template' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_tpl_info[$cur_key]['data'] = $this->_xml->value;
                    break 2;

                case 'ttype_originator':
                case 'ttype_name':
                    if( $__get_in() != 'template' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $key = $this->_xml->localName;
                    $this->_xml->read();
                    $this->_tpl_info[$cur_key][$key] = $this->_xml->value;
                    break 2;

                case 'csskey':
                    if( $__get_in() != 'stylesheet' ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $cur_key = $this->_xml->value;
                    $this->_css_info[$cur_key] = ['key' => $cur_key];
                    break 2;

                case 'cssname':
                    if( $__get_in() != 'stylesheet' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_css_info[$cur_key]['name'] = $this->_xml->value;
                    break 2;

                case 'cssdesc':
                    if( $__get_in() != 'stylesheet' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_css_info[$cur_key]['desc'] = $this->_xml->value;
                    break 2;

                case 'cssdata':
                    if( $__get_in() != 'stylesheet' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_css_info[$cur_key]['data'] = $this->_xml->value;
                    break 2;

                case 'cssmediatype':
                    if( $__get_in() != 'stylesheet' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_css_info[$cur_key]['mediatype'] = $this->_xml->value;
                    break 2;

                case 'cssmediaquery':
                    if( $__get_in() != 'stylesheet' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_css_info[$cur_key]['mediaquery'] = $this->_xml->value;
                    break 2;

                case 'fkey':
                    if( $__get_in() != 'file' ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $cur_key = $this->_xml->value;
                    $this->_file_map[$cur_key] = ['key' => $cur_key];
                    break 2;

                case 'fvalue':
                    if( $__get_in() != 'file' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_file_map[$cur_key]['value'] = $this->_xml->value;
                    break 2;

                case 'binary':
                    if( $__get_in() != 'file' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_file_map[$cur_key]['binary'] = $this->_xml->value;
                    break 2;

                case 'fdata':
                    if( $__get_in() != 'file' || !$cur_key ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_file_map[$cur_key]['data'] = $this->_xml->value;
                    if( $this->_versionid < 10800 ) {
                        //old-format 'fdata' values are not decoded upstream, so always base64_encode()'d here
                        $this->_file_map[$cur_key]['binary'] = 1;
                    }
                    break 2;

                case 'rname':
                    if( $__get_in() != 'requires' ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $cur_key = $this->_xml->value;
                    break 2;

                case 'rdata':
                    if( $__get_in() != 'requires' ) {
                        break 2; // validity error
                    }
                    $this->_xml->read();
                    $this->_raw_design_info['requires'][$cur_key] = $this->_xml->value;
                    $cur_key = '';
                    break 2;

                } // ELEMENT localName switch
                break;

            case XmlReader::END_ELEMENT:
                switch( $this->_xml->localName ) {
                case 'file':
                case 'template':
                case 'stylesheet':
                case 'requires':
                case 'design':
                    if( $in ) {
                        array_pop($in);
                    }
                    $cur_key = '';
                    break 2;
                } // END_ELEMENT localName switch
                break;
            } // nodeType switch
        } // while read
        $this->_scanned = TRUE;
    }

    public function get_design_info()
    {
        $this->_scan();
        return $this->_raw_design_info;
    }

    private function set_new_description($description = '')
    {
        $this->_new_design_description = $description;
    }

    public function get_template_list()
    {
        $this->_scan();
        $out = [];
        foreach( $this->_tpl_info as $key => $one ) {
            //c.f._file_map[] properties recorded during validate_template_names()
            $rec = ['key' => $key];
            $rec['name'] = $one['name'];
            $rec['newname'] = CmsLayoutTemplate::generate_unique_name($rec['name']);
            $rec['desc'] = $one['desc'];
            $rec['data'] = $one['data'];
            $rec['type_originator'] = $one['ttype_originator'];
            $rec['type_name'] = $one['ttype_name'];
            $out[] = $rec;
        }
        return $out;
    }

    public function get_stylesheet_list()
    {
        $this->_scan();
        $out = [];
        foreach( $this->_css_info as $key => $one ) {
            //c.f. _file_map[] properties recorded during validate_stylesheet_names()
            $rec = ['key' => $key];
            $rec['name'] = $one['name'];
            $rec['newname'] = CmsLayoutStylesheet::generate_unique_name($rec['name']);
            $rec['desc'] = $one['desc'];
            $rec['data'] = $one['data'];
            $rec['mediatype'] = $one['mediatype'];
            $rec['mediaquery'] = $one['mediaquery'];
            $out[] = $rec;
        }
        return $out;
    }

    private function validate_template_names()
    {
        $this->_scan();

        $templates = CmsLayoutTemplate::template_query(['as_list'=>1]);
        $tpl_names = array_values($templates);

        foreach( $this->_file_map as $key => &$rec ) {
            if( !startswith($key,'__TPL,,') ) continue;
            if( in_array($rec['value'],$tpl_names) ) {
                // gotta come up with a new name
                // it must conform to CmsAdminUtils::ITEMNAME_REGEX
                // and with CmsLayoutStyleSheet::generate_unique_name($orig_name) ?
                // see $this->get_template_list()
                $orig_name = $rec['value'];
                for( $n = 2; $n < 12; $n++ ) {
                    $new_name = "$orig_name $n";
                    if( !in_array($new_name,$tpl_names) ) {
                        $rec['old_value'] = $orig_name;
                        $rec['value'] = $new_name;
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

        foreach( $this->_file_map as $key => &$rec ) {
            if( !startswith($key,'__CSS,,') ) continue;
            if( in_array($rec['value'],$css_names) ) {
                // gotta come up with a new name
                // it must conform to CmsAdminUtils::ITEMNAME_REGEX
                // and with CmsLayoutStyleSheet::generate_unique_name($orig_name) ?
                // see $this->get_stylesheet_list()
                $orig_name = $rec['value'];
                for( $n = 2; $n < 12; $n++ ) {
                    $new_name = "$orig_name $n";
                    if( !in_array($new_name,$css_names) ) {
                        $rec['old_value'] = $orig_name;
                        $rec['value'] = $new_name;
                        break;
                    }
                }
                if( $n == 12 ) {
                    throw new RuntimeException('Could not determine a new name for stylesheet '.$orig_name);
                }
            }
        }
        unset($rec);
    }

    protected function get_destination_dir()
    {
        $config = cmsms()->GetConfig();
        $name = $this->get_new_name();
        $dirnm = dm_utils::munge_name_to_dir($name);
        $dir = cms_join_path($config['themes_path'],$dirnm);
        if( !is_dir($dir) ) {
            @mkdir($dir,0777,TRUE);
            if( !is_dir($dir) || !is_writable($dir) ) {
                throw new RuntimeException('Could not create, or could not write in, directory '.$dir);
            }
            else {
                touch($dir.DIRECTORY_SEPARATOR.'index.html');
            }
        }
        return $dirnm;
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

        if( !$description ) {
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

        $design->set_description($description);

        $config = cmsms()->GetConfig();

        // expand URL items to actual files
        // don't have to worry about duplicated filenames (hopefully)
        // because the destinaton directory is unique
        foreach( $this->_file_map as $key => &$rec ) {
            if( !startswith($key,'__URL,,') ) {
                continue;
            }
            //TODO if no associated content e.g. for a __TPL or __CSS key
            $destfile = cms_join_path($config['themes_path'],$destdir,$rec['value']);
            $parent = dirname($destfile);
            if( !is_dir($parent) ) {
                if( !is_file($parent) ) {
                    @mkdir($parent,0777,TRUE); // TODO relevant perms
                    @touch($parent.DIRECTORY_SEPARATOR.'index.html');
                }
                else {
                    throw new RuntimeException('Could not create, or could not write in, directory '.$parent);
                }
            }
            if ( empty($rec['binary']) ) {
                file_put_contents($destfile,$rec['data']);
            }
            else {
                file_put_contents($destfile,base64_decode($rec['data']));
            }
            touch($parent.DIRECTORY_SEPARATOR.'index.html');
            $base = rawurlencode($rec['value']);
            $base = str_replace(['%2F','%5C'],['/','/'],$base);
            $rec['tpl_url'] = "{themes_root}/$destdir/$base";
            $rec['css_url'] = "[[themes_root]]/$destdir/$base";
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

            //TODO process embedded css aliases like __CSS* ?
            // substitute other tpl keys in this content
            foreach( $tpl_recs as $tpl2 ) {
                if( $tpl['key'] == $tpl2['key'] ) continue;
                $content = str_replace($tpl2['key'],$tpl2['newname'],$content);
            }

            $template->set_content($content);

            // try to find the template type
            // if N/A, set the type to 'generic'.
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

//TODO check any requires

        $design->save();
    }
}
