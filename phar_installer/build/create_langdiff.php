#!/usr/bin/env php
<?php
/*
NOTE interactive mode uses PHP extensions & methods which are *NIX-only
 i.e. interactive mode is not for Windoze.

Requires:
 PHP readline if in interactive mode
Prefers:
 PHP extension pcntl if in interactive mode
*/

$_cli = php_sapi_name() == 'cli';
$_scriptname = basename(__FILE__);
$resep = strpos(__FILE__, '\\') !== false;
// default config params
$svn_root = 'http://svn.cmsmadesimple.org/svn/cmsmadesimple';
$uri_from = 'svn://';
$uri_to = 'file://';
// other params
$_debug = false;
$_interactive = false; //$_cli && (DIRECTORY_SEPARATOR !== '/');  //always false on windows
$_tmpdir = sys_get_temp_dir().DIRECTORY_SEPARATOR.basename(__FILE__,'php').getmypid();
$_tmpfile = $_tmpdir.DIRECTORY_SEPARATOR.'tmp.out';
$_configname = str_replace('.php', '.ini', $_scriptname);
$_configfile = get_config_file();
$outfile = '';
$diff = 0;
$verbose = 0;

// translations @ http://svn.cmsmadesimple.org/svn/translatecenter/modules/*
//FROM                 FOR
$placesmap = [
'Core_cms2'          =>'admin/lang',
'cms_selflink'       =>'lib/lang/cms_selflink',
'admin_cms2_help'    =>'lib/lang/help',
'admin_cms2_tags'    =>'lib/lang/tags',
'Tasks'              =>'lib/lang/tasks',
'adminsearch_cms2'   =>'modules/AdminSearch/lang',
'contentmanager_cms2'=>'modules/CMSContentManager/lang',
'CmsJobManager'      =>'modules/CmsJobManager/lang',
'cmsmailer_cms2'     =>'modules/CMSMailer/lang',
'designmanager_cms2' =>'modules/DesignManager/lang',
'filemanager_cms2'   =>'modules/FileManager/lang',
'FilePicker_cms2'    =>'modules/FilePicker/lang',
'microtiny_cms2'     =>'modules/MicroTiny/lang',
'modulemanager_cms2' =>'modules/ModuleManager/lang',
'navigator_cms2'     =>'modules/Navigator/lang',
'news_cms2'          =>'modules/News/lang',
'search_cms2'        =>'modules/Search/lang',
'cmspharinstall'     =>'phar_installer/app/lang/app',
];

$src_excludes = [
'~\.git.*~',
'~\.svn~',
'~svn\-.*~',
'~scripts~',
'~tests~',
'~UNUSED~',
'~HIDE~',
'~DEVELOP~',
'~uploads~',
];

if ($_cli) {
    $opts = getopt('ic:de:f:hkm:nwo:p:r::t::', [
    'ask', //-i : interactive
    'config',
    'debug',
    'dnd',  //-e : ??
    'from',
    'help',
    'md5',  //-k : checksum
    'mode',
    'nocompress',
    'nowrite', //-w TODO : preserve current config file
    'outfile',
    'pack',
    'root',
    'to',
    ]);
    // parse config-file argument
    $val = (isset($opts['c'])) ? $opts['c'] : ((isset($opts['config'])) ? $opts['config'] : '');
    if ($val) {
        $_configfile = $val;
    }
}

// attempt to read config file, if the user wants
if ($_configfile && $_configfile != '-') {
    if (!is_readable($_configfile)) {
        fatal("No valid config file at: $_configfile");
    }
    $_config = parse_ini_file($_configfile, false, INI_SCANNER_TYPED);
    if ($_config === false) {
        fatal("Problem processing config file: $_configfile");
    }
    info('Read config file from '.$_configfile);
    extract($_config);
}

if ($_cli) {
    // parse other command arguments
    foreach ($opts as $key => $val) {
        switch ($key) {
            case 'd':
            case 'debug':
                $_debug = true;
                break;

            case 'f':
            case 'from':
                $uri_from = trim($val);
                break;

            case 'h':
            case 'help':
                usage();
                exit;

            case 'i':
            case 'ask':
                if (DIRECTORY_SEPARATOR !== '/') {
                    $_interactive = true;
                } else {
                    fatal('Prompted input of parameters is not supported on Windows');
                }
                break;

            case 'o':
            case 'outfile':
                $val = trim($val);
                $outfile = $val;
                break;

            case 'r':
            case 'root':
                $svn_root = trim($val);
                break;

            case 't':
            case 'to':
                $uri_to = trim($val);
                break;

        }
    }
}

