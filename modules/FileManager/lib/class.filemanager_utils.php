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
    private static $_can_do_advanced = -1;
    private static $helper;

    protected function __construct() {}

    public static function is_valid_filename($name)
    {
        if( $name == '' ) return FALSE;
        if( strpos($name,'/') !== FALSE ) return FALSE;
        if( strpos($name,'\\') !== FALSE ) return FALSE;
        if( strpos($name,'..') !== FALSE ) return FALSE;
        if( $name[0] == '.' || $name[0] == ' ' ) return FALSE;
        if( endswith( $name, '.' ) ) return FALSE;

        $a = strrpos($name,'.');
        $ext = ($a > 0) ? substr($name,$a + 1) : '';
        if( $ext ) {
            $ext = strtolower($ext);
            if( startswith($ext,'php') || endswith($ext,'php') ) return FALSE;
        }
        if( preg_match('/[\n\r\t\[\]\&\?\<\>\!\@\#\$\%\*\(\)\{\}\|\"\'\:\;\+]/',$name) ) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_do_advanced()
    {
        if( self::$_can_do_advanced < 0 ) {
            $mod = cms_utils::get_module('FileManager');
            $config = cms_config::get_instance();
            if( startswith($config['uploads_path'],CMS_ROOT_PATH) && $mod->AdvancedAccessAllowed() ) {
                self::$_can_do_advanced = 1;
            }
            else {
                self::$_can_do_advanced = 0;
            }
        }
        return self::$_can_do_advanced;
    }

    public static function check_advanced_mode()
    {
        if( !self::can_do_advanced() ) return FALSE;
        $mod = cms_utils::get_module('FileManager');
        return ($mod->GetPreference('advancedmode') != FALSE);
    }

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

    // returns false if invalid. $path is root-path-relative, may be empty
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

    //this is NOT for constructing URLs
    public static function join_path(...$args)
    {
        if( !$args ) return '';
        if( count($args) < 2 ) return $args[0];

        $tmp = cms_join_path(...$args);
        return preg_replace('~[\\\\/]+~',DIRECTORY_SEPARATOR,$tmp); // scrub adjacent separators
    }

    public static function get_full_cwd()
    {
        $path = self::get_cwd();
        if( !self::test_valid_path($path) ) $path = self::get_default_cwd();
        return self::join_path(CMS_ROOT_PATH,$path);
    }

    public static function get_cwd_url()
    {
        $path = self::get_cwd();
        if( !self::test_valid_path($path) ) $path = self::get_default_cwd();
        return CMS_ROOT_URL.'/'.strtr($path,'\\','/');
    }

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
                $macos = !$winos && 0; // TODO fallack mechanism
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
                    $path = str_replace('/','\\',$path);
                    exec('attrib ' . escapeshellarg($path),$res);
                    if( $res && ($p = strpos($res,'H')) !== FALSE ) {
                        return preg_match('~\s.*?:?\\~',$res,null,0,$p + 1); //want whitespace after 'H' and before ':\'
                    }
                }
                return FALSE;
        }
    }

    public static function is_image_file($path)
    {
        if( !isset(self::$helper) ) {
            self::$helper = new FileTypeHelper();
        }
        return self::$helper->is_image($path);
    }

    public static function is_archive_file($path)
    {
        if( !isset(self::$helper) ) {
            self::$helper = new FileTypeHelper();
        }
        return self::$helper->is_archive($path);
    }

    //$path (if any) is CMS_ROOT_PATH-relative
    public static function get_file_list($path = '')//: array
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
                $a = strrpos($file,'.');
                $info['ext'] = ($a > 0) ? substr($file,$a + 1) : '';
                $info['fileinfo'] = GetFileInfo($fullname,$info['ext']);
            }

            // test for archive
            $info['archive'] = self::is_archive_file($fullname);

            // test for image
            $info['image'] = self::is_image_file($fullname);

            if ($statinfo && function_exists('posix_getpwuid')) {
                $userinfo = @posix_getpwuid($statinfo['uid']);
                $info['fileowner'] = isset($userinfo['name']) ? $userinfo['name'] : $mod->Lang('unknown');
            } else {
                $info['fileowner'] = lang('n_a');
            }

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

        if (!empty($_SESSION['FMnewsortby'])) {
            $sortby = $_SESSION['FMnewsortby'];
        }
        else {
            $sortby = 'nameasc';
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
            //TODO support sorting on mime, date
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

            case 'namedesc': return strncasecmp($b['name'],$a['name'],strlen($b['name']));

            default: return strncasecmp($a['name'],$b['name'],strlen($a['name']));
            }
        });
        return $result;
    }

    public static function mime_content_type($filename)
    {
        // this is effectively the same as FileTypeHelper->get_mime_type()
        if( function_exists('finfo_open') ) {
            $fh = finfo_open(FILEINFO_MIME_TYPE);
            if( $fh ) {
                $mime_type = finfo_file($fh,$filename);
                finfo_close($fh);
                return $mime_type;
            }
        }
        // but with the following fallback
        if( !function_exists('mime_content_type') ) {

            // Try to recreate a "very" simple mechanism for mime_content_type($filename);
            function mime_content_type($filename) {

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
                    'jpe' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'jpg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'bmp' => 'image/bmp',
                    'ico' => 'image/vnd.microsoft.icon',
                    'tiff' => 'image/tiff',
                    'tif' => 'image/tiff',
                    'svg' => 'image/svg+xml',
                    'svgz' => 'image/svg+xml',

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

                $a = strrpos($filename,'.');
                $ext = ($a > 0) ? substr($filename,$a + 1) : '';
                if( $ext ) $ext = strtolower($ext);
                if (array_key_exists($ext, $mime_types)) {
                    return $mime_types[$ext];
                }
                else {
                    //Nothing instead of "application/octet-stream"
                    return '';
                }
            }
        }

        // Now we can call this function
        return mime_content_type($filename);
    }

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

    // recursively get an array of directories in and descendent from $startdir.
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
                $tmp['/'] = '/'.basename($startdir).' ('.$mod->Lang('site_root').')';
            }
            else {
                $tmp['/'] = '/'.basename($startdir).' ('.$mod->Lang('top').')';
            }
            $output = array_merge($tmp,$output);
        }
        return $output;
    }

    public static function create_thumbnail($src, $dest = '', $force = FALSE)
    {
        if( !file_exists($src) ) return FALSE;
        if( !$dest ) {
            $bn = basename($src);
            $dn = dirname($src);
            $dest = $dn.DIRECTORY_SEPARATOR.'thumb_'.$bn;
        }

        if( !$force && (file_exists($dest) && !is_writable($dest) ) ) return FALSE;

        $info = getimagesize($src);
        if( !$info || !isset($info['mime']) ) return FALSE;

        $i_src = imagecreatefromstring(file_get_contents($src));
        $width = cms_siteprefs::get('thumbnail_width', 96);
        $height = cms_siteprefs::get('thumbnail_height', 96);

        $i_dest = imagecreatetruecolor($width, $height);
        imagealphablending($i_dest, FALSE);
        $color = imageColorAllocateAlpha($i_src, 255, 255, 255, 127);
        imagecolortransparent($i_dest, $color);
        imagefill($i_dest, 0, 0, $color);
        imagesavealpha($i_dest, TRUE);
        imagecopyresampled($i_dest, $i_src, 0, 0, 0, 0, $width, $height, imagesx($i_src), imagesy($i_src));

        switch( $info['mime'] ) {
        case 'image/gif':
            $res = imagegif($i_dest,$dest);
            break;
        case 'image/png':
            $res = imagepng($i_dest,$dest,9);
            break;
        case 'image/jpeg':
            $res = imagejpeg($i_dest,$dest,100);
            break;
        default:
            $res = FALSE;
        }
        return ($res != FALSE);
    }

    public static function format_filesize($size)
    {
        $mod = cms_utils::get_module('FileManager');
        if ($size < 2048) {
            $size = trim((string)$size);
            $unit = $mod->Lang('bytes');
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
