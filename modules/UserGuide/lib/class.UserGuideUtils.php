<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/
namespace UserGuide;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function endswith;
use function startswith;

class UserGuideUtils
{
    /**
     * Munge risky content of the supplied content string, and otherwise cleanup.
     * Handles php-start tags, script tags, js executables, '`' chars which would
     * be a problem in pages, templates, but TODO some might be ok in UDT content
     * in a textarea element?
     * Entitized content is interpreted, but not (url-, rawurl-, base64-) encoded content.
     * Does not deal with image-file content. Inline <svg/> will be handled anyway.
     * @internal
     * @see https://portswigger.net/web-security/cross-site-scripting/cheat-sheet
     * @see https://owasp.org/www-community/xss-filter-evasion-cheatsheet
     * @see http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php
     *
     * @param mixed $val input value, string (maybe empty) or null
     * @return string
     */
    public static function cleanContent($val)
    {
        $val = trim((string)$val);
        if (!$val) { return $val; }
        //WYSIWYG editor might have arbitrarily added para tags
        // to surround otherwise plaintext content
        if (startswith($val, '<p>') && endswith($val, '</p>')) {
            $l = strlen($val);
            $val[$l-1] = '_'; //do not match the trailing </p>
            // we don't need/use $matches, but PHP barfs if not used
            if (preg_match('/<\s?\/?\s?[^ >]+\s?\/?\s?>/', $val, $matches, 0, 3) === 1) {
                $val[$l-1] = '>';
            } else {
                $val = substr($val, 3, -4);
                $val = trim($val);
            }
        }

        $flags = ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_XHTML; // OR ENT_HTML5 ?
        $tmp = html_entity_decode($val, $flags, 'UTF-8');
        if ($tmp === $val) {
            $revert = false;
        } else {
            $revert = true;
            $val = $tmp;
        }
        // munge start-PHP tags
        $val = preg_replace(['/<\s*\?\s*php/i', '/<\s*\?\s*=/', '/<\s*\?(\s|\n)/'], ['&#60;&#63;php', '&#60;&#63;=', '&#60;&#63; '], $val);
        //TODO maybe disable SmartyBC-supported {php}{/php}
        //$val = preg_replace('~\{/?php\}~i', '', $val); but with current smarty delim's
        $val = str_replace('`', '&#96;', $val);
        foreach ([
             // script tags like <script or <script> or <script X> X = e.g. 'defer'
            '/<\s*(scrip)t([^>]*)(>?)/i' => function($matches) {
                return '&#60;'.$matches[1].'&#116;'.($matches[2] ? ' '.trim($matches[2]) : '').($matches[3] ? '&#62;' : '');
            },
            // explicit script
            '/jav(.+?)(scrip)t\s*:\s*(.+)?/i' => function($matches) {
                if ($matches[3]) {
                    return 'ja&#118;'.trim($matches[1]).$matches[2].'&#116;&#58;'.strtr($matches[3], ['(' => '&#40;', ')' => '&#41;']);
                }
                return $matches[0];
            },
            // inline scripts like on*="dostuff" or on*=dostuff (TODO others e.g. FSCommand(), seekSegmentTime() @ http://help.dottoro.com)
            // TODO invalidly processes non-event-related patterns like ontopofold='smoky'
            '/\b(on[\w.:\-]{4,})\s*=\s*(["\']?.+?["\']?)/i' => function($matches) {
                return $matches[1].'&#61;'.strtr($matches[2], ['"' => '&#34;', "'" => '&#39;', '(' => '&#40;', ')' => '&#41;']);
            },
            //callables like class::func
            '/([a-zA-Z0-9_\x80-\xff]+?)\s*?::\s*?([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*?)\s*?\(/' => function($matches) {
                return $matches[1] . '&#58;&#58;' . $matches[1] . '&#40;';
            },
            // embeds
            '/(embe)(d)/i' => function($matches) {
                return $matches[1].'&#'.ord($matches[2]).';';
            }
            ] as $regex => $replacer) {
                $val = preg_replace_callback($regex, $replacer, $val);
            }

        if ($revert) {
            // preserve valid content like <p>
            $tmp = strtr($val, '<>', "\2\3"); //TODO any other char(s) need preservation?
            $tmp2 = htmlentities($tmp, $flags, 'UTF-8', false);
            $val = strtr($tmp2, "\2\3", '<>');
        }
        return $val;
    }

    /**
     * Get a reasonably well minimised version of the supplied css
     * content
     *
     * @param string $css
     * @param bool $single whether to escape embedded single quotes.
     *  Default true. Otherwise, escape embedded double quotes.
     * @return string
     */
    public static function minCSS($css, $singl = true)
    {
        if ($css) {
            $from = [
            '~[\r\n]~',
            '~/\*.*?\*/~',
            '/: /',
            '/; /',
            '/ {\s*/',
            "/[^\\\\]'/"
            ];
            $to = ['','',':',';','{',"\\'"];
            if (!$singl) {
                $from[5] = '/[^\\]"/';
                $to[5] = '\\"';
            }
            return preg_replace($from, $to, $css);
        }
        return (string)$css;
    }

