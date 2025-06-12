<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: cms_filecache_driver
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# A class for caching data in files for CMSMS.
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE

/**
 * A cache driver to cache files using the filesystem.
 *
 * This driver stores files in the folder defined as TMP_CACHE_LOCATION,
 * it supports read and write locking, a settable cache lifetime,
 * hashed keys and groups so that filenames cannot be easily determined,
 * and automatic cleaning.
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell
 */
class cms_filecache_driver extends cms_cache_driver
{
    /**
     * @ignore
     */
    const LOCK_READ   = '_read';

    /**
     * @ignore
     */
    const LOCK_WRITE  = '_write';

    /**
     * @ignore
     */
    const LOCK_UNLOCK = '_unlock';

    /**
     * @ignore
     */
    const KEY_SERIALIZED = '__SERIALIZED__';

    /**
     * @var int
     */
    private $_lifetime = 7200;

    /**
     * @var bool
     */
    private $_locking = true;

    /**
     * @var bool
     */
    private $_blocking = false;

    /**
     * @var string
     */
    private $_cache_dir = TMP_CACHE_LOCATION;

    /**
     * @var bool
     */
    private $_auto_cleaning = false;

    /**
     * @var string
     */
    private $_group = '';

    /**
     * @var string
     * hash() algorithm
     */
    private $_algo;

    /**
     * Constructor
     *
     * Accepts an optional associative array of options with any/all of
     * the following:
     *   lifetime  => seconds (default 7200)
     *   locking   => boolean (default true)
     *   cache_dir => string (default TMP_CACHE_LOCATION)
     *   auto_cleaning => boolean (default false)
     *   blocking => boolean (default false)
     *   group => string (default '')
     * @param string $opts
     */
    public function __construct($opts = [])
    {
        if( $opts && is_array($opts) ) {
            $_keys = array('lifetime','locking','cache_dir','auto_cleaning','blocking','group');
            foreach( $opts as $key => $value ) {
                if( in_array($key,$_keys) ) {
                    $tmp = '_'.$key;
                    $this->$tmp = $value;
                }
            }
        }
        $this->_algo = (PHP_VERSION_ID >= 80100 && in_array('xxh64', hash_algos())) ? 'xxh64' : 'fnv164';
    }


    /**
     * Get a cached value
     * If $group is empty, the current group will be used
     *
     * @see cms_filecache_driver::set_group
     * @param string $key
     * @param string $group
     */
    public function get($key,$group = '')
    {
        if( !$group ) $group = $this->_group;

        $this->_auto_clean_files();
        $fn = $this->_get_filename($key,$group);
        $data = $this->_read_cache_file($fn);
        return $data;
    }


    /**
     * Clear all cached values from a group
     * If $group is empty, the current group will be used
     *
     * @see cms_filecache_driver::set_group
     * @param string $group
     */
    public function clear($group = '')
    {
        return $this->_clean_dir($this->_cache_dir,$group,false);
    }


    /**
     * Test if a cached value exists.
     * If $group is empty, the current group will be used
     *
     * @see cms_filecache_driver::set_group
     * @param string $key
     * @param string $group
     */
    public function exists($key,$group = '')
    {
        if( !$group ) $group = $this->_group;

        $this->_auto_clean_files();
        $fn = $this->_get_filename($key,$group);
        clearstatcache(false,$fn);
        return is_file($fn);
    }


    /**
     * Erase a cached value
     * If $group is empty, the current group will be used
     *
     * @see cms_filecache_driver::set_group
     * @param string $key
     * @param string $group
     */
    public function erase($key,$group = '')
    {
        if( !$group ) $group = $this->_group;

        $fn = $this->_get_filename($key,$group);
        if( is_file($fn) ) {
            @unlink($fn);
            return true;
        }
        return false;
    }


    /**
     * Set a cached value
     * If $group is empty, the current group will be used
     *
     * @see cms_filecache_driver::set_group
     * @param string $key
     * @param mixed $value
     * @param string $group
     */
    public function set($key,$value,$group = '')
    {
        if( !$group ) $group = $this->_group;

        $fn = $this->_get_filename($key,$group);
        $res = $this->_write_cache_file($fn,$value);
        return $res;
    }


    /**
     * Set the current group
     *
     * @param string $group
     */
    public function set_group($group)
    {
        if( $group ) $this->_group = trim($group);
    }