if (!$_interactive && DIRECTORY_SEPARATOR !== '/' &&
    !($uri_from && $uri_to &&
    ($svn_root || !(startswith($uri_from, 'svn://') || startswith($uri_to, 'svn://'))) &&
    $outfile && $mode)) {
    $_interactive = true;
}

// interactive mode
if ($_cli && $_interactive) {
    if (!function_exists('readline')) {
        fatal('Abort '.$_scriptname.' : PHP readline extension is missing');
    }
    if (!extension_loaded('pcntl')) {
        info($_scriptname.' works better with pcntl extension');
    }
    if (function_exists('pcntl_signal')) {
        @pcntl_signal(SIGTERM, 'sighandler');
        @pcntl_signal(SIGINT, 'sighandler');
    }

    $uri_from = ask_string("Enter 'comparison' fileset uri, or TC for translation-center files", $uri_from);
    $uri_to = ask_string("Enter 'release' fileset uri", $uri_to);
    if (startswith($uri_from, 'svn://') || startswith($uri_to, 'svn://')) {
        $svn_root = ask_string('Enter svn repository root url', $svn_root);
    }
    $outfile = ask_string('Enter manifest file name', $outfile);
    $mode = ask_options('Enter manifest mode (d|n|c|f)', ['d', 'n', 'c', 'f'], $mode);
}

// validate the config
if (empty($uri_from)) { //TODO if sources from svn Translation Center
    fatal("No 'reference' file-set source provided");
}
if (!preg_match('~((file|svn|git)://|TC)~', $uri_from)) {
    fatal("Unrecognised 'reference' file-set source. Specify file://... or git://... or svn://... or TC");
}
if (empty($uri_to)) {
    fatal("No 'release' file-set source provided");
}
if (!preg_match('~(file|svn|git)://~', $uri_to)) {
    fatal("Unrecognised 'release' file-set source. Specify file://... or git://... or svn://...");
}
if ($uri_from == $uri_to) {
    fatal('Must process two different file-sets. ' .$uri_from. ' was specified for both');
}
if (startswith($uri_from, 'svn://') || startswith($uri_to, 'svn://')) {
    if (empty($svn_root)) {
        fatal('No repository root found');
    }
    if (!endswith($svn_root, '/')) {
        $svn_root .= '/';
    }
}
if (startswith($uri_from, 'file://')) {
    $file = substr($uri_from, 7);
    if ($file === '' || $file == 'local') {
        $uri_from = 'file://local';
    } elseif (!is_dir($file) || !is_readable($file)) {
        fatal('Specified file-set source ' .$file. ' is not accessable');
    }
}
if (startswith($uri_to, 'file://')) {
    $file = substr($uri_to, 7);
    if ($file === '' || $file == 'local') {
        $uri_to = 'file://local';
    } elseif (!is_dir($file) || !is_readable($file)) {
        fatal('Specified file-set source ' .$file. ' is not accessable');
    }
}

// begin the work

// create empty temp directories to hold the filesets
if (!(is_writable($_tmpdir) || mkdir($_tmpdir, 0777))) {// generic perms, pending actuals for istallation
    fatal('Temp folder is not writable');
}
$_fromdir = $_tmpdir.DIRECTORY_SEPARATOR.'_from';
if (is_dir($_fromdir)) {
    rrmdir($_fromdir);
}
mkdir($_fromdir, 0777);
$_todir = $_tmpdir.DIRECTORY_SEPARATOR.'_to';
if (is_dir($_todir)) {
    rrmdir($_todir);
}
mkdir($_todir, 0777);

// retrieve sources
if ($uri_from == "TC") {
    if ($verbose == 0) info('Retrieving translations from Translation Center svn');
    $bp = $_fromdir.'/svntranslations';
    foreach ($placesmap as $fromname => $toplace) {
        $fp = joinpath($bp, $toplace, 'ext');
        mkdir($fp, 0777, true);
        verbose(1,"Fetching $fromname translations");
        try {
           $url = 'http://svn.cmsmadesimple.org/svn/translatecenter/modules/'.$fromname.'/lang/ext';
           $cmd = "svn checkout $url@HEAD --force --non-interactive --trust-server-cert $fp";
           $out = exec($cmd);
           $res = ($out != false); // OR includes some specific content e.g. 'Retrieved...'
        } catch (Throwable $t) {
            info('Translation '.$fromname.': '.$t->GetMessage());
            $res = false;
            break;
        }
    }
    if (!$res) {
        fatal('Retrieving translations from svn failed');
    }
} else {
    try {
        $res = get_sources($uri_from, $_fromdir);
    } catch (Throwable $t) {
        info($t->GetMessage());
        $res = false;
    }
    if (!$res) {
        fatal('Retrieving files from ' .$uri_from. ' failed');
    }
    if (0) { //TODO from-version < 2.1 ?
        if (!is_file(joinpath($_fromdir, 'version.php')) || !is_dir(joinpath($_fromdir, 'lib', 'adodb_lite'))) {
            fatal('The files retrieved from ' .$uri_from. ' do not appear to be for a CMSMS installation');
        }
    } else {
        if (!is_file(joinpath($_fromdir, 'lib', 'version.php')) || !is_dir(joinpath($_fromdir, 'lib', 'classes', 'Database'))) {
            fatal('The files retrieved from ' .$uri_from. ' do not appear to be for a CMSMS installation');
        }
    }
}

