<?php
namespace FilePicker;

use cms_config;
use LogicException;
use function startswith;

class PathAssistant
{
    private $_topdir;
    private $_topurl;

    public function __construct(cms_config $config, $topdir)
    {
        if( !$topdir || !is_dir($topdir) ) throw new LogicException('Invalid topdir passed to '.__METHOD__);
        if( !$this->is_relative_to( $topdir, $config['root_path'] ) ) throw new LogicException('Invalid topdir passed to '.__METHOD__);

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
            $tmp = rtrim($config['root_url'], ' /'); // prob. irrelevant, but just in case
            $this->_topurl = $tmp.'/'.strtr($config['assets_dir'], '\\', '/');
        }
        else if( $this->is_relative_to( $this->_topdir, $config['root_path']) ) {
            $rel_url = $this->to_relative_sub( $this->_topdir, $config['root_path'], TRUE );
            $this->_topurl = $config['root_url'];
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

    protected function to_relative_sub( $path_a, $path_b, $forurl = FALSE )
    {
        if( !$path_a || !$path_b ) throw new LogicException('Invalid path(s)_a passed to '.__METHOD__);
        $path_a = realpath( $path_a );
        $path_b = realpath( $path_b );
        if( !is_dir($path_a) && !is_file($path_a) ) throw new LogicException('Invalid path_a passed to '.__METHOD__.': '.$path_a);
        if( !is_dir($path_b) ) throw new LogicException('Invalid path_b passed to '.__METHOD__.': '.$path_b);

        if( !$this->is_relative_to( $path_a, $path_b ) ) throw new LogicException("$path_a is not relative to $path_b");
        $out = substr($path_a, strlen($path_b));
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

    //$patha, $path_b = filepath string (suitable for realpath) or falsy
    public function is_relative_to( $path_a, $path_b )
    {
        if( $path_a ) {
            $path_a = realpath( $path_a );
        }
        else {
            return FALSE;
        }
        if( $path_b ) {
            $path_b = realpath( $path_b );
        }
        if( !$path_a || !$path_b ) {
            return FALSE;
        }
        return startswith( $path_a, $path_b);
    }

    //$path = filepath string (suitable for realpath) or falsy
    public function is_relative( $path )
    {
        return $this->is_relative_to( $path, $this->_topdir );
    }

    //returns filepath string
    public function to_relative( $path )
    {
        return $this->to_relative_sub( $path, $this->_topdir, FALSE );
    }

    //$relative = filepath string (with leading separator or not) or empty
    //return filepath string
    public function to_absolute( $relative )
    {
        //TODO handle $relative already an absolute path
        //if $relative && preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~',$relative) throw? return $relative ?
        $relative = ltrim((string)$relative,' \\/');
        if( $relative ) {
            $relative = strtr($relative,'\\/',DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR); // just in case
            return $this->_topdir.DIRECTORY_SEPARATOR.$relative;
        }
        return $this->_topdir;
    }

    //$relative = filepath sub-string or URL sub-string (with leading separator or not) or empty
    //return URL string
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

    //$relative = filepath [sub-]string (with leading separator or not) or empty
    public function is_valid_relative_path( $relative )
    {
        $relative  = trim((string)$relative);
        if( $relative && preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~',$relative) ) {
            $absolute = $relative;
        }
        else {
            $absolute = $this->to_absolute( $relative ); //NOTE forces TRUE return always
        }
        return $this->is_relative( $absolute );
    }
} // end of class
