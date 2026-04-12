<?php
# Module CMSContentManager tab populator
# (c) 2025 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online, at
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

$this->SetCurrentTab('global');

// some settings are editable only if pretty-urls are enabled
foreach( [
    'content_autocreate_flaturls',
    'content_autocreate_urls',
    'content_mandatory_urls'] as $prefname ) {
    if( isset($params[$prefname]) ) {
        cms_siteprefs::set($prefname, ($params[$prefname]) ? 1 : 0);
    }
}

if( !empty($params['basic_attributes']) ) {
    cms_siteprefs::set('basic_attributes', implode(',', $params['basic_attributes']));
}
else { // nothing selected
    cms_siteprefs::set('basic_attributes', '');
}
cms_siteprefs::set('content_cssnameisblockname', (!empty($params['content_cssnameisblockname'])) ? 1 : 0);
if( !empty($params['disallowed_contenttypes']) ) {
    cms_siteprefs::set('disallowed_contenttypes', implode(',', $params['disallowed_contenttypes']));
}
else { // nothing selected
    cms_siteprefs::set('disallowed_contenttypes', '');
}

$error = false;
foreach( [
    'content_imagefield_path',
    'content_thumbnailfield_path',
    'contentimage_path'] as $prefname ) {
    $val = trim($params[$prefname], ' \\/');
    if( $val ) {
        $newdir = false;
        $oldval = cms_siteprefs::get($prefname);
        $val = strtr($val, '\\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
        $dirpath = cms_join_path($config['image_uploads_path'], $val);
        if( !is_dir($dirpath) ) {
            if( $oldval && $oldval != $val ) {
                $frompath = cms_join_path($config['image_uploads_path'], $oldval);
                if( is_dir($frompath) ) {
                    rename($frompath, $dirpath);
                    touch($dirpath.DIRECTORY_SEPARATOR.'index.html');
                }
                else {
                    $newdir = true; //slide into mkdir etc below
                }
            }
            if ( $newdir ) {
                if( @mkdir($dirpath, 0777, true) ) {
                    touch($dirpath.DIRECTORY_SEPARATOR.'index.html');
                    if( strpos($val, DIRECTORY_SEPARATOR) !== false ) {
                        // recurse all created subdirs
                        $p = $dirpath;
                        while( ($p = dirname($p)) != $config['image_uploads_path'] ) {
                            touch($p.DIRECTORY_SEPARATOR.'index.html');
                        }
                    }
                }
                else {
                    $error = true;
                    $this->SetError('Failed to create subdirectory ...'.$val); //TODO langify
                    continue;
                }
            }
        }
        elseif( $oldval && $oldval != $val ) {
            $frompath = cms_join_path($config['image_uploads_path'], $oldval);
            if( is_dir($frompath) ) {
                //migrate old folder contents
                $fl = strlen($frompath);
                $rdi = new RecursiveDirectoryIterator($frompath,
                    FilesystemIterator::KEY_AS_FILENAME | //unused, but shorter
                    FilesystemIterator::CURRENT_AS_PATHNAME |
                    FilesystemIterator::FOLLOW_SYMLINKS |
                    FilesystemIterator::SKIP_DOTS);
                $rii = new RecursiveIteratorIterator($rdi);
                foreach( $rii as $fp ) {
                    $tp = $dirpath.substr($fp, $fl);
                    $tdir = dirname($tp);
                    if( !is_dir($tdir) ) {
                        @mkdir($tdir, 0777, true);
                    }
                    @rename($fp, $tp); // TODO handle error
                }
                if( !recursive_delete($frompath)) {
                    $error = true;
                    $this->SetError('Failed to remove subdirectory ...'.$frompath); //TODO langify
                }
            }
        }
    }
    cms_siteprefs::set($prefname, $val);
}

// put mention into the admin log
audit('', 'Global content settings', 'Edited');

if( !$error ) {
    $this->SetMessage($this->Lang('msg_prefs_saved'));
}
$this->RedirectToAdminTab('','','admin_settings');