    /**
     * @ignore
     */
    private function _get_filename($key,$group)
    {
        $fn = $this->_cache_dir.DIRECTORY_SEPARATOR.'cache_'.hash($this->_algo,__DIR__.$group).'_'.hash($this->_algo,$key.__DIR__).'.cms';
        return $fn;
    }


    /**
     * @ignore
     */
    private function _flock($res,$flag)
    {
        if( !$this->_locking ) return true;
        if( !$res ) return false;

        $mode = '';
        switch( strtolower($flag) ) {
        case self::LOCK_READ:
            $mode = LOCK_SH;
            break;

        case self::LOCK_WRITE:
            $mode = LOCK_EX;
            break;

        case self::LOCK_UNLOCK:
            $mode = LOCK_UN;
        }

        if( $this->_blocking ) return flock($res,$mode);

        // non blocking lock
        $mode = $mode | LOCK_NB;
        for( $n = 0; $n < 5; $n++ ) {
            $res2 = flock($res,$mode);
            if( $res2 ) return true;
            $tl = mt_rand(10,300);
            usleep($tl);
        }
        return false;
    }


    /**
     * @ignore
     */
    private function _read_cache_file($fn)
    {
        $this->_cleanup($fn);
        $data = null; // no matched cache item == no value
        if( is_file($fn) ) {
            clearstatcache();
            $fp = @fopen($fn,'rb');
            if( $fp ) {
                if( $this->_flock($fp,self::LOCK_READ) ) {
                    $len = @filesize($fn);
                    if( $len > 0 ) $data = fread($fp,$len);
                    $this->_flock($fp,self::LOCK_UNLOCK);
                }
                @fclose($fp);

                if( $data && startswith($data,self::KEY_SERIALIZED) ) {
                    $data = unserialize(substr($data,strlen(self::KEY_SERIALIZED)));
                }
            }
        }
        return $data;
    }


    /**
     * @ignore
     */
    private function _cleanup($fn)
    {
        if( is_null($this->_lifetime) ) return;
        clearstatcache();
        $filemtime = @filemtime($fn);
        if( $filemtime < time() - $this->_lifetime ) @unlink($fn);
    }


    /**
     * @ignore
     */
    private function _write_cache_file($fn,$data)
    {
        @touch($fn);
        $fp = @fopen($fn,'r+');
        if( $fp ) {
            if( !$this->_flock($fp,self::LOCK_WRITE) ) {
                @fclose($fp);
                @unlink($fn);
                return false;
            }
            else {
                if( is_array($data) || is_object($data) ) {
                    $data = self::KEY_SERIALIZED.serialize($data);
                }
                @fwrite($fp,$data);
                $this->_flock($fp,self::LOCK_UNLOCK);
            }
            @fclose($fp);
            return true;
        }
        return false;
    }


    /**
     * @ignore
     */
    private function _auto_clean_files()
    {
        if( $this->_auto_cleaning ) {
            // only clean files once per request.
            static $_have_cleaned = false;
            if( !$_have_cleaned ) {
                $res = $this->_clean_dir($this->_cache_dir);
                if( $res > 0 ) $_have_cleaned = true;
                return $res;
            }
        }
        return 0;
    }


    /**
     * @ignore
     * if $group is empty, the current group will be used
     * To force-clear everything, supply $group = '*'
     */
    private function _clean_dir($dir,$group = '',$old = true)
    {
        if( !$group ) { $group = $this->_group; }

        if( $group && $group != '*' ) {
            $mask = $dir.DIRECTORY_SEPARATOR.'cache_'.hash($this->_algo,__DIR__.$group).'_*.cms';
        }
        else {
            $mask = $dir.DIRECTORY_SEPARATOR.'cache_*_*.cms';
        }

        $files = glob($mask);
        if( !$files ) return 0;

        $nremoved = 0;
        foreach( $files as $file ) {
            if( is_file($file) ) {
                $del = false;
                if( $old == true ) {
                    if( !is_null($this->_lifetime) ) {
                        if( (time() - @filemtime($file)) > $this->_lifetime ) {
                            @unlink($file);
                            $nremoved++;
                        }
                    }
                }
                else {
                    // clean all files...
                    @unlink($file);
                    $nremoved++;
                }
            }
        }
        return $nremoved;
    }

} // end of class

?>
