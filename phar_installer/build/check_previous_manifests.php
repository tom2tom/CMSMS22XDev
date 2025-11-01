#!/usr/bin/env php
<?php

// attempt to read config file
$_configname = 'create_manifest.ini';
$_configfile = get_config_file();
if ($_configfile && $_configfile != '-') {
    if (!is_readable($_configfile)) {
        fatal("No valid config file at: $_configfile");
    }
    $_config = parse_ini_file($_configfile, false, INI_SCANNER_TYPED);
    if ($_config === false) {
        fatal("Problem processing config file: $_configfile");
    }
    info('Read config file from '.$_configfile);
    $uri_from = $_config['uri_from'];
    $uri_to = $_config['uri_to'];
} else {
    fatal('Failed to read config file');
}

$_tmpdir = sys_get_temp_dir().DIRECTORY_SEPARATOR.basename(__FILE__, 'php').getmypid();
//get local copy of prior-release files
$_fromdir = $_tmpdir.DIRECTORY_SEPARATOR.'_from';
if (is_dir($_fromdir)) {
    rrmdir($_fromdir);
}
mkdir($_fromdir, 0777, true);
try {
    $res = get_sources($uri_from, $_fromdir);
} catch (Throwable $t) {
    info($t->GetMessage());
    $res = false;
}
if (!$res) {
    fatal('Retrieving files from ' .$uri_from. ' failed');
}
//get current-release files merely to procure the release number
$_todir = $_tmpdir.DIRECTORY_SEPARATOR.'_to';
if (is_dir($_todir)) {
    rrmdir($_todir);
}
mkdir($_todir, 0777, true);
try {
    $res = get_sources($uri_to, $_todir);
} catch (Throwable $t) {
    info($t->GetMessage());
    $res = false;
}
if (!$res) {
    fatal('Retrieving files from ' .$uri_to. ' failed');
}
list($_to_ver, $_to_name) = get_version($_todir);

// location of files to be processed
$destdir = joinpath(dirname(__DIR__), 'app', 'upgrade');
//$destdir = joinpath($_todir, 'phar_installer', 'app', 'upgrade');
$srcdir = joinpath($_fromdir, 'phar_installer', 'app', 'upgrade');
$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($srcdir,
        FilesystemIterator::KEY_AS_FILENAME |
        FilesystemIterator::CURRENT_AS_PATHNAME |
        FilesystemIterator::SKIP_DOTS |
        FilesystemIterator::UNIX_PATHS
    ),
    RecursiveIteratorIterator::SELF_FIRST);

foreach ($iter as $fn => $fp) {
    if (is_dir($fp)) {
        $ver = $fn; //aka basename($fp);
        if (cms_version_compare($ver, $_to_ver) < 0) {
            foreach (['MANIFEST.DAT.gz', 'upgrade.php'] as $check) {
                $f1 = $fp . DIRECTORY_SEPARATOR . $check;
                $f2 = joinpath($destdir,$ver,$check);
                $if1 = is_file($f1);
                $if2 = is_file($f2);
                if ($if1 && !$if2) {
                    info("Review $check for $ver upgrade");
                } elseif (!$if1 && $if2) {
                    info("Review $check for $ver upgrade");
                } elseif ($if1 && $if2) {
                    if (filesize($f1) != filesize($f2)) {
                        info("Review $check for $ver upgrade");
                    } else {
                        $c1 = file_get_contents($f1);
                        $c2 = file_get_contents($f2);
                        if ($c1 != $c2) {
                            info("Review $check for $ver upgrade");
                        }
                    }
                }
            }
        }
    }
}

info('DONE');
exit(0);

///////////////
// FUNCTIONS //
///////////////

function info($str)
{
    if (defined('STDOUT')) {
        fwrite(STDOUT, "INFO: $str\n");
    } else {
        echo("<br>INFO: $str");
    }
}

function fatal($str)
{
    if (defined('STDERR')) {
        fwrite(STDERR, "FATAL: $str\n");
    } else {
        echo("<br>FATAL: $str");
    }
//  cleanup();
    exit(1);
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

function cms_version_compare($v1, $v2)
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
