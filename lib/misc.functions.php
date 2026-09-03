<?php
#CMS Made Simple support functions
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
#$Id$
/**
 * Miscellaneous support functions
 *
 * @package CMS
 * @license GPL
 */

/**
 * Redirects to relative URL on the current site.
 *
 * If headers have not been sent this method will use header-based redirection.
 * Otherwise javascript redirection will be used.
 * @see also https://stackoverflow.com/questions/8028957/how-to-fix-headers-already-sent-error-in-php
 *
 * @author http://www.edoceo.com/
 * @since 0.1
 * @package CMS
 * @param string $to The url to redirect to
 */
function redirect($to)
{
    global $CMS_INSTALL_PAGE;

    $app = cmsms();
    if( $app->is_cli() ) {
        // cannot redirect cli based scripts
        die("ERROR: no redirect on cli based scripts ---\n");
    }
    $_SERVER['PHP_SELF'] = null; // aka unset

    $secure = $app->is_https_request();
    $schema = ($secure) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $components = parse_url($to);
    if( $components ) {
        $to = (isset($components['scheme']) && startswith($components['scheme'], 'http') ? $components['scheme'] : $schema) . '://';
        $to .= isset($components['host']) ? $components['host'] : $host;
        $to .= isset($components['port']) ? ':' . $components['port'] : '';
        if( isset($components['path']) ) {
            if( in_array(substr($components['path'],0,1),array('\\','/')) ) {
                //Path is absolute, just append.
                $to .= $components['path'];
            }
            //Path is relative, append current directory first.
            elseif( isset($_SERVER['PHP_SELF']) && !is_null($_SERVER['PHP_SELF']) ) { //Apache
                $to .= (strlen(dirname($_SERVER['PHP_SELF'])) > 1 ?  dirname($_SERVER['PHP_SELF']).'/' : '/') . $components['path'];
            }
            elseif( isset($_SERVER['REQUEST_URI']) && !is_null($_SERVER['REQUEST_URI']) ) { //Lighttpd
                if( endswith($_SERVER['REQUEST_URI'], '/') ) {
                    $to .= (strlen($_SERVER['REQUEST_URI']) > 1 ? $_SERVER['REQUEST_URI'] : '/') . $components['path'];
                }
                else {
                    $dn = dirname($_SERVER['REQUEST_URI']);
                    if( !endswith($dn,'/') ) $dn .= '/';
                    $to .= $dn . $components['path'];
                }
            }
        }
        $to .= isset($components['query']) ? '?' . $components['query'] : '';
        $to .= isset($components['fragment']) ? '#' . $components['fragment'] : '';
    }
    else {
        $to = $schema."://".$host."/".$to;
    }

    session_write_close();

    $debug = false;
    if( !isset($CMS_INSTALL_PAGE) ) {
        $config = $app->GetConfig();
        $debug = $config['debug'];
    }
    if( $debug ) {
        echo <<<EOS
Debug is on, automatic redirection is disabled...  Please click this link to continue.<br><br>
<a accesskey="r" href="$to">$to</a>

EOS;
        $reports = $app->get_errors();
        if( $reports ) {
            echo <<<EOS
<br><br>
<div id="DebugFooter">

EOS;
            foreach( $reports as $out ) {
                echo $out;
            }
            echo '</div><!-- end DebugFooter -->';
        }
        exit;
    }
    elseif( headers_sent() ) {
        // use javascript etc instead of a[nother] header
        // location.replace() and location.assign() fail if $to is not secure/https
        $detail = ($secure) ? "replace('$to')" : "href='$to'";
        //ALTERNATE <meta http-equiv="Refresh" content="0;url=$to">
        echo <<<EOS
<script>
 window.location.$detail;
</script>
<noscript>
 <html><head>
 <meta http-equiv="Location" content="$to">
 </head></html>
/noscript>

EOS;
        exit;
    }
    else {
        exit(header("Location: $to"));
    }
}


/**
 * Given a page ID or an alias, redirect to it.
 * Retrieves the URL of the specified page, and performs a redirect
 *
 * @param mixed $alias An integer page id or a string page alias.
 */
function redirect_to_alias($alias)
{
    $manager = CmsApp::get_instance()->GetHierarchyManager();
    $node = $manager->sureGetNodeByAlias($alias);
    if( !$node ) {
        // put mention into the admin log
        audit('','Core','Attempt to redirect to invalid alias: '.$alias);
        return;
    }
    $content = $node->GetContent();
    if( !is_object($content) ) {
        audit('','Core','Attempt to redirect to invalid alias: '.$alias);
        return;
    }
    if( $content->GetURL() != '' ) redirect($content->GetURL());
}


/**
 * Calculate the difference in seconds between two microtime() values.
 *
 * @since 0.3
 * @param string $a Earlier microtime value
 * @param string $b Later microtime value
 * @return int The difference.
 */
function microtime_diff($a,$b)
{
    list($a_dec, $a_sec) = explode(" ", $a);
    list($b_dec, $b_sec) = explode(" ", $b);
    return $b_sec - $a_sec + $b_dec - $a_dec;
}


/**
 * Join path components using the platform-specific directory separator.
 * Taken from: http://www.php.net/manual/en/ref.dir.php
 *
 * This method should NOT be used for building URLS.
 *
 * This method accepts a variable number of string arguments.
 * e.g.: cms_join_path($dir1,$dir2,$dir3,$filename)
 * or cms_join_path($dir1,$dir2,$filename)
 * It does not deal with adjacent separators resulting from
 * empty argument or argument already including separator(s)
 *
 * @since 0.14
 * @return string
 */
function cms_join_path()
{
    $args = func_get_args();
    return implode(DIRECTORY_SEPARATOR,$args);
}


/**
 * Return the relative portion of the supplied path
 *
 * @since 2.2
 * @author Robert Campbell
 * @param string $path The input path or file specification
 * @param string $relative_to Optional path to compute relative to. Default website root-path.
 * @return string The relative portion of $path. Maybe empty
 */
function cms_relative_path($path,$relative_to = '')
{
    $path = realpath(trim((string)$path));
    if( !$path ) return '';
    if( !$relative_to ) {
        $relative_to = CMS_ROOT_PATH;
    }
    $to = realpath(trim($relative_to));
    if( !$to ) return '';
    if( !startswith($path,$to) ) return '';

    return substr($path,strlen($to));
}