try {
    $res = get_sources($uri_to, $_todir);
} catch (Throwable $t) {
    info($t->GetMessage());
    $res = false;
}
if (!$res) {
    fatal('Retrieving files from ' .$uri_to. ' failed');
}
if (0) { //TODO to-version < 2.1 ?
    if (!is_file(joinpath($_todir, 'version.php')) || !is_dir(joinpath($_todir, 'lib', 'adodb_lite'))) {
        fatal('The files retrieved from ' .$uri_to. ' do not appear to be for a CMSMS installation');
    }
} else {
    if (!is_file(joinpath($_todir, 'lib', 'version.php')) || !is_dir(joinpath($_todir, 'lib', 'classes', 'Database'))) {
        fatal('The files retrieved from ' .$uri_to. ' do not appear to be for a CMSMS installation');
    }
}

// get version data
list($_from_ver, $_from_name) = get_version($_fromdir);
list($_to_ver, $_to_name) = get_version($_todir);

// begin output
output('LANG DIFF GENERATED: '.time());
if ($uri_from == "TC") {
    output('LANG DIFF FROM TRANSLATION CENTER');
} else {
    output('LANG DIFF FROM VERSION: '.$_from_ver);
    output('LANG DIFF FROM NAME: '.$_from_name); //ditto
}
output('LANG DIFF TO VERSION: '.$_to_ver);
output('LANG DIFF TO NAME: '.$_to_name);

$sep = DIRECTORY_SEPARATOR;
if ($uri_from == "TC") {
    $_outfile = __DIR__."{$sep}langs-svntc-{$_to_ver}.diff";
} else {
    $_outfile = __DIR__."{$sep}langs-{$_from_ver}-{$_to_ver}.diff";
}
output('DIFF RESULTS IN: '.$_outfile);
info('Diff results in: '.$_outfile);

$processed = [];

if (is_file($_outfile)) {
    file_put_contents($_outfile,'');
}
//check all lang-dirs in TO sources
$langdirs = [
 "$_todir{$sep}admin{$sep}lang{$sep}ext",
 "$_todir{$sep}lib{$sep}lang{$sep}ext",
 "$_todir{$sep}phar_installer{$sep}app{$sep}lang{$sep}ext",
] + glob("$_todir{$sep}modules{$sep}*{$sep}lang{$sep}ext", GLOB_NOESCAPE | GLOB_ONLYDIR);

foreach ($langdirs as $tp) {
    //TODO skip $tp's matching any $src_excludes[] esp .svn
    if ($uri_from == "TC") {
        $fp = str_replace($_todir, $_fromdir.DIRECTORY_SEPARATOR.'svntranslations', $tp);
    } else {
        $fp = str_replace($_todir, $_fromdir, $tp);
    }
    $processed[] = $fp;
    $cmd = "diff -rBZU 0 -x=*.htm? $fp $tp 2>&1";
    $res = shell_exec($cmd);
    $res = preg_replace('/^diff.*$/m', '', $res);
    file_put_contents($_outfile, $res, FILE_APPEND);
}

//check for extra lang-dirs in FROM sources
$langdirs = [
 "$_fromdir{$sep}admin{$sep}lang{$sep}ext",
 "$_fromdir{$sep}lib{$sep}lang{$sep}ext",
 "$_fromdir{$sep}phar_installer{$sep}app{$sep}lang{$sep}ext",
] + glob("$_fromdir{$sep}modules{$sep}*{$sep}lang{$sep}ext", GLOB_NOESCAPE | GLOB_ONLYDIR);
$first = true;
foreach ($langdirs as $fp) {
    //TODO skip $fp's matching any $src_excludes[] esp .svn
    if (is_dir($fp) && !in_array($fp, $processed)) {
        if ($first) {
            file_put_contents($_outfile, "\n\n ====== EXTRAS ======\n\n", FILE_APPEND);
            $first = false;
        }
        $tp = str_replace($_fromdir, $_todir, $fp);
        $cmd = "diff -rBZU 0 -x=*.htm? $fp $tp 2>&1";
        $res = shell_exec($cmd);
        $res = preg_replace('/^diff.*$/m', '', $res);
        file_put_contents($_outfile, $res, FILE_APPEND);
    }
}

