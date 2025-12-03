<?php
# FileManager module class
# (c) 2006-08 Morten Poulsen <morten@poulsen.org>
# (c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

use CMSMS\FileTypeHelper;

final class filemanager_utils
{
    private static $helper;

    protected function __construct() {}

    /**
     * Report whether $name contains invalid char(s)
     *
     * @param string $name
     *
     * @return bool
     */
    public static function is_valid_filename($name)
    {
        if( $name == '' ) return FALSE;
        if( strpos($name,'/') !== FALSE ) return FALSE;
        if( strpos($name,'\\') !== FALSE ) return FALSE;
        if( strpos($name,'..') !== FALSE ) return FALSE;
        if( $name[0] == '.' || $name[0] == ' ' ) return FALSE;
        if( endswith( $name, '.' ) ) return FALSE;
        // minimal executable filename-extension check formerly here is now in
        // self::is_restricted() and nothing about a restricted file's name
        // per se renders the filename invalid
        if( preg_match('/[\n\r\t\[\]\&\?\<\>\!\@\#\$\%\*\(\)\{\}\|\"\'\:\;\+]/',$name) ) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Perform a (useless) site-configuration test and then a user-permission
     * ('Use FileManager Advanced') test (the result of which is elsewhere
     * used to decide whether all site folders may be used in FileManager, or
     * else only the configured uploads-folders-tree).
     * NOTE $config property 'developer_mode', if set, effectively pre-empts
     * 'Use FileManager Advanced' permission, and does so for all admin users.
     *
     * @return int 2 or 1 or 0, >0 indicating 'can do'
     */
    public static function can_do_advanced()
    {
        static $_can_do_advanced = null;
        if( $_can_do_advanced === null ) {
            $config = cmsms()->GetConfig();
            if( !empty($config['developer_mode']) ) {
                $_can_do_advanced = 2;
            }
            else {
                $mod = cms_utils::get_module('FileManager');
                //user-setting test plus site-setting test (which should always pass)
                if( $mod->AdvancedAccessAllowed() && startswith($config['uploads_path'],CMS_ROOT_PATH) ) {
                    $_can_do_advanced = 1;
                }
                else {
                    $_can_do_advanced = 0;
                }
            }
        }
        return $_can_do_advanced;
    }

    /**
     * Report whether the return from filemanager_utils::can_do_advanced()
     * and FileManager-module 'advancedmode' preference are both truthy.
     * The latter enables all admin users to use all site folders in FileManager.
     * NOTE 'Use FileManager Advanced' permission has the same effect for
     * particular user(s) as 'advancedmode' preference has for all users
     *
     * @return bool indicating both tests were passed
     */
    public static function check_advanced_mode()
    {
        // site-configuration test and current-user 'Use FileManager Advanced' permission test
        $val = self::can_do_advanced();
        switch ($val) {
            case 2:
                return TRUE; //$config['developer_mode']
            case 1:
                // all users' property test prob effective duplication of permission
                $mod = cms_utils::get_module('FileManager');
                return $mod->GetPreference('advancedmode',FALSE) != FALSE;
            default:
                return FALSE;
        }
    }

    /**
     *
     * @return string, maybe empty
     */
    public static function get_default_cwd()
    {
        if( self::check_advanced_mode() ) {
            $dir = CMS_ROOT_PATH;
        }
        else {
            $config = cms_config::get_instance();
            $dir = $config['uploads_path'];
            if( !startswith($dir,CMS_ROOT_PATH) ) {
                $dir = self::join_path(CMS_ROOT_PATH,'uploads');
            }
        }

        $dir = cms_relative_path($dir,CMS_ROOT_PATH);
        return $dir;
    }

    /**
     *
     * @param string $path Site root-path relative filepath, maybe empty
     *
     * @return bool indicating $path validity
     */
    public static function test_valid_path($path)
    {
        if( !($path == '/' || $path == '\\' || $path == '') ) {
            $path = self::join_path(CMS_ROOT_PATH,$path);
        }
        else {
            $path = CMS_ROOT_PATH;
        }
        $rpath = realpath($path);
        if( !$rpath ) return FALSE;

        if( !self::check_advanced_mode() ) {
            // 'non advanced mode', path must start with the uploads path.
            $config = cms_config::get_instance();
            $uprp = realpath($config['uploads_path']);
            if( startswith($rpath,$uprp) ) return TRUE;
        }
        else {
            // advanced mode, path must start with the root path.
            $rprp = realpath(CMS_ROOT_PATH);
            if( startswith($path,$rprp) ) return TRUE; //always TRUE
        }
        return FALSE;
    }

    /**
     *
     * @return string
     */
    public static function get_cwd()
    {
        // check the path
        $path = cms_userprefs::get('filemanager_cwd');
        if( !$path ) $path = self::get_default_cwd();
        if( !self::test_valid_path($path) ) {
            $path = self::get_default_cwd();
        }
        //if( $path == '' ) $path = '/'; causes double // in site root
        return $path;
    }

    /**
     * Record current user's cwd preference
     *
     * @param string $path filesystem path absolute or site-root-relative
     */
    public static function set_cwd($path)
    {
        if( startswith($path,CMS_ROOT_PATH) ) $path = cms_relative_path($path,CMS_ROOT_PATH);

        // validate the path.
        $tmp = self::join_path(CMS_ROOT_PATH,$path);
        $tmp = realpath($tmp);
        if( !$tmp || !is_dir($tmp) ) throw new Exception('Cannot set current working directory to an invalid path');
        $newpath = cms_relative_path($tmp,CMS_ROOT_PATH);
        if( !self::test_valid_path($newpath) ) throw new Exception('Cannot set current working directory to an invalid path');

        $newpath = str_replace('\\','/',$newpath);
        cms_userprefs::set('filemanager_cwd',$newpath);
    }

    /**
     * This is NOT for constructing URLs
     * See also cms_join_path()
     *
     * @param varargs $args
     * @return string, maybe empty
     */
    public static function join_path(...$args)
    {
        if( !$args ) return '';
        if( count($args) < 2 ) return $args[0];

        $tmp = cms_join_path(...$args);
        return preg_replace('~[\\\\/]+~',DIRECTORY_SEPARATOR,$tmp); // scrub adjacent separators
    }

    /**
     *
     * @return string
     */
    public static function get_full_cwd()
    {
        $path = self::get_cwd();
        if( !self::test_valid_path($path) ) $path = self::get_default_cwd();
        return self::join_path(CMS_ROOT_PATH,$path);
    }

    /**
     *
     * @return string
     */
    public static function get_cwd_url()
    {
        $path = self::get_cwd();
        if( !self::test_valid_path($path) ) $path = self::get_default_cwd();
        return CMS_ROOT_URL.'/'.strtr($path,'\\','/');
    }

    /**
     *
     * @param string $path
     * @return bool
     */
    public static function is_hidden_file($path)
    {
        static $macos; // whether running on some flavour of MacOS
        static $winos; // whether running on some flavour of Windows
        if( !isset($macos) ) {
            if( function_exists('php_uname') && ($tmp = php_uname('s')) ) { //might return null (undocumented)
                $winos = stripos($tmp,'windo') !== FALSE;
                $macos = !$winos && stripos($tmp,'darwin') !== FALSE;
            }
            else {
                $winos = (PATH_SEPARATOR == ';');
                $macos = !$winos && (PHP_EOL == "\r" || 0); // TODO robust fallack mechanism for OS X+
            }
        }

        $tmp = basename($path);
        switch( $tmp[0] ) {
            case '.':
                return !$winos;
            case '_':
                return $macos;
            case '~':
                return $winos;
            default:
                if( $winos ) {
                    if( (int)ini_get('safe_mode_exec_dir') == 0 ) { //exec() not blocked
                        $path = str_replace('/','\\',$path);
                        if( ($res1 = exec('attrib ' . escapeshellarg($path),$outlines,$res)) !== FALSE ) {
                            $res2 = reset($outlines); //TODO is the wanted member first? or use $res1?
                            if( ($p = strpos($res2,'H')) !== FALSE ) {
                                return preg_match('~ ([A-Z]{1,2}:)?\\~',$res2,null,0,$p + 1); //want whitespace after 'H' and before path-start
                            }
                        }
                    }
                }
                return FALSE;
        }
    }

    /**
     *
     * @param string $path
     * @return bool
     */
    public static function is_image_file($path)
    {
        if( !isset(self::$helper) ) {
            self::$helper = new FileTypeHelper();
        }
        return self::$helper->is_image($path);
    }

    /**
     *
     * @param string $path
     * @return bool
     */
    public static function is_archive_file($path)
    {
        if( !isset(self::$helper) ) {
            self::$helper = new FileTypeHelper();
        }
        return self::$helper->is_archive($path);
    }

    /**
     *
     * @since 1.6.14
     * @param string $path
     * @return bool
     */
    public static function is_restricted_file($path)
    {
        $bn = basename($path);
        $a = strrpos($bn,'.'); //is_executable() checks file-extension
        if( $a > 0 ) { //also exclude hidden file
            if( !isset(self::$helper) ) {
                self::$helper = new FileTypeHelper();
            }
            if( self::$helper->is_executable($path)) {
                return TRUE;
            }
            if( substr_compare($bn,'.js',$a,3,TRUE) == 0 ) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     *
     * @param string $path CMS_ROOT_PATH-relative, maybe empty. Default ''
     * @param string $sortby Optional sort-type 'name','size' etc with 'asc' or 'desc' suffix. Default ''
     *
     * @return array
     */
    public static function get_file_list($path = '',$sortby ='')//: array
    {
        if( !$path ) $path = self::get_cwd();
        $advancedmode = self::check_advanced_mode();
        $mod = cms_utils::get_module('FileManager');
        $showhiddenfiles = (bool)$mod->GetPreference('showhiddenfiles');
        $result = [];

        // convert the path|cwd to an absolute path
        $basepath = self::join_path(CMS_ROOT_PATH,$path);

        $dir = @opendir($basepath);
        if (!$dir) return [];
        while ($file = readdir($dir)) {
            if ($file == '.') continue;
            if ($file == '..') {
                // can we go up.
                if( $path == '/' || $path == self::get_default_cwd() ) continue;
            } elseif( !($advancedmode || $showhiddenfiles) ) {
                if( self::is_hidden_file($basepath.DIRECTORY_SEPARATOR.$file)) continue;
            }

            if (substr($file,0,6)=='thumb_') {
                //ignore thumbnail files if showing thumbnails is off
                if (!$mod->GetPreference('showthumbnails',1)) continue;
            }

            // build the file info array.
            $info = [
                'name' => $file,
                'image' => FALSE,
                'archive' => FALSE
            ];
            $fullname = self::join_path($basepath,$file);
            $info['mime'] = self::mime_content_type($fullname);
            $statinfo = stat($fullname); //array | false
            $info['size'] = $statinfo ? $statinfo['size'] : 0;
            $info['date'] = $statinfo ? $statinfo['mtime'] : ''; //default no display

            if (is_dir($fullname)) {
                $info['dir'] = TRUE;
                $info['ext'] = '';
                $info['fileinfo'] = GetFileInfo($fullname,'',TRUE);
            } else {
                $info['dir'] = FALSE;
                $tmp = trim(strtr($path,'\\','/'),' /');
                $info['url'] = implode('/',[CMS_ROOT_URL,$tmp,$file]);
                $info['exec'] = self::is_restricted_file($fullname);
                $a = strrpos($file,'.');
                $info['ext'] = ($a > 0) ? substr($file,$a + 1) : '';
                $info['fileinfo'] = GetFileInfo($fullname,$info['ext']);
            }

            // test for archive
            $info['archive'] = self::is_archive_file($fullname);

            // test for image
            $info['image'] = self::is_image_file($fullname);

            $info['writable'] = is_writable($fullname);
            if ($statinfo) {
                $mode = $statinfo['mode'];
            } elseif ($info['writable']) {
                $mode = is_readable($fullname) ? 0600 : 0400; //TOO BAD about access/execute, other users
            } elseif (is_readable($fullname)) {
                $mode = 0400;
            } else {
                $mode = 0;
            }
            $info['permissions'] = self::format_permissions($mode,$mod->GetPreference('permissionstyle','xxx'));

            $result[] = $info;
        }
        closedir($dir);

        if (!$sortby) {
            if (!empty($_SESSION['FMsortby'])) {
                $sortby = $_SESSION['FMsortby'];
            }
            else {
                $sortby = 'nameasc';
            }
        }

        usort($result, function ($a, $b) use ($sortby) {
            if ($a['name'] == '..') return -1;
            if ($b['name'] == '..') return 1;
/*          print_r($a);
            print_r($b);*/
            //Handle if only one is a dir
            if ($a['dir'] xor $b['dir']) {
                return ($a['dir']) ? -1 : 1;
            }

            switch($sortby) {
            case 'sizeasc':
                if (!$a['dir'] || !$b['dir']) {
                    $n = (int)($a['size'] - $b['size']);
                    if ($n !== 0) return $n;
                }
                return strncasecmp($a['name'],$b['name'],strlen($a['name']));

            case 'sizedesc':
                if (!$a['dir'] || !$b['dir']) {
                    $n = (int)($b['size'] - $a['size']);
                    if ($n !== 0) return $n;
                }
                return strncasecmp($a['name'],$b['name'],strlen($a['name']));

            case 'datedasc':
                $n = (int)($a['date'] - $b['date']);
                return ($n !== 0) ? $n : strncasecmp($a['name'],$b['name'],strlen($a['name']));

            case 'datedesc':
                $n = (int)($b['date'] - $a['date']);
                return ($n !== 0) ? $n : strncasecmp($a['name'],$b['name'],strlen($a['name']));

            case 'typeasc':
                $n = strcasecmp($a['mime'], $b['mime']);
                return ($n !== 0) ? $n : strncasecmp($a['name'],$b['name'],strlen($a['name']));

            case 'typedesc':
                $n = strcasecmp($b['mime'], $a['mime']);
                return ($n !== 0) ? $n : strncasecmp($a['name'],$b['name'],strlen($a['name']));

            case 'namedesc':
                return strncasecmp($b['name'],$a['name'],strlen($b['name']));

            default:
                return strncasecmp($a['name'],$b['name'],strlen($a['name']));
            }
        });
        return $result;
    }

    /**
     *
     * @return string maybe empty
     */
    public static function mime_content_type($filename)
    {
        if( function_exists('mime_content_type') ) {
            $mime_type = mime_content_type($filename);
            if( $mime_type ) return $mime_type;
        }
        // this is effectively the same as FileTypeHelper->get_mime_type()
        if( function_exists('finfo_open') ) {
            $fh = finfo_open(FILEINFO_MIME_TYPE);
            if( $fh ) {
                $mime_type = finfo_file($fh,$filename);
                if( PHP_VERSION_ID < 80500 ) finfo_close($fh);
                if( $mime_type ) return $mime_type;
            }
        }
        // fall back to a simple extension-based mechanism
        $a = strrpos($filename,'.');
        $ext = ($a > 0) ? substr($filename,$a + 1) : '';
        if( $ext ) {
            $ext = strtolower($ext);
            $mime_types = [
                'txt' => 'text/plain',
                'htm' => 'text/html',
                'html' => 'text/html',
                'php' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'xml' => 'application/xml',
                'swf' => 'application/x-shockwave-flash',
                'flv' => 'video/x-flv',

                // images
                'png' => 'image/png',
                'jpeg' => 'image/jpeg',
                'jpe' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'avif' => 'image/avif',
                'bmp' => 'image/bmp',
                'wbmp' => 'image/vnd.wap.wbmp',
                'ico' => 'image/vnd.microsoft.icon',
                'tiff' => 'image/tiff',
                'tif' => 'image/tiff',
                'svg' => 'image/svg+xml',
                'svgz' => 'image/svg+xml',
                'apng' => 'image/apng',
//              'heic' => 'image/heic', //iOS
//              'heif' => 'image/heif',

                // archives
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'exe' => 'application/x-msdownload',
                'msi' => 'application/x-msdownload',
                'cab' => 'application/vnd.ms-cab-compressed',

                // audio/video
                'mp3' => 'audio/mpeg',
                'qt' => 'video/quicktime',
                'mov' => 'video/quicktime',
                'webm' => 'video/webm',

                // adobe
                'pdf' => 'application/pdf',
                'psd' => 'image/vnd.adobe.photoshop',
                'ai' => 'application/postscript',
                'eps' => 'application/postscript',
                'ps' => 'application/postscript',

                // ms office
                'doc' => 'application/msword',
                'rtf' => 'application/rtf',
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint',

                // open office
                'odt' => 'application/vnd.oasis.opendocument.text',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            ];
            if (array_key_exists($ext, $mime_types)) {
                 return $mime_types[$ext];
            }
        }
        //empty instead of "application/octet-stream"
        return '';
    }

    /**
     *
     * @param mixed $val
     * @return int
     */
    public static function str_to_bytes($val)
    {
        if( $val && is_string($val) ) {
            $val = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            if( $last < '<' || $last > 9 ) $val = substr($val,0,-1);
            $val = (int)$val;
            switch($last) {
            case 'g':
                $val *= 1024;
                //no break here
            case 'm':
                $val *= 1024;
                //no break here
            case 'k':
                $val *= 1024;
            }
        }
        return (int) $val;
    }

    /**
     * Recursively get directories in and descendent from $startdir
     *
     * @param string $startdir
     * @param bool $showhiddenfiles
     * @param string $prefix e.g. DIRECTORY_SEPARATOR
     *
     * @return array maybe empty
     */
    private static function get_dirs($startdir,$showhiddenfiles,$prefix)
    {
        if( !is_dir($startdir) ) return [];

        $res = [];
        $dh = opendir($startdir);
        while( FALSE !== ($entry = readdir($dh)) ) {
            if( $entry == '.' || $entry == '..' || $entry == '.svn' || $entry == '.git' ) continue;
            $full = self::join_path($startdir,$entry);
            if( !is_dir($full) ) continue;
            if( !$showhiddenfiles && self::is_hidden_file($full) ) continue;

            $res[$prefix.$entry] = $prefix.$entry;
            $tmp = self::get_dirs($full,$showhiddenfiles,$prefix.$entry.DIRECTORY_SEPARATOR); //recurse
            if( $tmp && is_array($tmp) ) $res = array_merge($res,$tmp);
        }
        closedir($dh);
        return $res;
    }

    /**
     *
     * @return array
     */
    public static function get_dirlist()
    {
        $mod = cms_utils::get_module('FileManager');
        $showhiddenfiles = (bool)$mod->GetPreference('showhiddenfiles');
        $advancedmode = self::check_advanced_mode();
        if( $advancedmode ) {
            $startdir = CMS_ROOT_PATH;
        }
        else {
            $config = cms_config::get_instance();
            $startdir = $config['uploads_path'];
        }
        $output = self::get_dirs($startdir,$showhiddenfiles,DIRECTORY_SEPARATOR);
        if( $output && is_array($output) ) {
            ksort($output);
            $tmp = [];
            if( $advancedmode ) {
                $tmp['/'] = '/'.basename($startdir).' ('.$mod->Lang('site_root').')'; //DIRECTORY_SEPARATOR?
            }
            else {
                $tmp['/'] = '/'.basename($startdir).' ('.$mod->Lang('top').')';
            }
            $output = array_merge($tmp,$output);
        }
        return $output;
    }

    /**
     *
     * @param string $src Filepath
     * @param string $dest Default ''
     * @param bool $force Default false
     *
     * @return bool
     */
    public static function create_thumbnail($src,$dest = '',$force = FALSE)
    {
        if( !file_exists($src) ) return FALSE;
        if( !$dest ) {
            $bn = basename($src);
            $dn = dirname($src);
            $dest = $dn.DIRECTORY_SEPARATOR.'thumb_'.$bn;
        }

        if( !$force && (file_exists($dest) && !is_writable($dest) ) ) return FALSE;

        $mime = self::mime_content_type($src);
        if( !$mime ) return FALSE;

        if( $mime == 'image/svg+xml' || $mime == 'image/svg' ) {
            if( $force ) {
                if( is_file($dest) ) {
                    unlink($dest);
                }
                copy($src,$dest); //TODO suitably replicate content as $dest
            }
            elseif( !is_file($dest) ) {
                copy($src,$dest);
            }
            return TRUE;
        }

        $info = getimagesize($src);
        if( !$info ) return FALSE;

        $src_width = $info[0];
        $src_height = $info[1];
        $thumb_width = cms_siteprefs::get('thumbnail_width',96);
        $thumb_height = cms_siteprefs::get('thumbnail_height',96);
        $src_x = 0;
        $src_y = 0;
        self::get_thumbnail_size($src_width,$src_height,$thumb_width,$thumb_height,$src_x,$src_y);

        // if suitably-sized for thumbnail, copy it
        if( $info[0] <= $thumb_width && $info[1] <= $thumb_height ) {
            if( $force ) {
                if( is_file($dest) ) {
                    unlink($dest);
                }
                copy($src,$dest);
            }
            elseif( !is_file($dest) ) {
                copy($src,$dest);
            }
            return TRUE;
        }

        $i_src = imagecreatefromstring(file_get_contents($src));
        $i_dest = imagecreatetruecolor($thumb_width,$thumb_height);

        //TODO some of the following are type-spacific
        imagealphablending($i_dest,FALSE); //TODO relevant if format has alpha channel
        $color = imageColorAllocateAlpha($i_src,255,255,255,127); // ditto
        imagecolortransparent($i_dest,$color);
        imagefill($i_dest,0,0,$color);
        imagesavealpha($i_dest,TRUE); //TODO for png, webp and avif only

        imagecopyresampled($i_dest,$i_src,0,0,$src_x,$src_y,$thumb_width,$thumb_height,$src_width,$src_height);
        // c.f. FileTypeHelper image-file extensions 'jpg','jpeg','jpe','bmp','wbmp','gif','png','tiff','tif','ico','webp','avif','heif','svg','apng'
        switch( $mime ) {
        case 'image/gif':
            $res = imagegif($i_dest,$dest);
            break;
        case 'image/png':
        case 'image/apng': //ok here ?
            $res = imagepng($i_dest,$dest,9);
            break;
        case 'image/jpeg':
            $res = imagejpeg($i_dest,$dest,80);
            break;
        case 'image/bmp':
        case 'image/x-ms-bmp':
            if (PHP_VERSION_ID >= 70200) {
                $res = imagebmp($i_dest,$dest);
            } else {
                $res = FALSE;
            }
            break;
        case 'image/vnd.wap.wbmp':
            $res = imagewbmp($i_dest,$dest); // black foreground
            break;
        case 'image/webp':
            $res = imagewebp($i_dest,$dest,80);
            break;
        case 'image/avif':
            if (PHP_VERSION_ID >= 80100 && function_exists('imageavif')) {
                $res = imageavif($i_dest,$dest,80,6);
            } else {
                $res = FALSE;
            }
            break;
        default:
            $res = FALSE;
        }
        return ($res != FALSE);
    }

    /**
     * Alters some/all of the provided parameters, based on their supplied values
     * Size will be cropped to retain image ratio
     * @since 2.2.21F2
     *
     * @param int $src_width width of the source image > 0
     * @param int $src_height height of the source image > 0
     * @param int $thumb_width optional width of thumbnail to be created Default 0 hence sitepref
     * @param int $thumb_height optional height of thumbnail to be created Default 0 hence sitepref
     * @param int $src_x optional x-coordinate of source point Default 0
     * @param int $src_y optional y-coordinate of source point Default 0
     */
    private static function get_thumbnail_size(
        &$src_width,
        &$src_height,
        &$thumb_width = 0,
        &$thumb_height = 0,
        &$src_x = 0,
        &$src_y = 0)
    {
        // if one dimension not set, calculate width/height ratio
        if ($thumb_width > 0 && $thumb_height > 0) {
            $thumb_width = (int)$thumb_width;
            $thumb_height = (int)$thumb_height;
        } elseif ($thumb_width == 0) {  // but not $thumb_height
            if ($src_height == 0) {
                return;
            }
            $thumb_width = (int)($src_width / $src_height * $thumb_height);
        } else { // $thumb_height == 0 but not $thumb_width
            if ($src_width == 0) {
                return;
            }
            $thumb_height = (int)($src_height / $src_width * $thumb_width);
        }
        if ($thumb_height == 0) {
            return;
        }
        // set $src_x|$src_y, $src_width|$src_height to crop-related values if required
        $ratio_src = $src_width / $src_height;
        $ratio_thumb = $thumb_width / $thumb_height;
        if ($ratio_src >= $ratio_thumb) { // width to be clipped
            $src_x = (int)(($src_width - $src_height * $ratio_thumb) / 2);
            $src_width = (int)($src_height * $ratio_thumb);
        } else { // height to be clipped
            $src_y = (int)(($src_height - $src_width / $ratio_thumb) / 2);
            $src_height = (int)($src_width / $ratio_thumb);
        }
    }

    /**
     *
     * @param int $size
     * @return array
     */
    public static function format_filesize($size)
    {
        $mod = cms_utils::get_module('FileManager');
        if ($size < 2048) {
            $unit = ($size > 0) ? $mod->Lang('bytes') : '';
            $size = trim((string)$size);
        }
        elseif ($size <= 1048576) { //aka 1024*1024
            $lcc = localeconv();
            $size = round($size/1024,1);
            $size = number_format($size, 1, $lcc['decimal_point'], $lcc['thousands_sep']);
            $size = trim($size, '0'.$lcc['decimal_point']);
            $unit = $mod->Lang('kb');
        }
        else {
            $lcc = localeconv();
            $size = round($size/1048576,1);
            $size = number_format($size, 1, $lcc['decimal_point'], $lcc['thousands_sep']);
            $size = trim($size, '0'.$lcc['decimal_point']);
            $unit = $mod->Lang('mb');
        }
        return ['size' => $size, 'unit' => $unit];
    }

    /**
     *
     * @param int $mode
     * @param string $style Default 'xxx'
     * @return string
     */
    public static function format_permissions($mode, $style='xxx')
    {
        switch ($style) {
        case 'xxx':
            $owner = 0;
            if ($mode & 0400) $owner += 4;
            if ($mode & 0200) $owner += 2;
            if ($mode & 0100) $owner ++;
            $group = 0;
            if ($mode & 0040) $group += 4;
            if ($mode & 0020) $group += 2;
            if ($mode & 0010) $group ++;
            $others = 0;
            if ($mode & 0004) $others += 4;
            if ($mode & 0002) $others += 2;
            if ($mode & 0001) $others ++;
            return $owner.$group.$others;

        case 'xxxxxxxxx':
            $owner = '';
            if ($mode & 0400) $owner.='r'; else $owner.='-';
            if ($mode & 0200) $owner.='w'; else $owner.='-';
            if ($mode & 0100) $owner.='x'; else $owner.='-';
            $group = '';
            if ($mode & 0040) $group.='r'; else $group.='-';
            if ($mode & 0020) $group.='w'; else $group.='-';
            if ($mode & 0010) $group.='x'; else $group.='-';
            $others = '';
            if ($mode & 0004) $others.='r'; else $others.='-';
            if ($mode & 0002) $others.='w'; else $others.='-';
            if ($mode & 0001) $others.='x'; else $others.='-';
            return $owner.$group.$others;

        default:
            return (string)$mode;
        }
    }
} // class