/**
 * Test if the provided filepath is absolute.
 * Note: Windoze current-directory relative paths like C:somewhere
 * (no path-leading \) will be reported as absolute, in case the caller
 * needs to strip the drive
 * @see https://learn.microsoft.com/en-us/windows/win32/fileio/naming-a-file#fully-qualified-vs-relative-paths
 * Any leading whitespace will be ignored. Any leading '../' or '..\'
 * will cause a false return. Any later '..' or '..' will be ignored.
 * Any './' or '.\' will be ignored.
 * TODO handle /A/B/C or \A\B\C where A is not CMS_ROOT_PATH
 *
 * @since 2.2.23F2
 * @param string $path The filesystem path to check. Can be empty.
 * @return bool indicating absolute
 */
function is_absolute_path($path)
{
    if( $path ) {
        if( preg_match('~^\s*\\.\\.[/\\\\]~', $path) ) {
            return false;
        }
        $test = preg_quote($path, '~');
        if( preg_match('~^\s*((?:\\.)?[/\\\\]|[C-Z][A-Z]?\\:(.)?)~i', $test, $matches) ) {
            if( !empty($matches[1]) ) { // && (empty($matches[2]) || $matches[2] == '\\' || $matches[2] == '/') ) { extra checks to distinguish Windoze current-directory-relative path
                return true;
            }
        }
    }
    return false;
}


/**
 * Return the accessor for private/restricted files, or a specific one of those.
 * Typically a server-accessible filepath, but possibly (in future) a web-hook.
 * No check of the determined value.
 * @since 2.2.23F2
 *
 * @param string $fn Optional file-basename Default ''
 * @param cms_config|null $config Optional config object Default null
 * @param array $context Optional permissions-limiter etc Default []
 * @return string maybe empty if $context is unacceptable
 */
function private_place($fn='',$config=null,$context=[])
{
    if( !$config ) {
        $config = cmsms()->GetConfig();
    }
    if( !empty($context['dbase']) ) {
        // cannot use dbase-recorded value
        //NOTE any change to this must be compatible with and manually migrated to installer wizard_step_4
        if( is_string($context['dbase']) ) {
            $rp = $context['dbase'];
        } else {
            $fp = dirname(debug_backtrace(3)[1]['file']).DIRECTORY_SEPARATOR.'dbPath';
            $rp = is_file($fp) ? file_get_contents($fp) : '';
        }
    } else {
        $rp = cms_siteprefs::get('privatePath');
    }
    if( $rp ) {
        $tmp = preg_replace('~\s*[,\\/]+\s*~', DIRECTORY_SEPARATOR, trim($rp,' \/'));
        $rp = preg_replace_callback('~\$config\[\s*([\'"])(.+)\1\s*\]~', function($m) use($config)
        {
            return $config[$m[2]];
        }, $tmp);
        // general test for non-absolute path: !preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $rp) WRONG e.g. \\\\ or drive letter(s) or C:somepath
        if( !(startswith($rp, CMS_ROOT_PATH) || is_absolute_path($rp)) ) {
            $fp = CMS_ROOT_PATH . DIRECTORY_SEPARATOR . $rp;
        }
        else {
            $fp = $rp;
        }
        $fp = realpath($fp);
        if( $fp ) {
            return ($fn) ? $fp . DIRECTORY_SEPARATOR . $fn : $fp;
        }
    }
    //default
    $fp = implode(DIRECTORY_SEPARATOR, [$config['admin_path'], 'configs', 'private']);
    return ($fn) ? $fp.DIRECTORY_SEPARATOR.$fn : $fp;
}

/**
 * Perform HTML entity conversion on a string.
 *
 * @see also cleanValue() and PHP's htmlentities() and htmlspecialchars()
 * @param string $val The input string
 * @param int $param Bit-flags indicating how quotes should be handled (see htmlentities) (UNUSED)
 * @param string $charset $val The input character set (UNUSED)
 * @param bool $convert_single_quotes A flag indicating wether single quotes should be converted to entities.
 * @return string the converted string.
 */
function cms_htmlentities($val,$param = ENT_QUOTES,$charset = 'UTF-8',$convert_single_quotes = false)
{
    if( $val == "" ) return "";

    $val = str_replace("&#032;"  , " "           , $val);
    $val = str_replace("&"       , "&amp;"       , $val);
    $val = str_replace("<!--"    , "&#60;&#33;--", $val);
    $val = str_replace("-->"     , "--&#62;"     , $val);
    $val = str_ireplace("<script", "&#60;script" , $val);
    $val = str_replace(">"       , "&gt;"        , $val);
    $val = str_replace("<"       , "&lt;"        , $val);
    $val = str_replace("\""      , "&quot;"      , $val);
    $val = preg_replace("/\\$/"  , "&#36;"       , $val); // TODO no need for regex?
    $val = str_replace("!"       , "&#33;"       , $val);
    $val = str_replace("'"       , "&#39;"       , $val); // BUT see below

    if( $convert_single_quotes ) {
        $val = str_replace("\\'", "&apos;", $val);
        $val = str_replace("'", "&apos;", $val);
    }

    return $val;
}


/**
 * Output a backtrace into the generated log file.
 *
 * @see debug_to_log, debug_bt
 *
 * @param array $trace Optional Event/Throwable trace data. Default [].
 * @param int $limit Optional depth-limit. See debug_backtrace(). Default 0.
 * @return void
 */
function debug_bt_to_log($trace = [],$limit = 0)
{
    $show = get_userid(false) != false; //a user is logged into Admin Console aka admin request?
    if( !$show ) {
        $config = cms_config::get_instance();
        $show = $config['debug_to_log']; //unlikely
    }
    if( $show ) {
        if( $trace ) {
            $num = max(0, (int)$limit);
            $bt = ($num == 0) ? $trace : array_slice($trace, 0, $num); //TODO properties 'file' 'line' 'class' 'type' 'function' 'args' ignore the latter if not disabled?
        }
        else {
            $bt = debug_backtrace(1, max(0, (int)$limit)); // Populate object and args indices
        }
        $file = $bt[0]['file'];
        $line = $bt[0]['line'];

        $out = array();
        $out[] = "Backtrace in $file on line $line";

        $bt = array_reverse($bt);
        foreach( $bt as $trace ) {
            if( $trace['function'] == 'debug_bt_to_log' ) continue;

            $file = '';
            $line = '';
            if( isset($trace['file']) ) {
                $file = $trace['file'];
                $line = $trace['line'];
            }
            $function = $trace['function'];
            $str = "$function";
            if( $file ) $str .= " at $file:$line";
            $out[] = $str;
        }

        $filename = TMP_CACHE_LOCATION . '/debug.log';
        foreach( $out as $txt ) {
            error_log($txt . "\n", 3, $filename);
        }
    }
}


/**
 * Generate a backtrace in a readable format.
 *
 * @param int $limit Optional depth-limit. See debug_backtrace(). Default 0.
 * @return void
 */
