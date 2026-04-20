<?php
#-------------------------------------------------------------------------
# Module DesignManager class dm_design_exporter
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
final class dm_design_exporter
{
    const DTD_VERSION = '1.8';

    private $_advice = ''; // runtime addition(s) to recorded notes
    private $_css_list;
    private $_description;
    private $_design;
    private $_files;
    private $_modnames; // currently-recognised modules
    private $_requires = []; // modules tags cmsmsversion etc dependencies - each as [name => possible op + version]
    private $_tpl_list;
//  private $_pages_list;
//  private $_pageprops_list;
//  private $_subcss_list; // @import stylesheets
//  private $_subtpl_list;  // {include} {extends} {cms_module X template='QQ'} etc
//  private $_settings_list; // module and/or global

    // the exported *key values are effectively func(itemtype+corresponding data),
    // used for sorting before export and restoration during reading
    private $_dtd = <<<EOT
<!DOCTYPE design [
 <!ELEMENT design (dtdversion?,cmsversion,name,version?,description,notes?,generated,requires*,template*,stylesheet*,file*)>
 <!ELEMENT dtdversion (#PCDATA)>
 <!ELEMENT cmsversion (#PCDATA)>
 <!ELEMENT name (#PCDATA)>
 <!ELEMENT description (#PCDATA)>
 <!ELEMENT version (#PCDATA)>
 <!ELEMENT notes (#PCDATA)>
 <!ELEMENT generated (#PCDATA)>
 <!ELEMENT requires (rname,rdata?)>
 <!ELEMENT rname (#PCDATA)>
 <!ELEMENT rdata (#PCDATA)>
 <!ELEMENT template (tkey,tname,tdesc,tdata,ttype_originator,ttype_name)>
 <!ELEMENT tkey (#PCDATA)>
 <!ELEMENT tname (#PCDATA)>
 <!ELEMENT tdesc (#PCDATA)>
 <!ELEMENT tdata (#PCDATA)>
 <!ELEMENT ttype_originator (#PCDATA)>
 <!ELEMENT ttype_name (#PCDATA)>
 <!ELEMENT stylesheet (csskey,cssname,cssdesc,cssdata,cssmediatype,cssmediaquery)>
 <!ELEMENT csskey (#PCDATA)>
 <!ELEMENT cssname (#PCDATA)>
 <!ELEMENT cssdesc (#PCDATA)>
 <!ELEMENT cssdata (#PCDATA)>
 <!ELEMENT cssmediatype (#PCDATA)>
 <!ELEMENT cssmediaquery (#PCDATA)>
 <!ELEMENT file (fkey,fvalue,binary?,fdata?)>
 <!ELEMENT fkey (#PCDATA)>
 <!ELEMENT fvalue (#PCDATA)>
 <!ELEMENT binary (#PCDATA)>
 <!ELEMENT fdata (#PCDATA)>
]>

EOT;

    public function __construct(CmsLayoutCollection $design)
    {
        $this->_design = $design;
    }

    public function get_xml()
    {
        $this->parse_related_files(); // do this 1st, to simplify url-processing in css etc
        $this->parse_templates();
        $this->parse_template_tags(); // if intra-tag template-names are not suitably handled in _parse_tpl_urls()
        $this->parse_stylesheets();

        $dname = $this->_design->get_name();
        $space = 'dm'.hash('crc32',$dname);
        $cache = $_SESSION[$space];
        $vers = (!empty($cache['version'])) ? $cache['version'] : $this->_design->get_version();
        $desc = (!empty($cache['description'])) ? $cache['description'] : $this->_design->get_description();
        $notes = (!empty($cache['notes'])) ? $cache['notes'] : '';
        $reqs = (!empty($cache['requires'])) ? $cache['requires'] : $this->_design->get_requires(4); // as array
        if( $this->_requires ) {
            $reqs = array_merge($this->_requires, $reqs);
        }
        if( $reqs ) {
            ksort($reqs, SORT_NATURAL);
        }
        if( $this->_advice ) { $notes .= "\n". $this->_advice; }

        // this generates a humungous pile of text in memory - is there a better way?
        $output = '<?xml version="1.0" encoding="UTF-8"?>';
        $output .= $this->_dtd;
        $output .= $this->_open_tag('design',0);
        $output .= $this->_output('dtdversion',self::DTD_VERSION);
        $output .= $this->_output('cmsversion',CMS_VERSION);
        $output .= $this->_output('name',$dname);
        $output .= $this->_output('version',$vers);
        $output .= $this->_output_data('description',$desc);
        $output .= $this->_output_data('notes',$notes);
        $output .= $this->_output('generated',time());

        foreach( $reqs as $dep => $detail ) {
            if( $detail ) {
                $detail = implode(' ',$detail);
            }
            $output .= $this->_output_req($dep,$detail);
        }

        foreach( $this->_tpl_list as $rec ) {
            $output .= $this->_output_template($rec['obj'],$rec['name'],1);
        }

        foreach( $this->_css_list as $rec ) {
            $output .= $this->_output_stylesheet($rec['obj'],$rec['name'],1);
        }

        if( !empty($this->_files) ) {
            // sort on 'type' (CSS, URL etc) asc and then value asc
            $arr = &$this->_files;
            uksort($this->_files,function($a,$b) use($arr) {
                $n = strncmp($a,$b,5);
                if( $n == 0 ) {
                    $n = strcmp($arr[$a],$arr[$b]);
                }
                return ($n > 0) ? 1 : (($n < 0) ? -1 : 0);
            });
            unset($arr);
            foreach( $this->_files as $key => $value ) {
                $output .= $this->_output_file($key,$value);
            }
        }

        //TODO other things e.g. global- and/or module-settings, pages
        unset($_SESSION[$space]);

        $output .= $this->_close_tag('design',0);
        return $output;
    }

    private function log($msg,$sysmsg='')
    {
        if( $this->_advice ) { $this->_advice .= "\nExport: $msg"; }
        else { $this->_advice = "Export: $msg"; }
        $dname = $this->_design->get_name();
        audit($this->_design->get_id(),'Export design '.$dname,rtrim($msg.' '.$sysmsg));
    }

    private function _get_signature($fn,$type = 'URL')
    {
        if( isset($this->_files) && is_array($this->_files) ) {
            if( ($sig = array_search($fn,$this->_files)) !== FALSE ) {
                return $sig;
            }
        }
        else {
            $this->_files = [];
        }
        $sig = "__{$type},,".hash('md4',$fn).'__'; // md4-hashing is better-distributed than md5
        $this->_files[$sig] = $fn;
        return $sig;
    }

    /**
     * Compare the specified url with the site-root url
     * @param string $url
     * @return bool indicating (sufficient) match between the two
     */
    private function _is_site_url($url)
    {
        static $root_url = null;

        $chk_url = new cms_url($url);
        if( !$chk_url ) {
            return FALSE;
        }
        if( $root_url == null ) {
            $root_url = new cms_url(CMS_ROOT_URL);
        }
        if( $chk_url->get_host() && $chk_url->get_host() != $root_url->get_host() ) { return FALSE; }
        if( $chk_url->get_scheme() && $chk_url->get_scheme() != $root_url->get_scheme() ) { return FALSE; }
        if( $chk_url->get_port() != $root_url->get_port() ) { return FALSE; }
        $pr = $root_url->get_path();
        $pc = $chk_url->get_path();
        return ($pr == $pc || startswith($pc,$pr));
    }

    /**
     * Detect, record and substute an alias for urls in the supplied string
     * No checking for url-relevance e.g. for something external to the design
     *
     * @param string $content
     * @return string possibly-modified content
     */
    private function _parse_css_urls($content)
    {
        $ob = $this;
        $out = preg_replace_callback('/url\s*\(\s*(["\']?)(.+?)\1\s*\)/is',
            function($matches) use ($ob) {
                $url = $matches[2];
                if( strncasecmp($url,'data:',5) != 0 ) { // stet data url
                    if( !preg_match('/\[\[\s*\$/',$url) ) { // stet url having Smarty variable (which we can't (yet?) intepret for content retrieval)
                        if( !$ob->_is_site_url($url) ) {
                            $ob->log('Detected offsite url '.$url);
                        }
                        if( !startswith($url,'http') || startswith($url,CMS_ROOT_URL) || startswith($url,'[[root_url]]') ) {
                            $sig = $ob->_get_signature($url);
                            return "url(\"$sig\")";
                        }
                    }
                    else {
                        $ob->log("Failed to process stylesheet url '$url'");
                    }
                }
                return 'url('.$matches[1].$url.$matches[1].')';
            },$content);
        return $out;
    }

    /**
     * Detect, record and substitute an alias for urls in the supplied string
     * No check for url-relevance i.e. something necessary for the design
     *
     * @param string $content
     * @return string possibly-modified content
     */
    private function _parse_tpl_urls($content)
    {
        $ob = $this;
        foreach( ['href','src','url','to'] as $type ) {
            $content = preg_replace_callback('/'.$type.'\s*=\s*(["\'`])([a-z0-9:?=&@._\-\/]+?)\1/is',
                function($matches) use ($ob,$type) {
                    //TODO $matches[2] == {file_url ...} etc prob. on-site
                    if( preg_match('/(\[\[|\{)\s*\$/',$matches[2]) ) {
                        // Smarty variable used, can't process it here
                        $ob->log('Cannot parse url '.$matches[2],'Contains Smarty variable(s)');
                        return $matches[0];
                    }
                    elseif( $ob->_is_site_url($matches[2]) ) {
                        $sig = $ob->_get_signature($matches[2]);
                        return "$type=\"$sig\"";
                    }
                    else {
                        $ob->log('Detected offsite url '.$matches[2]);
                        return $matches[0];
                    }
               },$content);
        }
        return $content;
    }

    private function parse_stylesheets()
    {
        if( !isset($this->_css_list) ) {
            $this->_css_list = [];

            $csslist = $this->_design->get_stylesheets();
            if( is_array($csslist) && count($csslist) > 0 ) {
                foreach( $csslist as $css_id ) {
                    $css_ob = CmsLayoutStylesheet::load($css_id);

                    //TODO interpret any @import rule(s) in stylesheet content
                    $new_content = $this->_parse_css_urls($css_ob->get_content());
                    $sig = $this->_get_signature($css_ob->get_name(),'CSS');
                    $new_css_ob = clone $css_ob;
                    $new_css_ob->set_name($sig);
                    $new_css_ob->set_content($new_content);

                    if( !isset($this->_css_list) || !is_array($this->_css_list) ) $this->_css_list = [];
                    $this->_css_list[] = ['name'=>$css_ob->get_name(),'obj'=>$new_css_ob];
                }
            }
        }
    }

    /**
     * Add data for the specified template to the _tpl_list array
     * @param mixed $name object | string
     * @return string
     */
    private function _add_template($name)
    {
         if( is_object($name) ) {
             $tpl_ob = $name;
             $name = $tpl_ob->get_name();
         }
         else {
             $tpl_ob = CmsLayoutTemplate::load($name);
         }
/* URL possibilities in content:
hlml element attr for <base/> <a/> <audio/> <cite/> <embed/> <form/> <frame/>
<iframe/> <img/> <link/> <meta/> <object/> <param/> <q/> <source/> <video/> txt/bare
a href="{file_url file=...GENERATES AN URL
img src="{thumbnail_url file=...GENERATES AN URL
{embed url=...
{form_start url=... 
{image src=...
{redirect_url to=...
non-core tags ?
*/
         $new_content = $this->_parse_tpl_urls($tpl_ob->get_content());
         $sig = $this->_get_signature($tpl_ob->get_name(),'TPL');
         $new_tpl_ob = clone $tpl_ob;
         $new_tpl_ob->set_name($sig);
         $new_tpl_ob->set_content($new_content);

         if( !is_array($this->_tpl_list) ) $this->_tpl_list = [];
         $this->_tpl_list[$sig] = ['name'=>$name,'obj'=>$new_tpl_ob];
         return $sig;
    }

    private function parse_templates()
    {
        if( !isset($this->_tpl_list) ) {
            $this->_tpl_list = [];

            $idlist = $this->_design->get_templates();
            if( $idlist && is_array($idlist) ) {
                $tpllist = CmsLayoutTemplate::load_bulk($idlist);
                if( count($idlist) != count($tpllist) ) {
                    throw new RuntimeException('Internal error... could not directly load all of the templates associated with this design');
                }
                foreach( $tpllist as $tpl ) {
                    $this->_add_template($tpl);
                }
            }
        }
    }

    /**
     * Process missed template-names in {Smarty tags} in identified templates
     * @since 1.3
     */
    private function parse_template_tags()
    {
        if( !empty($this->_tpl_list) ) {
            $obj = $this;
            $dname = $this->_design->get_name();
            foreach( $this->_tpl_list as $sig => $data ) {
                $added = [];
                $modified = [];
                $content = $data['obj']->get_content();
                //check each tag in $content and adjust as needed TODO {*_url} tags
                $test = preg_replace_callback('~(?:\{(?![\s*/$]))(.+?)(?:(?<![\s*\\\\])\})~s',function($m) use($obj,$dname,&$added,&$modified) {
                    if( preg_match_all(
                        '/(?:([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)\s+)?(["\']?)(\S+)\2\s*=\s*(([^"\'\s]+)|(["\']?)(.+?)\6)/',
                        $m[1],$attrs,PREG_SET_ORDER) ) {
                        $ret = $m[0];
                        foreach( $attrs as $one ) {
                            $tagname = $one[1];
                            // if $tagname is something that needs special attention, process it
                            switch( $tagname ) { //TODO ? case '':
                                case 'include':
/* possibilities
{include file='page_header.tpl'}
{include file="$tpl_name.tpl"}
{include 'sub_template.tpl'}
{include 'sub_template.tpl' foo}
{include 'sub_template.tpl' foo=bar}
{include 'cms_template:Template Name'}
{include "cms_template:$tpl_name"}
*/
                                $obj->log("Did not process tag $ret");
                                continue 2;
//                              case 'global_content': deprecated treat like 'include'

                                case 'extends':
/* possibilities
{extends file='myproject.tpl'}
{extends file="$tpl_name.tpl"}
{extends 'parent.tpl'}
{extends "cms_template:$tpl_name"}
*/
                                $obj->log("Did not process tag $ret");
                                continue 2;

/*                              case 'cms_selflink':
attr posibilities
page
href
urlonly
image
*/
                                case 'cms_module':
                                if( preg_match('/template.*=.+/',$ret) || preg_match('/tpl.*=.+/',$ret) ) {
                                     $obj->log("Did not process tag $ret");
                                }
                                $n = array_search('module',array_column($attrs,3));
                                $modname = ($attrs[$n][5]) ?: $attrs[$n][7]; // [5] if unquoted, [7] if quoted
                                $obj->_requires[$modname] = []; // no op or version ?
                                continue 2;

                                default:
                                if( !isset($obj->_modnames) ) {
                                    $obj->_modnames = ModuleOperations::get_instance()->GetInstalledModules(TRUE);
                                }
                                if( in_array($tagname, $obj->_modnames) ) {
                                    if( preg_match('/template.*=.+/',$ret) || preg_match('/tpl.*=.+/',$ret) ) {
                                        $obj->log("Did not process tag $ret");
                                    }
                                    $obj->_requires[$tagname] = []; // no op or version ?
                                    continue 2;
                                }
                                //check the value and related-content of each relevant attr
                                $val = ($one[5] !== '') ? $one[5] : $one[7]; // [5] if unquoted, [7] if quoted
                                if( $val || is_numeric($val) ) {
                                    $key = $one[3];
                                    if( $key[0] == '$' ) {
                                        continue 2;
                                    }
                                    $lk = strlen($key);
                                    if( stripos($key,'template') !== FALSE ||
                                        stripos($key,'tpl') !== FALSE ) {
                                        if( ($n = array_search($val,array_column($obj->_tpl_list,'name'))) !== FALSE ) {
                                            $sig2 = key(array_slice($obj->_tpl_list,$n,1));
                                            $ret = str_replace($one[4].$val.$one[4],"'$sig2'",$ret); // offset useful here ? e.g. could be > 1 replacement
                                            $modified[$sig2] = $obj->_tpl_list[$sig2];
                                        }
                                        else {
                                            $here = 1; //TODO anything here?
                                        }
                                    }
                                    elseif( ($n = array_search($val,array_column($obj->_tpl_list,'name'))) !== FALSE ) {
                                        $here = 1; // any relevant replacement as quoted-signature, $modified[] = X
                                    }
                                    elseif( strpos($dname,$val) !== FALSE ) {
                                        $here = 1; // add to $obj->_tpl_list if relevant, $added[] = X (named-template might be unrelated to design being processed
                                    }
                                }
                            } // switch
                        }
                        return $ret;
                    }
                    return $m[0];
                }, $content);
                foreach( $modified as $sig2 => $data ) {
                    $here = 1; // content already changed in preg_replace_callback()
                }
                foreach( $added as $tplname ) {
                    $sig2 = $this->_get_signature($tplname,'TPL');
                    $this->_tpl_list[$sig2] = ['name'=>$tplname,'obj'=>$TODOobj];
                }
            }
        }
    }

    /**
     * Append associated files to $this->_files
     * @since 1.2
     */
    private function parse_related_files()
    {
        $config = cms_config::get_instance();
        $name = $this->_design->get_name();
        $dirnm = dm_utils::munge_name_to_dir($name);
        $fp = cms_join_path($config['themes_path'],$dirnm);
        if( is_dir($fp) ) {
            if( !isset($this->_files) ) {
                $this->_files = [];
            }
            $ob = $this;
            $pl = strlen(CMS_ROOT_PATH);
            $sep = DIRECTORY_SEPARATOR;
            //recursive closure c.f. get_recursive_file_list($fp,['index\.html'],-1,'FILES')
            $filer = function($dirpath) use($ob,$pl,$sep,&$filer) {
                $items = scandir($dirpath);
                if( $items ) {
                    foreach( $items as $iname ) {
                        if( !($iname == '.' || $iname == '..' || $iname == 'index.html') ) {
                            $sp = "$dirpath{$sep}$iname";
                            if( is_dir($sp) ) {
                                $filer($sp); // recurse
                                continue;
                            }
                            elseif( is_link($sp) ) {
                                $sp = readlink($sp);
                                if( $sp && is_dir($sp) ) {
                                    $filer($sp);
                                    continue;
                                }
                                elseif( !$sp ) {
                                    continue; //TODO
                                }
                            }
                            $key = '__URL,,'.hash('md4',$iname).'__'; // md4-hashing is better-distributed than md5
                            $ob->_files[$key] = strtr(substr($sp,$pl),'\\','/'); // always record *NIX path-seps
                        }
                    }
                }
            };
            $filer($fp);
        }
    }

    private function _open_tag($elem,$lvl = 1)
    {
        $indent = str_repeat("\t",$lvl);
        return "$indent<$elem>\n";
    }

    private function _close_tag($elem,$lvl = 1)
    {
        $indent = str_repeat("\t",$lvl);
        return "$indent</$elem>\n";
    }

    private function _output($elem,$txt,$lvl = 1)
    {
        $indent = str_repeat("\t",$lvl);
        return "$indent<$elem>$txt</$elem>\n"; // assume simple text, no encoding issue to handle
    }

    private function _output_data($elem,$txt,$lvl = 1,$encode = FALSE)
    {
        if( $txt ) {
            if( $encode ) {
                $data = base64_encode($txt);
            }
            else {
                $encnow = mb_detect_encoding($txt,'auto',TRUE);
                if( !($encnow == 'ASCII' || $encnow == 'UTF-8') ) {
                    $old = $txt;
                    $txt = mb_convert_encoding($txt,'UTF-8',$encnow);
                    if( !$txt ) {
                        $txt = 'String encoding-conversion failure in'."\n{$old}";
                    }
                }
                $tmp = str_replace(']]>',']]/>',$txt); // $txt cannot include xml-ender ']]>'
                $data = '<![CDATA['.$tmp.']]>';
            }
        }
        else {
            $data = (string)$txt;
        }
        return $this->_output($elem,$data,$lvl);
    }

    private function _output_req($name,$detail,$lvl = 1)
    {
        $output = $this->_open_tag('requires',$lvl);
        $output .= $this->_output('rname',$name,$lvl+1);
        if( $detail ) {
            $output .= $this->_output_data('rdata',$detail,$lvl+1);
        }
        else {
            $output .= $this->_output('rdata','',$lvl+1);
        }
        $output .= $this->_close_tag('requires',$lvl);
        return $output;
    }

    private function _output_template(CmsLayoutTemplate $tpl,$name,$lvl = 1)
    {
        if( ($tid = $tpl->get_type_id()) == 0 ) {
            throw new RuntimeException('Cannot get template type for '.$name);
        }
        try {
            $type = CmsLayoutTemplateType::load($tid);
        }
        catch (Exception $e) {
            throw new RuntimeException('Cannot get template type for '.$name);
        }
        $orig = $type->get_originator();
        if( !isset($this->_modnames) ) {
            $this->_modnames = ModuleOperations::get_instance()->GetInstalledModules(TRUE);
        }
        if( in_array($orig, $this->_modnames) ) {
            // if $tpl snuk into _tpl_list remove it
            $sig = "__TPL,,".hash('md4',$name).'__'; // OR $tpl->get_name(), now a signature
            unset($this->_tpl_list[$sig]);
            $this->log("Did not process {$orig}-module template ".$name);
            $this->_requires[$orig] = []; // no op or version
            return '';
        }
        if( ($content = $tpl->get_content()) == '' ) {
            throw new RuntimeException('Cannot export empty template');
        }
        $output = $this->_open_tag('template',$lvl);
        $output .= $this->_output('tkey',$tpl->get_name(),$lvl+1);
        $output .= $this->_output('tname',$name,$lvl+1);
        $output .= $this->_output_data('tdesc',$tpl->get_description(),$lvl+1);
        $output .= $this->_output_data('tdata',$content,$lvl+1);
        $output .= $this->_output('ttype_originator',$orig,$lvl+1);
        $output .= $this->_output('ttype_name',$type->get_name(),$lvl+1);
        $output .= $this->_close_tag('template',$lvl);
        return $output;
    }

    private function _output_stylesheet(CmsLayoutStylesheet $css,$name,$lvl = 1)
    {
        if( $css->get_content() == '' ) {
            throw new RuntimeException('Cannot export empty stylesheet');
        }
        $output = $this->_open_tag('stylesheet',$lvl);
        $output .= $this->_output('csskey',$css->get_name(),$lvl+1);
        $output .= $this->_output('cssname',$name,$lvl+1);
        $output .= $this->_output_data('cssdesc',$css->get_description(),$lvl+1);
        $output .= $this->_output_data('cssdata',$css->get_content(),$lvl+1);
        $output .= $this->_output('cssmediatype',implode(',',$css->get_media_types()),$lvl+1);
        $output .= $this->_output_data('cssmediaquery',$css->get_media_query(),$lvl+1);
        $output .= $this->_close_tag('stylesheet',$lvl);
        return $output;
    }

    private function _output_file($key,$value,$lvl = 1)
    {
        if( !startswith($key,'__') || !endswith($key,'__') ) return ''; // invalid
        $p = strpos($key,',,');
        $nkey = substr($key,2,$p-2); // type

        $output = $this->_open_tag('file',$lvl);
        $output .= $this->_output('fkey',$key,$lvl+1);

        switch ($nkey) {
        case 'URL':
            // javascript file, image, font etc
            // might have Smarty tag(s), possibly including variable(s) which cannot be understood here.
            $ovalue = '';
            if( strpos($value,'[[') !== FALSE ) {
                if( !preg_match('/\[\[\s*\$/',$value) ) {
                    // Smarty css left-delimiter [[ but no variable
                    if (!isset($smarty) ) $smarty = cmsms()->GetSmarty();
                    $ol = $smarty->left_delimiter;
                    $or = $smarty->right_delimiter;
                    $smarty->left_delimiter = '[[';
                    $smarty->right_delimiter = ']]';
                    try {
                        $nvalue = $smarty->fetch('string:'.$value);
                    }
                    catch (Exception $e) {
                        //TODO revert to self-managed interpretation
                        $this->log('Smarty failed to parse url '.$value,$e->GetMessage());
                        $nvalue = '';
                        $ovalue = $value;
                    }
                    $smarty->left_delimiter = $ol;
                    $smarty->right_delimiter = $or;
                }
                else {
                    // Smarty variable used, can't process it here
                    $this->log('Cannot parse url '.$value,'Contains Smarty variable(s)');
                    $nvalue = ''; // no content-retrieval
                    $ovalue = $value;
                }
            }
            elseif( strpos($value,'{') !== FALSE ) {
                if( !preg_match('/\{\s*\$/',$value) ) { //js files should not be a problem here
                    // Smarty syntax left delimiter { but no variable
                    if (!isset($smarty) ) $smarty = cmsms()->GetSmarty();
                    try {
                        $nvalue = $smarty->fetch('string:'.$value);
                    }
                    catch (Exception $e) {
                        //TODO revert to self-managed interpretation
                        $this->log('Smarty failed to parse url '.$value,$e->GetMessage());
                        $nvalue = '';
                        $ovalue = $value;
                    }
                }
                else {
                    // Smarty variable used, can't process it here
                    $this->log('Cannot parse url '.$value,$e->GetMessage());
                    $nvalue = ''; // no content-retrieval
                    $ovalue = $value;
                }
            }
            else {
                $nvalue = $value;
            }

            if( $nvalue ) {
                // should be an absolute path or URL, or start with / (or \ if it's a path)
                // convert it to a relative filepath
                // TODO conform separators in $nvalue
                if( $nvalue[0] == '\\' || ($nvalue[0] == '/' && $nvalue[1] != '/') ) { // DIRECTORY_SEPARATOR either / or \
                    $fn = cms_join_path(CMS_ROOT_PATH,$nvalue);
                }
                elseif( startswith($nvalue,CMS_ROOT_URL) ) {
                    $fn = str_replace([CMS_ROOT_URL,'/'],[CMS_ROOT_PATH,DIRECTORY_SEPARATOR],$nvalue);
                }
                elseif( !startswith($nvalue,CMS_ROOT_PATH)) {
                    // assume it's relative to root
                    $fn = cms_join_path(CMS_ROOT_PATH,$nvalue);
                }
                if( !is_file($fn) ) {
                    $mod = cms_utils::get_module('DesignManager');
                    $this->log($mod->Lang('error_nophysicalfile',$value));
                    $output .= $this->_output('fvalue',$nvalue,$lvl+1);
                    break;
                }
                $data = file_get_contents($fn);
                $nname = $this->_design->get_name();
                if( ($p = strpos($nvalue,$nname)) !== FALSE ) {
                    $nvalue = substr($nvalue,$p + strlen($nname) + 1); // omits leading separator
                }
                else {
                    $nvalue = basename($nvalue); //TODO relative to something else ?
                }
                $output .= $this->_output('fvalue',$nvalue,$lvl+1);
                //base64-encode content of 'binary' files = images, fonts ... not for js etc
                $encode = !(endswith($fn,'.js') || endswith($fn,'.svg')); // TODO caseless checks
                if( $encode ) {
                    $output .= $this->_output('binary',1,$lvl+1,false);
                }
                $output .= $this->_output_data('fdata',$data,$lvl+1,$encode);
            }
            elseif( $ovalue ) {
                $output .= $this->_output('fvalue',$ovalue,$lvl+1);
            }
            else {
                $this->log('Cannot parse url '.$value,$e->GetMessage());
                return '';
            }
            break;

        case 'TPL':
            // template signature
            // just need the key and value
            $output .= $this->_output('fvalue',$value,$lvl+1);
            break;

        case 'CSS':
            // stylesheet signature
            // just need the key and value
            $output .= $this->_output('fvalue',$value,$lvl+1);
            break;

        case 'MM':
            // menu manager file template - probably never exist, these days
            // just need the key and value
            $output .= $this->_output('fvalue',$value,$lvl+1);
            break;

        default:
            break;
        }
        $output .= $this->_close_tag('file',$lvl);
        return $output;
    }
}