//cleanup(); DEBUG
info('DONE');
exit(0);

///////////////
// FUNCTIONS //
///////////////

function usage()
{
    global $_scriptname;
    echo <<<'EOT'
This script generates a manifest of differences (additions/changes/deletions) between two sets of CMSMS files, to facilitate cleaning up and verification during a CMSMS upgrade.

EOT;
    $fn = OUTBASE;
    echo <<<EOT
Usage: php $_scriptname [options]
options
  -c|--config <string> = config file name (or just '-' to skip reading a saved config file)
  -d|--debug           = enable debug mode
  -f|--from <string>   = a fileset-source identifier, one of local or file://... or svn://... or git://...
  -h|--help            = display this message then exit
  -i|--ask             = interactive input of some parameters (N/A on Windows)
  -o|--outfile <string> = a non-default manifest file (the default is STDOUT or $fn)
  -r|--root <string>   = a non-default root url for svn-sourced fileset(s)
  -t|--to <string>     = the 'release' fileset-source identifier, same format as for -f option
EOT;
}

function output($str)
{
    global $_tmpfile;
    static $_mode = 'a';
    $fh = fopen($_tmpfile, $_mode);
    $_mode = 'a';
    if (!$fh) {
        fatal('Problem opening file ('.$_tmpfile.') for writing');
    }
    fwrite($fh, "$str\n");
    fclose($fh);
}

function info($str)
{
    if (defined('STDOUT')) {
        fwrite(STDOUT, "INFO: $str\n");
    } else {
        echo("<br>INFO: $str");
    }
}

function verbose($lvl,$msg)
{
    global $verbose;
    if( $verbose >= $lvl ) echo "VERBOSE: ".$msg."\n";
}

function debug($str)
{
    global $_debug;
    if ($_debug) {
        if (defined('STDOUT')) {
            fwrite(STDOUT, "DEBUG: $str\n");
        } else {
            echo("<br>DEBUG: $str");
        }
    }
}

function fatal($str)
{
    if (defined('STDERR')) {
        fwrite(STDERR, "FATAL: $str\n");
    } else {
        echo("<br>FATAL: $str");
    }
    cleanup();
    exit(1);
}

function startswith($haystack, $needle)
{
    return (strncmp($haystack, $needle, strlen($needle)) == 0);
}

function endswith($haystack, $needle)
{
    $o = strlen($needle);
    if ($o > 0 && $o <= strlen($haystack)) {
        return strpos($haystack, $needle, -$o) !== false;
    }
    return false;
}