function debug_bt($limit = 0)
{
    $bt = debug_backtrace(1, max(0, (int)$limit)); // Populate object and args indices
    $file = $bt[0]['file'];
    $line = $bt[0]['line'];

    echo "\n\n<p><b>Backtrace in $file on line $line</b></p>\n";

    $bt = array_reverse($bt);
    echo "<pre><dl>\n";
    foreach( $bt as $trace ) {
        $file = $trace['file'];
        $line = $trace['line'];
        $function = $trace['function'];
        $args = implode(',', $trace['args']);
        echo "
        <dt><b>$function</b>($args) </dt>
        <dd>$file on line $line</dd>
        ";
    }
    echo "</dl></pre>\n";
}


/**
 * Debug function to display $var nicely in html.
 *
 * @param mixed $var The data to display
 * @param string $title (optional) title for the output.  If null memory information is output.
 * @param bool $echo_to_screen (optional) Flag indicating whether the output should be echoed to the screen or returned.
 * @param bool $use_html (optional) flag indicating whether html or text should be used in the output.
 * @param bool $showtitle (optional) flag indicating whether the title field should be displayed in the output.
 * @return string
 */
function debug_display($var,$title = '',$echo_to_screen = true,$use_html = true,$showtitle = true)
{
    global $starttime, $orig_memory;
    if( !$starttime ) $starttime = microtime();

    ob_start();

    if( $showtitle ) {
        $titleText = "Debug: ";
        if( $title ) $titleText = "Debug display of '$title':";
        $titleText .= '(' . microtime_diff($starttime,microtime()) . ')';
        if( function_exists('memory_get_usage') ) {
            $net = memory_get_usage() - $orig_memory;
            $titleText .= ' - (net usage: '.$net.')';
        }

        $memory_peak = (function_exists('memory_get_peak_usage')?memory_get_peak_usage():'');
        if( $memory_peak ) $titleText .= ' - (peak: '.$memory_peak.')';

        if( $use_html ) {
            echo "<div><b>$titleText</b>\n";
        }
        else {
            echo "$titleText\n";
        }
    }

    if( !empty($var) ) {
        if( $use_html ) echo '<pre>';
        if( is_array($var) ) {
            echo "Number of elements: " . count($var) . "\n";
            print_r($var);
        }
        elseif( is_object($var) ) {
            print_r($var);
        }
        elseif( is_string($var) ) {
            if( $use_html ) {
                print_r(htmlentities(str_replace("\t", '  ', $var)));
            }
            else {
                print_r($var);
            }
        }
        elseif( is_bool($var) ) {
            echo $var === true ? 'true' : 'false';
        }
        else {
            print_r($var);
        }
        if( $use_html ) echo '</pre>';
    }
    if( $use_html ) echo "</div>\n";

    $output = ob_get_contents();
    ob_end_clean();

    if( $echo_to_screen ) echo $output;
    return $output;
}


/**
 * Display $var nicely only if $config['debug'] (aka CMS_DEBUG) is set.
 *
 * @param mixed $var
 * @param string $title
 */
function debug_output($var,$title = '')
{
    $config = cms_config::get_instance();
    if( $config['debug'] ) debug_display($var, $title, true);
}


/**
 * Output formatted debug information about $var to a debug log file.
 *
 * @param mixed $var    data to display
 * @param string $title optional title.
 * @param string $filename optional output filename
 */
function debug_to_log($var,$title = '',$filename = '')
{
    $config = cms_config::get_instance();
    if( $config['debug_to_log'] || (function_exists('get_userid') && get_userid(false)) ) {
        if( $filename == '' ) {
            $filename = TMP_CACHE_LOCATION . '/debug.log';
            $now = time();
            $st = (is_file($filename)) ? @filemtime($filename) : $now;
            if( $st !== false && $st < ($now - 86400) ) unlink($filename);
        }
        $errlines = explode("\n",debug_display($var, $title, false, false, true));
        foreach( $errlines as $txt ) {
            error_log($txt . "\n", 3, $filename);
        }
    }
}


/**
 * Display $var nicely to the CmsApp::get_instance()->errors array if
 * $config['debug'] (aka CMS_DEBUG) is set.
 *
 * @param mixed $var
 * @param string $title
 */
function debug_buffer($var,$title = '')
{
    if( defined('CMS_DEBUG') && CMS_DEBUG ) { //might be not-yet-defined
       CmsApp::get_instance()->add_error(debug_display($var, $title, false, true));
    }
}


/**
 * Return the provided $value if it's non-null and the same basic type as
 * $default_value. Otherwise return $default_value, or
 * $_SESSION['parameter_values'][$session_key] if that's set.
 * Note: this function trim()'s non-numeric values, and records the
 * returned value as $_SESSION['parameter_values'][$session_key] if
 * $session_key is not falsy.
 * @ignore
 *
 * @param mixed $value Might be array
 * @param mixed $default_value Default ''
 * @param mixed $session_key Default ''
 * @return mixed
 */
function _get_value_with_default($value,$default_value = '',$session_key = '')
{
    if( $session_key ) {
        if( isset($_SESSION['default_values'][$session_key]) ) $default_value = $_SESSION['default_values'][$session_key];
    }

    // set our return value to the default initially and overwrite with $value if we like it.
    $return_value = $default_value;

    if( isset($value) ) {
        if( is_array($value) ) {
            // $value is an array - validate each element.
            $return_value = array();
            foreach( $value as $element ) {
                $return_value[] = _get_value_with_default($element, $default_value);
            }
        }
        elseif( is_numeric($default_value) ) {
            if( is_numeric($value) ) {
                $return_value = $value;
            }
        }
        else {
            $return_value = is_string($value) ? trim($value) : $value;
        }
    }

    if( $session_key ) $_SESSION['default_values'][$session_key] = $return_value;
    return $return_value;
}


/**
 * Return a named value from the specified container, or a variant of
 * such value, or a default value if $key is not a property/member/key
 * of $container or if $container[$key] is null or if $container[$key]
 * not an acceptable type.
 *
 * @param mixed $container Array or object implementing ArrayAcess
 * @param string $key Identifier of the wanted property
 * @param varargs $args Array with 0, 1 or 2 members:
 *  [0] mixed $default_value ('' is assumed if $args[0] is N/A)
 *  [1] string $session_key ('' is assumed if $args[0] is present but $args[1] is N/A or falsy)
 * If $session_key is not falsy, $_SESSION['parameter_values'][$session_key]
 * (if any) will be used instead of $default_value.
 * @return mixed
 * Note: this function trim()'s returned string values, and records the
 * returned value as $_SESSION['parameter_values'][$session_key] if
 * $session_key is not falsy.
 */
