<?php
/*
CMSMS FilePicker module class: PathAssistant
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file FilePicker.module.php applies to this file.
*/
// arguably this class is sensibly a FileManager thing ...
namespace FilePicker;

use cms_config;
use LogicException;
use function is_absolute_path;
use function startswith;

class PathAssistant
{
    private $_topdir;
    private $_topurl;

    public function __construct(cms_config $config, $topdir)
    {
        if( !$topdir || !is_dir($topdir) ) throw new LogicException('Invalid topdir passed to '.__METHOD__);
        if( !$this->is_relative_to( $topdir, CMS_ROOT_PATH ) ) throw new LogicException('Invalid topdir passed to '.__METHOD__);

        $topdir = rtrim($topdir,' \\/');
        $this->_topdir = trim($topdir);

        // look at relevant paths
        if( $this->is_relative_to( $this->_topdir, $config['image_uploads_path']) ) {
            $rel_url = $this->to_relative_sub( $this->_topdir, $config['image_uploads_path'], TRUE );
            $this->_topurl = $config['image_uploads_url'];
        }
        else if( $this->is_relative_to( $this->_topdir, $config['uploads_path']) ) {
            $rel_url = $this->to_relative_sub( $this->_topdir, $config['uploads_path'], TRUE );
            $this->_topurl = $config['uploads_url'];
        }
        else if( $this->is_relative_to( $this->_topdir, $config['admin_path']) ) {
            $rel_url = $this->to_relative_sub( $this->_topdir, $config['admin_path'], TRUE );
            $this->_topurl = $config['admin_url'];
        }
        else if( $this->is_relative_to( $this->_topdir, $config['assets_path']) ) {
            $rel_url = $this->to_relative_sub( $this->_topdir, $config['assets_path'], TRUE );
            $tmp = rtrim(CMS_ROOT_URL, ' /'); // prob. irrelevant, but just in case
            $this->_topurl = $tmp.'/'.strtr($config['assets_dir'], '\\', '/');
        }
        else if( $this->is_relative_to( $this->_topdir, CMS_ROOT_PATH) ) {
            $rel_url = $this->to_relative_sub( $this->_topdir, CMS_ROOT_PATH, TRUE );
            $this->_topurl = CMS_ROOT_URL;
        }
        else {
            $rel_url = '';
            $this->_topurl = '#'; // OR 'javascript:void(0)' ?
        }
        if ($rel_url) {
            $tmp = rtrim($this->_topurl, ' /'); // prob. irrelevant, but just in case
            $this->_topurl = $tmp.'/'.$rel_url;
        }
    }

    /**
     * Get the trailing portion of dir|file-path $path_a that is relative to (i.e. after) $path_b
     * @param mixed $path_a dir|file path string suitable for realpath() or falsy
     * @param mixed $path_b ibid
     * @param bool $forurl whether to return a relative-url instead of filepath. Default false.
     * @return string (without leading separator)
     * @throws LogicException if either path is missing or invalid or $path_a is not relative to $path_b
     */
    protected function to_relative_sub( $path_a, $path_b, $forurl = FALSE )
    {
        if( !$path_a || !$path_b ) { throw new LogicException('Invalid path(s) passed to '.__METHOD__); }
        $ra = realpath($path_a);
        if( !$ra || !(is_dir($ra) || is_file($ra)) ) { throw new LogicException('Invalid path_a passed to '.__METHOD__.': '.$path_a); }
        $rb = realpath($path_b);
        if( !$rb || !is_dir($rb) ) { throw new LogicException('Invalid path_b passed to '.__METHOD__.': '.$path_b); }
        if( $ra == $rb ) { return ''; }

        if( !$this->is_relative_to($ra, $rb) ) {
            throw new LogicException("$path_a is not relative to $path_b");
        }
        $out = substr($ra, strlen($rb));
        $out = ltrim($out, ' \\/');
        if( $forurl ) {
            $out = strtr($out, '\\', '/');
        }
        else {
            $out = strtr($out, '\\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR); // just in case
        }
        return $out;
    }

    public function get_top_url()
    {
        return $this->_topurl;
    }

    //@since 1.0.7
    public function get_top_dir()
    {
        return $this->_topdir;
    }

    /**
     * Report whether $path_a is relative to (i.e. starts with) $path_b
     * @param mixed $path_a filepath string suitable for realpath() or falsy
     * @param mixed $path_b ibid
     * @return bool
     */
    public function is_relative_to( $path_a, $path_b )
    {
        if( $path_a ) {
            $path_a = realpath($path_a);
        }
        if( !$path_a ) {
            return FALSE;
        }
        if( $path_b ) {
            $path_b = realpath($path_b);
        }
        if( !$path_b ) {
            return FALSE;
        }
        return startswith($path_a, $path_b);
    }

    /**
     * Report whether $path is relative to (i.e. starts with) this object's topdir property
     * @param mixed $path filepath string suitable for realpath() or falsy
     * @return bool
     */
    public function is_relative( $path )
    {
        return $this->is_relative_to($path, $this->_topdir);
    }

    /**
     * @param mixed $path filepath string suitable for realpath() or falsy
     * @return string filepath
     */
    public function to_relative( $path )
    {
        return $this->to_relative_sub($path, $this->_topdir, FALSE);
    }

    /**
     * @param mixed $relative filepath string suitable for realpath() maybe including '..' or falsy
     * @return string filepath maybe ending with '/..'
     */
    public function to_absolute( $relative )
    {
        //TODO handle $relative already an absolute path
//      if( $relative && is_absolute_path() or preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~',$relative) ) { return TODO; }
        $relative = ltrim((string)$relative,' \\/');
        if( $relative ) {
            $relative = strtr($relative,'\\/',DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR); // just in case
            $tmp = $this->_topdir.DIRECTORY_SEPARATOR.$relative;
            if( !\endswith($tmp, '..') ) {
                return $tmp;
            }
            return dirname($tmp,2);
        }
        return $this->_topdir;
    }

    /**
     * @param string $relative filepath sub-string or URL sub-string (with leading separator or not) or empty
     * @return string URL
     */
    public function relative_path_to_url( $relative )
    {
        $prefix = rtrim($this->_topurl,' /'); // prob. irrelevant, but just in case
        $relative = ltrim((string)$relative,' \\/');
        if( $relative ) {
            $relative = strtr($relative,'\\','/');
            return trim($prefix.'/'.$relative);
        }
        return $prefix;
    }

    /**
     * @param string $relative filepath [sub-]string (with leading separator or not) or empty
     * @return bool
     */
    public function is_valid_relative_path($relative)
    {
        $relative = trim((string)$relative);
        if( $relative && is_absolute_path($relative) ) {
            $absolute = $relative; // return FALSE ?
        }
        else {
            $absolute = $this->to_absolute($relative); //NOTE forces TRUE return always
        }
        return $this->is_relative_to($absolute,$this->_topdir);
    }
} // class