    /**
     * Recursively copy all items in and below directory $from to $to
     *
     * @param string $from source filepath
     * @param string $to destination filepath, which need not already exist
     * @param array $excludes Optional array of regex patterns of items to
     *  be excluded from the transfer
     * @param bool $replace Optional flag whether to overwrite any
     *  existing file in below $to. Default false.
     * @return array of error message(s) or maybe empty
     */
    public static function recursiveCopy($from, $to, $excludes = [], $replace = false)
    {
        if (!is_dir($to)) {
            if (!mkdir($to, 0775, true)) {
                return ['Failed to create directory '.$to];
            }
            $replace = false; // no replacements in new place
        }

        $errors = [];
        $l = strlen($from);
        $rdi = new RecursiveDirectoryIterator($from,
            FilesystemIterator::KEY_AS_FILENAME |
            FilesystemIterator::CURRENT_AS_PATHNAME |
            FilesystemIterator::FOLLOW_SYMLINKS |
            FilesystemIterator::UNIX_PATHS |
            FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);
        foreach ($rii as $name => $fp) {
            foreach ($excludes as $patn) {
                if (preg_match($patn, $fp)) {
                    continue 2;
                }
            }
            $tp = $to.substr($fp, $l);
            $dir = dirname($tp);
            if (!file_exists($dir)) {
                if (!@mkdir($dir, 0775, true) && !is_dir($dir)) { //TODO relevant permissions
                    $msg = 'Failed to create directory '.$dir.', file(s) which should be in there are missing';
                    if (!in_array($msg, $errors)) {
                        $errors[] = $msg;
                    }
                    continue;
                }
            }
            if (is_dir($fp)) {
                if (!(is_dir($tp) || @mkdir($tp, 0775, true))) { //TODO relevant permissions
                    $msg = 'Failed to create directory '.$tp.', file(s) which should be in there are missing';
                    if (!in_array($msg, $errors)) {
                        $errors[] = $msg;
                    }
                }
            } elseif (is_file($tp)) {
                if ($replace) {
                    @unlink($tp);
                } else {
                    // use a similar name
                    $bn = basename($tp);
                    $main = substr($bn, 0, strrpos($bn, '.'));
                    $bp = dirname($tp).DIRECTORY_SEPARATOR;
                    $all = glob($bp.$main.'*');
                    $existing = [];
                    foreach ($all as $fp) {
                        $existing[] = basename($fp);
                    }
                    if ($main != $bn) {
                        $ext = '.'.substr($bn, strrpos($bn, '.') + 1);
                    } else {
                        $ext = '';
                    }
                    for ($i = 1; $i < 21; $i++) {
                        $alt = "$main($i){$ext}";
                        if (!in_array($alt, $existing)) {
                            break; // use this one
                        }
                    }
                    if ($i > 20) {
                        $i = mt_rand(50, 99); // default to something very unlikely to be conflicted
                        $alt = "$main($i){$ext}";
                    }
                    $tp = $bp.$alt;
                }
            }
            if (!@copy($fp, $tp)) {
                $errors[] = 'Failed to copy file '.$fp;
            }
        }
        return $errors;
    }

    /**
     * Get a unique replacement for the supplied $candidate if that
     * value is among the values (if any) in $existing.
     * If a change is made, the new value is appended to $existing.
     *
     * @param string $candidate
     * @param mixed $existing reference to string | strings[] | null
     * @return string
     */
    public static function uniquename($candidate, &$existing)
    {
        if ($existing) {
            if (!is_array($existing)) {
                $existing = [$existing];
            }
        } else {
            $existing = [];
        }
        if (!($candidate || is_numeric($candidate))) {
            return '';
        }

        if (!in_array($candidate, $existing)) {
            $existing[] = $candidate;
            return $candidate;
        }
        $i = 65; // ord('A')
        while ($i < 91) { // ord('Z') + 1
            $sf = chr($i);
            $alt = "{$candidate}x{$sf}";
            if (!in_array($alt, $existing)) {
                $existing[] = $alt;
                return $alt;
            }
            $i++;
        }
        //default to 5 or 6 appended random uppercase letters
        //per www.php.net/manual/en/function.base-convert.php#94874
        $n = mt_rand(1048576, 16777216);
        $sf = '';
        for ($i = 1; $i < 10 && $n >= 0; $i++) {
           $k = 26 ** $i;
           $o = $n % $k / 26 ** ($i - 1);
           $sf = chr((int)$o + 65) . $sf;
           $n -= $k;
        }
        $alt = "{$candidate}x{$sf}";
        $existing[] = $alt;
        return $alt;
    }
}