function get_parameter_value($container,$key,...$args)
{
    $session_key = (count($args) == 2 && $args[1]) ? trim((string)$args[1]) : '';
    if( $session_key && isset($_SESSION['parameter_values'][$session_key]) ) {
        $default_value = $_SESSION['parameter_values'][$session_key];
    }
    else {
        // support distinction between explicit and implicit empty-string value
        $default_value = ($args && array_key_exists(0, $args)) ? $args[0] : ''; // might be null
    }

    $return_value = $default_value;
    if( isset($container[$key]) ) { // OR array_key_exists($key, $container) back-incompatible check also for null value
        $found_value = $container[$key];
        // substitute the found value if we like it
        switch (gettype($default_value)) {
            case 'string':
                if( is_string($found_value) ) { $return_value = trim($found_value); }
                elseif( !$args ) { // implicit $default_value, so 'non-strict' type-comparison
                    if( is_numeric($found_value) ) { $return_value = (string)$found_value; }
                    elseif( is_bool($found_value) ) { $return_value = ($found_value) ? 'true' : 'false'; }
                    elseif( !is_scalar($found_value) ) { $return_value = $found_value; } // object, array etc verbatim
                }
                break;
            case 'integer':
                if( is_numeric($found_value) ) {
                    $tmp = (is_string($found_value)) ? 0 + $found_value : $found_value;
                    if( $tmp === (int)$tmp ) { $return_value = $tmp; }
                    else {
                        //round a float towards 0
                        $return_value = (int)floor($tmp);
                        if( $return_value < 0 ) $return_value++;
                    }
                }
                elseif( is_string($found_value) ) {
                    $return_value = 0;
                    $tmp = trim($found_value);
                    if( $tmp ) {
                        if( $tmp[0] == '0' ) {
                            if( $tmp[1] == 'x' && preg_match('/\G[0-9a-f]+$/i', $tmp, null, 0, 2) ) {
                                $return_value = (int)base_convert($tmp, 16, 10);
                            }
                            elseif( preg_match('/\G[0-7]+\s$/', $tmp, null, 0, 1) ) {
                                $return_value = (int)base_convert($tmp, 8, 10);
                            }
                            elseif( $tmp[1] == 'b' && preg_match('/\G[01]+$/', $tmp, null, 0, 2) ) {
                                $return_value = (int)base_convert($tmp, 2, 10);
                            }
                        }
                    }
                }
                break;
            case 'double':
                if( is_numeric($found_value) ) {
                    $return_value = (is_string($found_value)) ? 0.0 + $found_value : (float)$found_value;
                }
                break;
            case 'boolean':
                if( is_bool($found_value) ) { $return_value = $found_value; }
                elseif( is_numeric($found_value) ) { $return_value = (bool)$found_value; }
                elseif( is_string($found_value) ) { $return_value = cms_to_bool(trim($found_value)); }
                else { $return_value = ($found_value != false); }
                break;
            case 'array':
                if( is_array($found_value) ) { $return_value = $found_value; }
                break;
            default: // some other default type
                if( is_array($found_value) ) {
                    // process each member independently
                    $return_value = [];
                    foreach( $found_value as $element ) {
                        $return_value[] = _get_value_with_default($element, $default_value);
                    }
                }
                else {
                    $return_value = (!is_string($found_value)) ? $found_value : trim($found_value);
                }
        }
    }
    if( $session_key ) {
        $_SESSION['parameter_values'][$session_key] = $return_value; // record default for next/later call
    }
    return $return_value;
}


/**
 * Remove a permission from the permissions table.
 *
 * @internal
 * @ignore
 * @access private
 * @param string The permission name
 * @deprecated
 */
function cms_mapi_remove_permission($permission_name)
{
    try {
        $perm = CmsPermission::load($permission_name);
        $perm->delete();
    }
    catch( Exception $e ) {
    }
}


/**
 * Add a permission to the permissions table.
 *
 * @internal
 * @ignore
 * @access private
 * @param unknown (ignored)
 * @param string  The permission name
 * @param string  The permission human readable text.
 * @deprecated
 */
function cms_mapi_create_permission($cms,$permission_name,$permission_text)
{
    try {
        $perm = new CmsPermission();
        $perm->originator = 'Other';
        $perm->name = $permission_name;
        $perm->text = $permission_text;
        $perm->save();
        return true;
    }
    catch( Exception $e ) {
        return false;
    }
}


/**
 * Test if the provided directory and its contents and all
 * descendent-directories (if any) and their contents have write-permission.
 *
 * @param  string  $path Start directory.
 * @return bool
 */
function is_directory_writable($path)
{
    if( !is_dir($path) ) return false;

    if( $handle = opendir($path) ) {
        if( !endswith($path,DIRECTORY_SEPARATOR) ) $path .= DIRECTORY_SEPARATOR;
        while( false !== ($file = readdir($handle)) ) {
            if( $file == '.' || $file == '..' ) continue;

            $p = $path.$file;
            if( !@is_writable($p) ) return false;

            if( @is_dir($p) ) {
                if( !is_directory_writable($p) ) return false;
            }
        }
        closedir($handle);
        return true;
    }
    return false;
}


/**
 * Return a list of all or matching items in the given directory (not recursive)
 * @internal
 *
 * @param string $path Filepath of directory to scan
 * @param string $extensions Filename extension(s) to match. Comma-separated, any case. Default ''
 * @param bool $excludedot Whether to exclude items whose name starts with '.' Default true
 * @param bool $excludedir Whether to exclude directories. Default true
 * @param string $prefix Filename prefix to exclude or include (per $excludefiles) Default ''
 * @param bool $excludefiles Whether to ignore items whose name begins with $prefix Default true
 *   False to ignore items whose name does not begin with $prefix
 * @return array maybe empty
 */
function get_matching_files($path,$extensions = '',$excludedot = true,$excludedir = true,$prefix = '',$excludefiles = true)
{
    if( !is_dir($path) ) return [];
    $dh = opendir($path);
    if( !$dh ) return [];

    if( $extensions ) $extensions = explode(',',strtolower($extensions));
    $results = array();
    while( false !== ($file = readdir($dh)) ) {
        if( $file == '.' || $file == '..' ) continue;
        if( $excludedot && startswith($file,'.') ) continue;
        if( $excludedir && is_dir(cms_join_path($path,$file)) ) continue;
        if( $prefix ) {
            if( $excludefiles ) {
                if( startswith($file,$prefix) ) continue;
            }
            elseif( !startswith($file,$prefix) ) {
                continue;
            }
        }
        if( $extensions ) {
            $p = strrpos($file,'.');
            if( $p > 0 ) {
                $ext = strtolower(substr($file,$p + 1));
                if( !in_array($ext,$extensions) ) continue;
            }
        }
        $results[] = $file;
    }
    closedir($dh);
    return $results;
}


