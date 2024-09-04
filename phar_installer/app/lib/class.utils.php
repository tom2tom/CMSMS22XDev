<?php

namespace cms_autoinstaller;

use Exception;
use function __appbase\get_app;
use function __appbase\lang;

final class utils
{
    private function __construct() {}

    /**
     * Compare 2 CMSMS-version numbers (etc ) which might include non-'standardized' chars
     * hence special comparison of versions including letter(s) other than:
     *  'dev' < 'a[lpha]< 'b[eta]' <'rc' < '#' < 'p[l]' pre?
     *
     * @param string $v1
     * @param string $v2
     * @return int -1, 0, 1
     */
    public static function cms_version_compare($v1, $v2)
    {
        if (strcasecmp($v1, $v2) == 0) {
            return 0;
        }
        $versions = [$v1, $v2];
        foreach ($versions as $i => $vi) {
            if (preg_match('/([a-z]+)/i', $vi)) {
               $c = preg_replace('/([^a-z])([ce-oqs-z])/i', '$1.0$2', $vi);
               if ($c != $vi) {
                   $versions[$i] = $c;
               }
            }
        }
        return version_compare($versions[0], $versions[1]);
    }

    // get the list of versions we can upgrade from.
    public static function get_upgrade_versions()
    {
        $app = get_app();
        $app_config = $app->get_config();
        $min_upgrade_version = $app_config['min_upgrade_version'];
        if( !$min_upgrade_version ) throw new Exception(lang('error_invalidconfig'));

        $dir = $app->get_appdir().'/upgrade';
        if( !is_dir($dir) ) throw new Exception(lang('error_internal','u100'));
        $dh = opendir($dir);
        if( !$dh ) throw new Exception(lang('error_internal','u101'));

        $versions = array();
        while( ($file = readdir($dh)) !== false ) {
            if( $file == '.' || $file == '..' ) continue;
            if( is_dir($dir.'/'.$file) &&
                (is_file("$dir/$file/MANIFEST.DAT.gz") || is_file("$dir/$file/MANIFEST.DAT") || is_file("$dir/$file/upgrade.php")) ) {
                if( self::cms_version_compare($min_upgrade_version, $file) <= 0 ) $versions[] = $file;
            }
        }
        closedir($dh);
        if( count($versions) > 1 ) {
            //accommodate special sorting of versions including: 'dev' < 'a[lpha]< 'b[eta]' <'rc' < '#' < 'p[l]' pre?
            $care = preg_grep('/([a-z]+)/i',$versions);
            if( $care ) {
                foreach( $care as $k => $fixer ) {
                   $q = preg_replace('/([^a-z])([ce-oqs-z])/i','$1.0$2',$fixer);
                   if( $q != $fixer ) {
                       $versions[$k] = $q;
                   }
                }
                uasort($versions,'version_compare');
                $versions = array_values(array_replace($versions,$care));
            } else {
                usort($versions,'version_compare');
            }
        }
        return $versions;
    }

    public static function get_upgrade_changelog($version)
    {
        // it is not an error to not have a changelog file
        $app = get_app();
        $dir = $app->get_appdir()."/upgrade/$version";
        if( !is_dir($dir) ) throw new Exception(lang('error_internal','u110'));
        $files = array('CHANGELOG.txt','CHANGELOG.TXT','changelog.txt');
        foreach( $files as $fn ) {
            if( is_file("$dir/$fn") ) {
                // convert text into some sort of html
                $tmp = @file_get_contents("$dir/$fn");
                $tmp = nl2br(wordwrap(htmlspecialchars($tmp),80));
                return $tmp;
            }
        }
        return '';
    }

    public static function get_upgrade_readme($version)
    {
        // it is not an error to not have a readme file
        $app = get_app();
        $dir = $app->get_appdir()."/upgrade/$version";
        if( !is_dir($dir) ) throw new Exception(lang('error_internal','u210'));
        $files = array('README.HTML.INC','readme.html.inc','README.HTML','readme.html');
        foreach( $files as $fn ) {
            if( is_file("$dir/$fn") ) return @file_get_contents("$dir/$fn");
        }
        if( is_file("$dir/readme.txt") ) {
            // convert text into some sort of html.
            $tmp = @file_get_contents("$dir/readme.txt");
            $tmp = nl2br(wordwrap(htmlspecialchars($tmp),80));
            return $tmp;
        }
        return '';
    }
} // end of class

?>