function joinpath(...$segs)
{
    if (is_array($segs[0])) {
        $segs = $segs[0];
    }
    $path = implode(DIRECTORY_SEPARATOR, $segs);
    return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
}

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                $file = $dir.DIRECTORY_SEPARATOR.$object;
                if (is_dir($file)) {
                    rrmdir($file);
                } else {
                    unlink($file);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

function sighandler($signum)
{
    info('Signal received');
    cleanup();
    exit(1);
}

function cleanup()
{
    global $_tmpdir;
    debug('Clean up');
    rrmdir($_tmpdir);
}

function ask_string($prompt, $dflt = null, $allow_empty = false)
{
    while (1) {
        if ($dflt) {
            $prompt = $prompt." [default=$dflt]: ";
        }
        if (!endswith($prompt, ': ') || !endswith($prompt, ' ')) {
            $prompt .= ': ';
        }
        $tmp = trim(readline('INPUT: '.$prompt));
        if ($tmp) {
            return $tmp;
        }

        if ($allow_empty) {
            return '';
        }
        if ($dflt) {
            return $dflt;
        }
        info('ERROR: Invalid input. Please try again');
    }
}

function ask_options($prompt, array $options, $dflt)
{
    while (1) {
        if ($dflt) {
            $prompt = $prompt." [default=$dflt] :";
        }
        if (!endswith($prompt, ': ') || !endswith($prompt, ' ')) {
            $prompt .= ': ';
        }
        $tmp = trim(readline('INPUT: '.$prompt));

        if (!$tmp) {
            $tmp = $dflt;
        }
        if (in_array($tmp, $options)) {
            return $tmp;
        }
        info('ERROR: Invalid input. Please enter one of the valid options');
    }
}

function get_config_file()
{
    global $_configname;
    // detect user's home directory
    $home = getenv('HOME');
    if ($home) {
        $home = realpath($home);
    }
    if (is_dir($home)) {
        $file = $home.DIRECTORY_SEPARATOR.$_configname;
        if (is_readable($file)) {
            return $file;
        }
    }
    $file = __DIR__.DIRECTORY_SEPARATOR.$_configname;
    if (is_readable($file)) {
        return $file;
    }
    return '';
}

function rcopy($srcdir, $tmpdir)
{
    global $src_excludes;

    info("Copy source files from $srcdir to $tmpdir");
    //NOTE KEY_AS_FILENAME flag does not work as such - always get path here
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcdir,
            FilesystemIterator::KEY_AS_FILENAME |
            FilesystemIterator::CURRENT_AS_PATHNAME |
            FilesystemIterator::UNIX_PATHS |
            FilesystemIterator::FOLLOW_SYMLINKS
        ),
        RecursiveIteratorIterator::SELF_FIRST);

    $len = strlen($srcdir.DIRECTORY_SEPARATOR);
    $matches = null;

    foreach ($iter as $fn => $fp) {
        foreach ($src_excludes as $excl) {
            if (preg_match($excl, $fp, $matches, 0, $len)) {
//              $relpath = substr($fp, $len);
//              info("$relpath (matched pattern $excl)");
                continue 2;
            }
        }

        $relpath = substr($fp, $len);
        if ($fn == '.') {
            $tp = joinpath($tmpdir, $relpath);
            @mkdir(dirname($tp), 0777, true); // generic perms
        } elseif ($fn !== '..') {
            $tp = joinpath($tmpdir, $relpath);
            @mkdir(dirname($tp), 0777, true);
            @copy($fp, $tp);
            @chmod($tp, 0666);
        }
    }
}

function get_version($basedir)
{
    global  $CMS_VERSION, $CMS_VERSION_NAME, $CMS_SCHEMA_VERSION;

    $file = joinpath($basedir, 'lib', 'version.php');
    if (is_file($file)) {
        $A = (isset($CMS_VERSION)) ? $CMS_VERSION : '';
        $B = (isset($CMS_VERSION_NAME)) ? $CMS_VERSION_NAME : '';
        $C = (isset($CMS_SCHEMA_VERSION)) ? $CMS_SCHEMA_VERSION : '';
        if ($A) {
            //prevent warning from re-definition of 3 consts in included 'to' version-file
            $lvl = error_reporting();
            error_reporting(0);
        }
        include $file;
        $ret = [$CMS_VERSION, $CMS_VERSION_NAME];
        if ($A) {
            error_reporting($lvl);
            // reinstate the 'from' release values
            $CMS_VERSION = $A;
            $CMS_VERSION_NAME = $B;
            $CMS_SCHEMA_VERSION = $C;
        }
        return $ret;
    }
    return ['', ''];
}

function get_sources($sourceuri, $tmpdir)
{
    if (strncmp($sourceuri, 'file://', 7) == 0) {
        $dir = substr($sourceuri, 7);
        if ($dir == 'local' || $dir === '') {
            //get local root
            $dir = __DIR__;
            while ($dir !== '.' && !is_dir(joinpath($dir, 'admin')) && !is_dir(joinpath($dir, 'phar_installer'))) {
                $dir = dirname($dir);
            }
            if ($dir !== '.') {
                rcopy($dir, $tmpdir);
                return true;
            }
        } elseif (is_dir($dir)) {
            rcopy($dir, $tmpdir);
            return true;
        }
    } elseif (strncmp($sourceuri, 'svn://', 6) == 0) {
        $remnant = substr($sourceuri, 6);
        $url = SVNROOT;
        switch (strtolower(substr($remnant, 0, 4))) {
            case '':
            case 'trun':
                $url .= '/trunk';
                break;
            case 'tags':
            case 'bran':
                $url .= '/'. strtolower($remnant);
                break;
            case 'http':
                $url = $remnant;
                break;
            case 'svn.':
                $url = 'http://'.$remnant;
                break;
            default:
                return false;
        }

        $cmd = escapeshellcmd("svn export -q --force $url $tmpdir");

        info("Retrieve files from SVN ($url)");
        system($cmd, $retval);
        return ($retval == 0);
    } elseif (strncmp($sourceuri, 'git://', 6) == 0) {
        $url = 'https://'.substr($sourceuri, 6);
        $cmd = escapeshellcmd("git clone -q --bare $url $tmpdir");

        info("Retrieve files from GIT ($url)");
        system($cmd, $retval);
        return ($retval == 0);
    }
    return false;
}