/**
 * Return a list of files and/or directories in and below the specified directory.
 *
 * @param  string  $path     Start path, may have trailing '\' or '/' char.
 * @param  array   $excludes Regular expression(s) indicating items to exclude.
 *         Each such expression will be used as "~expr~i".
 * @param  int     $maxdepth How deep to browse (-1=unlimited)
 * @param  string  $mode     "FULL"|"DIRS"|"FILES"
 * @param  int     $d        Recursion depth, for internal use only
 * @return string[] each including DIRECTORY_SEPARATOR and folder-paths end with a DIRECTORY_SEPARATOR
 */
function get_recursive_file_list($path,$excludes,$maxdepth = -1,$mode = 'FULL',$d = 0)
{
    $fn = function( $file, $excludes ) {
        if( !$excludes ) return false;
        // strip the path from the file
        $bn = basename($file);
        foreach( $excludes as $excl ) {
            if( @preg_match( "~$excl~i", $bn ) ) return true;
        }
        return false;
    };

    $path = rtrim($path, "/\\") . DIRECTORY_SEPARATOR;
    $dirlist = array();
    if( $mode != "FILES" ) { $dirlist[] = $path ; }
    if( $handle = opendir($path) ) {
        while( false !== ($file = readdir($handle)) ) {
            if( $file == '.' || $file == '..' ) continue;
            if( $excludes && $fn( $file, $excludes ) ) continue;

            $file = $path . $file ;
            if( ! @is_dir ( $file ) ) { if( $mode != "DIRS" ) { $dirlist[] = $file ; } }
            elseif( $d >= 0 && ($d < $maxdepth || $maxdepth < 0) ) {
                $result = get_recursive_file_list($file, $excludes, $maxdepth, $mode, $d + 1); //recurse
                $dirlist = array_merge($dirlist, $result);
            }
        }
        closedir($handle);
    }
    if( $d == 0 ) { natcasesort($dirlist); }
    return $dirlist;
}


/**
 * Recursively delete all files and folders in a directory; synonymous with rm -r.
 *
 * @param string $dirname The directory filepath
 * @return bool
 */
function recursive_delete($dirname)
{
    // all subdirectories and contents
    if( !is_dir($dirname) ) return true;
    $dir_handle = opendir($dirname);
    while( $file = readdir($dir_handle) ) {
        if( $file != "." && $file != ".." ) {
            if( !is_dir($dirname."/".$file) ) {
                if( !@unlink ($dirname."/".$file) ) {
                    closedir($dir_handle);
                    return false;
                }
            }
            else {
                recursive_delete($dirname."/".$file);
            }
        }
    }
    closedir($dir_handle);
    if( ! @rmdir($dirname) ) return false;
    return true;
}


/**
 * Recursively chmod all files and folders in a directory.
 *
 * @see chmod
 * @param string $path The start location
 * @param int $mode The octal mode
 * @return int|bool value returned by chmod() | false
 */
function chmod_r($path,$mode)
{
    if( !is_dir($path) ) return chmod($path, $mode);

    $dh = @opendir($path);
    if( !$dh ) return false;

    while( $file = readdir($dh) ) {
        if( $file == '.' || $file == '..' ) continue;

        $p = $path.DIRECTORY_SEPARATOR.$file;
        if( is_dir($p) ) {
            if( !@chmod_r($p, $mode) ) {
                closedir( $dh );
                return false;
            }
        }
        elseif( !is_link($p) ) {
            if( !@chmod( $p, $mode ) ) {
                closedir( $dh );
                return false;
            }
        }
    }
    @closedir($dh);
    return @chmod($path, $mode);
}


/**
 * Test whether one string starts with another.
 * @see also str_starts_with() PHP8+
 *
 * e.g. startswith('The Quick Brown Fox','The');
 *
 * @param string $str The string to test against
 * @param string $sub The search string
 * @return bool
 */
function startswith($str,$sub)
{
    $l = strlen($sub);
    return ( $l > 0 ) ? (strncmp($str, $sub, $l) == 0) : false;
}


/**
 * Test whether one string ends with another.
 * @see also str_ends_with() PHP8+
 *
 * e.g. endswith('The Quick Brown Fox','Fox');
 *
 * @param string $str The string to test against
 * @param string $sub The search string
 * @return bool
 */
function endswith($str,$sub)
{
    $l = strlen($sub);
    return ( $l > 0 ) ? (substr_compare($str, $sub, -$l, $l) == 0) : false;
}


/**
 * Convert the provided string into something more akin to the content of an URL.
 * This function is essentially a hasher for internal use.
 * NOTE: this function is not accurate/suitable for sanitization of an
 * actual valid URL or url-path. Historically, it has been used for that
 * purpose and thus for CmsRoute url-path, which is unfortunate.
 * @see also cms_utils::cleanUrlPath()
 *
 * @param string $alias String to convert, not necessarily a page-alias
 * @param bool $tolower Whether the output string should be converted to lower case. Default false
 * @param bool $withslash Whether slashes are allowed in $alias. Default false
 * @return string with only letter(s), digit(s), '_', '-', '.' and/or ' ' char(s)
 */
function munge_string_to_url($alias,$tolower = false,$withslash = false)
{
    // NOTE back-compatibility must be maintained
    $alias = trim((string)$alias);
    if( $tolower ) $alias = mb_strtolower($alias);

    // remove unwanted chars
    $expr = ($withslash) ? '/[^\pL_\-. \d\/]/u' : '/[^\pL_\-. \d]/u'; // could have \p{Nd} instead of \d
    $tmp = preg_replace($expr, '', $alias);

    // replace spaces and remove extra dashes
    $tmp = str_replace([' ', '---', '--'], ['-', '-', '-'], $tmp);
    return $tmp;
}


/**
 * Sanitize input to prevent against XSS and other nasty stuff.
 * Taken from cakephp (http://cakephp.org)
 * Licensed under the MIT License
 *
 * @internal
 * @param string $val input value
 * @return string
 */
function cleanValue($val)
{
    if( $val == "" ) return $val;
    //Replace odd spaces with safe ones
    $val = str_replace(" ", " ", $val);
    $val = str_replace(chr(0xCA), "", $val);
    //Encode any HTML to entities (including \n --> <br>)
    $_cleanHtml = function($string,$remove = false) {
        if( $remove ) {
            $string = strip_tags($string);
        }
        else {
            $patterns = array("/&(?!amp;)/", "/%/", "/</", "/>/", '/"/', "/'/", "/\(/", "/\)/", "/\+/", "/-/");
            $replacements = array("&amp;", "&#37;", "&lt;", "&gt;", "&quot;", "&#39;", "&#40;", "&#41;", "&#43;", "&#45;");
            $string = preg_replace($patterns, $replacements, $string);
        }
        return $string;
    };
    $val = $_cleanHtml($val);
    //Double-check special chars and remove carriage returns
    //For increased SQL security
    $val = preg_replace("/\\\$/", "$", $val);
    $val = preg_replace("/\r/", "", $val);
    $val = str_replace("!", "!", $val);
    $val = str_replace("'", "'", $val);
    //Allow unicode (?)
    $val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val);
    //Add slashes for SQL
    //$val = $this->sql($val);
    //Swap user-inputted backslashes (?)
    $val = preg_replace("/\\\(?!&amp;#|\?#)/", "\\", $val);
    return $val;
}


/**
 * Test if permissions and PHP configuration allow an
 * administrator to upload files to CMSMS.
 *
 * @internal
 * @return bool
 */
function can_admin_upload()
{
    /*
    if safe mode is enabled, check the owner of both files index.php
    and moduleinterface.php and both folders uploads and modules.
    if those owners are all the same, then subject to permissions, files
    can be uploaded.
    if safe mode is off, just check the permissions.
    */
    $file_index = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'index.php';
    $config = cms_config::get_instance();
    $file_moduleinterface = $config['admin_path'].DIRECTORY_SEPARATOR.'moduleinterface.php';
    $dir_uploads = $config['uploads_path'];
    $dir_modules = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'modules';

    $stat_index = @stat($file_index);
    $stat_moduleinterface = @stat($file_moduleinterface);
    $stat_uploads = @stat($dir_uploads);
    $stat_modules = @stat($dir_modules);

    $my_uid = @getmyuid();

    if( $my_uid === false || $stat_index == false ||
      $stat_moduleinterface == false || $stat_uploads == false ||
      $stat_modules == false ) {
        // couldn't get some necessary information.
        return false;
    }

    $safe_mode = ini_get_boolean('safe_mode');
    if( $safe_mode ) {
        // we're in safe mode.
        if( ($stat_moduleinterface[4] != $stat_modules[4]) ||
            ($stat_moduleinterface[4] != $stat_uploads[4]) ||
            ($my_uid != $stat_moduleinterface[4]) ) {
            // owners don't match
            return false;
        }
    }

    // now check to see if we can write to the directories
    if( !is_writable($dir_modules) ) return false;
    if( !is_writable($dir_uploads) ) return false;

    // It all worked.
    return true;
}


/**
 * Return the boolean value corresponding to the given a PHP ini key
 * that represents a bool.
 *
 * @param string $str The PHP ini key
 * @return bool
 */
function ini_get_boolean($str)
{
    $val1 = ini_get($str);
    $val2 = strtolower($val1);

    $ret = 0;
    if( $val2 == 1 || $val2 == '1' || $val2 == 'yes' || $val2 == 'true' || $val2 == 'on' ) $ret = 1;
    return $ret;
}


/**
 * Output a human readable function stack trace.
 * This function uses echo.
 */
function stack_trace()
{
    $stack = debug_backtrace();
    foreach( $stack as $elem ) {
        if( $elem['function'] == 'stack_trace' ) continue;
        if( isset($elem['file'])  ) {
            echo $elem['file'].':'.$elem['line'].' - '.$elem['function'].'<br>';
        }
        else {
            echo ' - '.$elem['function'].'<br>';
        }
    }
}


/**
 * A wrapper around move_uploaded_file that attempts to ensure permissions
 * on uploaded files are set correctly.
 * Moving executable files is blocked.
 *
 * @param string $tmpfile The temporary file specification
 * @param string $destination The destination file specification
 * @return bool indicating success
 */
function cms_move_uploaded_file($tmpfile,$destination)
{
    // reject browser-executable files
    $helper = new CMSMS\FileTypeHelper();
    if( $helper->is_executable($destination) ) {
        //TODO report|log error or throw new Exception(lang(''))
        return false;
    }

    if( !@move_uploaded_file( $tmpfile, $destination ) ) return false;
    @chmod($destination,octdec($config['default_upload_permission']));
    return true;
}


/**
 * Test whether the provided IP address matches a list of expressions.
 * Credits to J.Adams <jna@retins.net>
 *
 * Expressions can be of the form
 *   xxx.xxx.xxx.xxx        (exact)
 *   xxx.xxx.xxx.[yyy-zzz]  (range)
 *   xxx.xxx.xxx.xxx/nn    (nn = # bits, cisco style -- i.e. /24 = class C)
 *
 * @param string $ip IP address to test
 * @param array  $checklist Array of match expressions
 * @return bool
 * Rolf: only used in lib/content.functions.php
 */
function cms_ipmatches($ip,$checklist)
{
    $_testip = function($range,$ip) {
        $result = true;

        // IP Pattern Matcher
        // J.Adams <jna@retina.net>
        //
        // Matches:
        //
        // xxx.xxx.xxx.xxx        (exact)
        // xxx.xxx.xxx.[yyy-zzz]  (range)
        // xxx.xxx.xxx.xxx/nn    (nn = # bits, cisco style -- i.e. /24 = class C)
        //
        // Does not match:
        // xxx.xxx.xxx.xx[yyy-zzz]  (range, partial octets nnnnnot supported)

        $regs = array();
        if( preg_match("/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)\/([0-9]+)/",$range,$regs) ) {
            // perform a mask match
            $ipl = ip2long($ip);
            $rangel = ip2long($regs[1] . "." . $regs[2] . "." . $regs[3] . "." . $regs[4]);

            $maskl = 0;

            for( $i = 0; $i < 31; $i++ ) {
                if( $i < $regs[5]-1) $maskl = $maskl + pow(2,(30-$i) );
            }

            return ($maskl & $rangel) == ($maskl & $ipl);
        }
        else {
            // range based
            $maskocts = explode('.',$range);
            $ipocts = explode('.',$ip);

            if( count($maskocts) != count($ipocts) && count($maskocts) != 4 ) return false;

            // perform a range match
            for( $i = 0; $i < 4; $i++ ) {
                if( preg_match("/\[([0-9]+)\-([0-9]+)\]/",$maskocts[$i],$regs) ) {
                    if( ($ipocts[$i] > $regs[2]) || ($ipocts[$i] < $regs[1]) ) $result = false;
                }
                else {
                    if( isset($maskocts[$i]) && isset($ipocts[$i]) && ($maskocts[$i] <> $ipocts[$i]) ) $result = false;
                }
            }
        }
        return $result;
    }; // _testip

    if( !is_array($checklist) ) $checklist = explode(',',$checklist);
    foreach( $checklist as $one ) {
        if( $_testip(trim($one),$ip) ) return true;
    }
    return false;
}


/**
 * Extension of PHP version_compare which supports versions including
 * non-'standardised' letters, which might be present in CMSMS
 * version-numbers etc
 * @since 2.2.19F2
 *
 * @param string $v1
 * @param string $v2
 * @return int -1, 0 or 1 according to whether $v1 is regarded <, = or > $v2
*/
function cmsversion_compare($v1,$v2)
{
    if( strcasecmp($v1, $v2) == 0 ) { return 0; }
    $comp = [$v1, $v2];
    foreach( $comp as $i=>$vi ) {
        if( preg_match('/([a-z]+)/i',$vi) ) {
            $c = preg_replace('/([^a-z])([ce-oqs-z])/i','$1.0$2',$vi);
            if( $c != $vi ) {
                $comp[$i] = $c;
            }
        }
    }
    return version_compare($comp[0],$comp[1]);
}


/**
 * Test if the provided string is a valid email address.
 *
 * @return bool
 * @param string  $email
 * @param bool $checkDNS
*/
function is_email($email,$checkDNS = false)
{
    //PHP's FILTER_VALIDATE_EMAIL mechanism is incomplete (per RFC5321) - see notes at https://www.php.net/manual/en/function.filter-var.php
    if( !filter_var($email,FILTER_VALIDATE_EMAIL) ) return false;
    //$email = (string)$email; if( !preg_match('/\S+.*@[\w.\-\x80-\xff]+$/',$email) ) return false;
    $parts = explode('@',$email);
    if( count($parts) != 2 || !$parts[0] || !$parts[1] ) return false;
    if( $checkDNS && function_exists('checkdnsrr') ) {
        if( !(checkdnsrr($parts[1], 'A') || checkdnsrr($parts[1], 'MX')) ) return false; // Domain doesn't actually exist
    }
    return true;
}


/**
 * Get the security parameter used in all admin links.
 * UNUSED
 *
 * @internal
 * @access private
 * @return string
 */
function get_secure_param()
{
    $urlext = '?';
/*
BAD including a session identifier means exposing implementation detail in URLs
    $str = ini_get('session.use_cookies');
    if( $str == '0' || strcasecmp($str,'off') == 0 ) {
        if( defined('SID') ) {
            $urlext .= htmlspecialchars(SID).'&';
        }
    }
*/
    $urlext .= CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
    return $urlext;
}


/**
 * Convert the provided value to a corresponding boolean.
 * Reports true for strings '1', 'y', 'yes', 'true', 'on' (all case insensitive),
 * true for numerics > 0 or < 0, true for boolean true, false for all other values.
 *
 * @param mixed $val Value to test. Normally a scalar.
 * @return bool
 */
function cms_to_bool($val)
{
    if( is_numeric($val) ) return ((int)$val != 0);
    if( is_bool($val) ) return $val;
    if( !$val ) return false;

    $val = strtolower((string)$val); // (string)true == '1'
    return ($val == 'y' || $val == 'yes' || $val == 'true' || $val === 'on' || $val == '1');
}


/**
 * Return the appropriate HTML tags to include the CMSMS included jquery in a web page.
 * CMSMS is distributed with a version of jQuery, jQueryUI and various
 * other jQuery-based libraries.
 * This function generates the HTML code that will include these scripts.
 * @since 1.10
 *
 * See the {cms_jquery} Smarty plugin for a convenient way of including
 * the CMSMS-provided jQuery libraries from within a Smarty template.
 *
 * Default included (and excludable) libraries:
 *  jquery
 *  jquery-ui
 *  json
 *  migrate
 *  ui_touch_punch
 *  cms_admin
 *  cms_js_setup
 * Other libraries (appendable here but in any case, loaded where
 * needed via 'admin_add_headtext' hook)
 *  cms_autorefresh
 *  cms_dirtyform
 *  cms_filepicker
 *  cms_hiersel added when selector created see CreateHierarchyDropdown()
 *  cms_lock
 *  nestedSortable
 *
 * @param string $exclude A comma separated list of script names or aliases to exclude.
 * @param bool $ssl Force use of the ssl_url for the root url to necessary scripts.
 * @param bool $cdn Force the use of a CDN url for the libraries if one is known
 * @param string $append A comma separated list of library URLS to the output
 * @param string $custom_root A custom root URL for all scripts (when using local mode).
 *   If this is specified the $ssl param will be ignored.
 * @param bool $include_css Optionally output stylesheet tags for the included javascript libraries.
 */
function cms_get_jquery($exclude = '',$ssl = false,$cdn = false,$append = '',$custom_root = '',$include_css = true)
{
    $config = cms_config::get_instance();
    $base_url = $config->smart_root_url(); //TODO deprecated since 2.2 use CMS_ROOT_URL
    if( $ssl ) $base_url = $config['ssl_url']; //TODO deprecated since 2.2
    $basePath = ($custom_root) ? trim($custom_root,'/') : $base_url;

    $scripts = array();
    // Scripts to include NOTE keep {cms_jquery} tag help reconciled with the following
    $scripts['jquery'] = array('cdn'=>'https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js',
                             'sri'=>'sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=',
                             'local'=>$basePath.'/lib/jquery/js/jquery-2.2.4.min.js',
                             'aliases'=>array('jquery.min.js','jquery',));
    $scripts['jquery-ui'] = array('cdn'=>'https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.js',
                                'sri'=>'sha256-sw0iNNXmOJbQhYFuC9OF2kOlD5KQKe1y5lfBn4C9Sjg=',
                                'local'=>$basePath.'/lib/jquery/js/jquery-ui-1.13.3.custom.min.js',
                                'aliases'=>array('jquery-ui.min.js','ui'),
                                'css_cdn'=>'https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.css', //avoid this, not customised
                                'css_sri'=>'sha256-dqprBIh+sKyQRTeSSQMXcNOfMzTyw/RrKvERIpmkZXA=',
                                'css'=>$basePath.'/lib/jquery/css/smoothness/jquery-ui-1.13.3.custom.min.css');
    //TODO Use native JSON.stringify (browsers with ES5 capability, since 2010 or so)
    //CMSMS since 2.0 (OneEleven theme) has used JSON.stringify() directly
    $scripts['json'] = array('local'=>$basePath.'/lib/jquery/js/json2.min.js'); // remove me ASAP
    $scripts['migrate'] = array('local'=>$basePath.'/lib/jquery/js/jquery-migrate-1.4.1.min.js');

    if( CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE) ) {
        $scripts['ui_touch_punch'] = array('local'=>$basePath.'/lib/jquery/js/jquery.ui.touch-punch.min.js');
        global $CMS_LOGIN_PAGE;
        if( isset($_SESSION[CMS_USER_KEY]) && !isset($CMS_LOGIN_PAGE) ) {
            require_once __DIR__.DIRECTORY_SEPARATOR.'js_setup.php';
            $scripts['cms_js_setup'] = array('local'=>$runtimeurl);
        }
        $scripts['cms_admin'] = array('local'=>$basePath.'/lib/jquery/js/jquery.cmsms_admin.js');
    }

    // Check if we need to exclude some script(s)
    if( !empty($exclude) ) {
        $exclude_list = explode(",", trim(str_replace(' ','',$exclude)));
        foreach( $exclude_list as $one ) {
            $one = trim(strtolower($one));

            // find a match
            $found = '';
            foreach( $scripts as $key => $rec ) {
                if( strtolower($one) == strtolower($key) ) {
                    $found = $key;
                    break;
                }
                if( isset($rec['aliases']) && is_array($rec['aliases']) ) {
                    foreach( $rec['aliases'] as $alias ) {
                        if( strtolower($one) == strtolower($alias) ) {
                            $found = $key;
                            break;
                        }
                    }
                    if( $found ) break;
                }
            }

            if( $found ) unset($scripts[$found]);
        }
    }

    // optionally add stuff to the end e.g. a jQuery plugin or stylesheet
    if( !empty($append) ) {
        $internals = array(
        'cms_autorefresh' => 'jquery.cmsms_autorefresh',
        'cms_dirtyform' => 'jquery.cmsms_dirtyform',
        'cms_filepicker' => 'jquery.cmsms_filepicker',
        'cms_hiersel' => 'jquery.cmsms_hierselector',
        'cms_lock' => 'jquery.cmsms_lock',
        'nestedSortable' => 'jquery.mjs.nestedSortable.min');

        $append_list = explode(',',trim(str_replace(' ','',$append)));
        foreach( $append_list as $key => $item ) {
            if( isset($internals[$item]) ) {
                $scripts[$item] = array('local'=>$basePath.'/lib/jquery/js/'.$internals[$item].'.js');
            }
            else {
                $scripts['user_'.$key] = array('local'=>$item);
            }
        }
    }

    // Output
    $output = '';
    $fmt_js = '<script src="%s"></script>';
    $fmt_sjs = '<script src="%s" integrity="%s" crossorigin="anonymous"></script>'; // CDN-sourced, not customised
    $fmt_css = '<link href="%s" rel="stylesheet">';
    $fmt_scss = '<link href="%s" integrity="%s" crossorigin="anonymous" rel="stylesheet">'; // CDN-sourced, not customised
    foreach( $scripts as $script ) {
        //TODO check logic here
        if( !empty($script['css']) && $include_css ) {
            if( $cdn && !empty($script['css_cdn']) ) {
                $url = $script['css_cdn'];
                if( isset($script['css_sri']) ) {
                    $hash = $script['css_sri'];
                    $output .= sprintf($fmt_scss,$url,$hash)."\n";
                }
                else {
                    $output .= sprintf($fmt_css,$url)."\n";
                }
            }
            else {
                $url = $script['css'];
                $output .= sprintf($fmt_css,$url)."\n";
            }
        }
        if( $cdn && !empty($script['cdn']) ) {
            $url = $script['cdn'];
            if( isset($script['sri']) ) {
                $hash = $script['sri'];
                $output .= sprintf($fmt_sjs,$url,$hash)."\n";
            }
            else {
                $output .= sprintf($fmt_js,$url)."\n";
            }
        }
        else {
            $url = $script['local'];
            $output .= sprintf($fmt_js,$url)."\n";
        }
    }
    return $output;
}


/**
 * @ignore
 * @since 2.0.2
 */
function setup_session($cachable = false)
{
    global $CMS_INSTALL_PAGE, $CMS_ADMIN_PAGE;
    static $_setup_already = false;
    if( $_setup_already ) return;

    if( headers_sent($_f,$_l) ) {
        throw new \LogicException("Attempt to set headers, but headers were already sent: $_f line $_l");
    }

    if( $cachable ) {
        if( $_SERVER['REQUEST_METHOD'] != 'GET' || isset($CMS_ADMIN_PAGE) || isset($CMS_INSTALL_PAGE) ) $cachable = false;
    }
    if( $cachable ) $cachable = (int) cms_siteprefs::get('allow_browser_cache',0);
    if( !$cachable ) {
        // admin pages can't be cached... period, at all.. never.
        @session_cache_limiter('nocache');
    }
    else {
        // frontend request
        $expiry = (int)max(0,cms_siteprefs::get('browser_cache_expiry',60));
        @session_cache_expire($expiry);
        @session_cache_limiter('public');
    }

    // setup session with different id and start it
    $session_name = 'CMSSESSID'.substr(md5(__DIR__.CMS_VERSION),0,12);
    if( !isset($CMS_INSTALL_PAGE) ) {
        @session_name($session_name);
        @ini_set('url_rewriter.tags', '');
        @ini_set('session.use_trans_sid', 0);
    }

    if( isset($_COOKIE[$session_name]) ) {
        // validate the contents of the cookie.
        if( !preg_match('/^[a-zA-Z0-9,-]{22,40}$/', $_COOKIE[$session_name]) ) {
            session_id( uniqid() );
            session_start();
            session_regenerate_id();
        }
    }
    if( !@session_id() ) session_start();

    if( $cachable ) header_remove('Last-Modified');
    $_setup_already = true;
}


/**
 * Test if the provided string is base64-encoded
 *
 * @since 2.2
 * @param string $s The input string
 * @return bool
 */
function is_base64($s)
{
    if( !preg_match('~^[a-zA-Z0-9/+\r\n]{2,}={0,2}$~', $s) ) {
        return false;
    }
    $t1 = base64_decode($s,true);
    if( !$t1 ) {
        return false;
    }
    $t2 = base64_encode($t1);
    if( $t2 != $s ) {
        return false;
    }
    //TODO more discrimination e.g. page10 is valid but extremely unlikely as an encoded string
    //e.g. preg_match('~[\x00-\x08\x0b\x0c\x0e-\x1f]~', should-be-text $var)
    return true;
}
