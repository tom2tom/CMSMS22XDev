<?php

namespace cms_autoinstaller;

use __appbase\utils as utils2;
use Exception;
use PharData;
use RecursiveIteratorIterator;
use function __appbase\get_app;
use function __appbase\joinpath;
use function __appbase\lang;
use function __appbase\smarty;
use function file_put_contents;

class wizard_step7 extends wizard_step
{
    protected function process()
    {
        // nothing here
    }

    private function clear_langs($langs)
    {
        if( $langs ) {
            $val = implode(', ',$langs);
            $this->message(lang('remove_langs',$val));
            //these cleanups might have been done during files processing
            $app = get_app();
            $top_dir =  $app->get_destdir();
            $bases = ( count($langs) == 1 ) ? reset($langs) : '{'.implode(',',$langs).'}';
            $flags = ( count($langs) == 1 ) ? GLOB_NOSORT|GLOB_NOESCAPE : GLOB_NOSORT|GLOB_NOESCAPE|GLOB_BRACE;
            $frompaths = array(
                joinpath($top_dir,'admin','lang','ext',$bases.'.php'), // 'admin' must not be configured otherwise
                joinpath($top_dir,'lib','lang','*','ext',$bases.'.php'),
                joinpath($top_dir,'lib','nls',$bases.'.nls.php'),
                joinpath($top_dir,'modules','*','lang','ext',$bases.'.php'),
            );
            foreach( $frompaths as $patn ) {
                $files = glob($patn,$flags);
                if( $files ) {
                    foreach( $files as $fp ) {
                        unlink($fp);
                    }
                }
            }
        }
    }

    private function do_index_html()
    {
        $this->message(lang('install_dummyindexhtml'));
        $app = get_app();
        $top_dir = $app->get_destdir();
        if( !$top_dir ) throw new Exception(lang('error_internal',711));
        $sskips = ['/'=>1,'/admin'=>1]; //in-phar paths always have / separators
        $sep = DIRECTORY_SEPARATOR;
        $baks = ($sep == '\\');
        $archive = $app->get_archive();
        $phardata = new PharData($archive);
        $archive = basename($archive);
        $l = strlen($archive);

        foreach( new RecursiveIteratorIterator($phardata) as $file => $info ) { //$info is PharFileInfo object, unused here
            if( ($p = strpos($file,$archive)) === FALSE ) {
                continue;
            }
            $dfn = dirname(substr($file,$p+$l));
            if( isset($sskips[$dfn]) ) {
                continue;
            }
            if( $baks ) $dfn = strtr($dfn,'/','\\');
            $dn = $top_dir.$dfn; //in-site parent of $file
            if( is_dir($dn) ) {
                touch("$dn{$sep}index.html"); //might be repeated
            }
        }

        foreach( ['tmp','assets'] as $dfn ) { //'assets' must not be configured otherwise
            //TODO recurse all folders
            $all = glob("$top_dir{$sep}$dfn{$sep}*",GLOB_ONLYDIR);
            foreach( $all as $dn ) {
                $fp = "$dn{$sep}index.html";
                if(! is_file($fp) ) {
                    touch($fp);
                }
            }
        }
   }

    private function do_files($langlist = [])
    {
        $languages = ['en_US'];
        $wiz = $this->get_wizard();
        $siteinfo = $wiz->get_data('siteinfo');
        if( is_array($siteinfo) && !empty($siteinfo['extlanguages']) && is_array($siteinfo['extlanguages']) ) {
            $languages = array_merge($languages,$siteinfo['extlanguages']);
        }
        if( $langlist && is_array($langlist) ) {
            $languages = array_merge($languages,$langlist);
        }
        $languages = array_unique($languages);

        $app = get_app();
        $top_dir = $app->get_destdir();
        if( !$top_dir ) throw new Exception(lang('error_internal',720));
        $sep = DIRECTORY_SEPARATOR;
        $baks = ($sep == '\\');

        if( !empty($siteinfo['theme_relpath']) ) { //possibly entered value - check BEORE 'themes_dir'
            $dn = $sep.$siteinfo['theme_relpath'];
        }
        elseif( !empty($siteinfo['themes_dir']) ) { //possible recorded value
            $dn = $sep.$siteinfo['themes_dir'];
        }
        else {
            $dn = '/assets/themes';
        }
        if( $baks ) $dn = strtr($dn,'/','\\');
        $themesdir = $top_dir.$dn;

        $archive = $app->get_archive();
        $this->message(lang('install_extractfiles'));
        $phardata = new PharData($archive);
        $archive = basename($archive);
        $l = strlen($archive);

        $filehandler = new install_filehandler();
        $filehandler->set_languages($languages);
        $filehandler->set_destdir($top_dir);
        $filehandler->set_themesdir($themesdir);
        $filehandler->set_output_fn('\cms_autoinstaller\wizard_step7::verbose');
        foreach( new RecursiveIteratorIterator($phardata) as $file => $fi ) {
            if( ($p = strpos($file,$archive)) === FALSE ) continue;
            $fn = substr($file,$p+$l);
            $filehandler->handle_file($fn,$file,$fi);
        }

        //remove residual demo-theme folders in superseded place(s)
        $choices = $wiz->get_data('config');
        $demos = (!empty($choices['demothemes'])) ? $choices['demothemes'] : [];
        if( $demos ) {
            foreach( ['uploads','assets'.DIRECTORY_SEPARATOR.'themes'] as $sp ) {
                if( ($bp = $top_dir.DIRECTORY_SEPARATOR.$sp) != $themesdir ) {
                    foreach( $demos as $dirn ) {
                        $fp = $bp.DIRECTORY_SEPARATOR.$dirn;
                        utils2::rrmdir($fp);
                    }
                }
            }
        }
    }

    private function do_manifests()
    {
        // get the list of all available versions that this upgrader knows about
        $app = get_app();
        $upgrade_dir = $app->get_appdir().'/upgrade';
        if( !is_dir($upgrade_dir) ) throw new Exception(lang('error_internal',730));
        $top_dir = $app->get_destdir();
        if( !$top_dir ) throw new Exception(lang('error_internal',731));

        $version_info = $this->get_wizard()->get_data('version_info'); // populated only for refreshes & upgrades
        $versions = utils::get_upgrade_versions();
        if( is_array($versions) && count($versions) ) {
            $this->message(lang('cleaning_files'));
            foreach( $versions as $one_version ) {
                if( utils::cms_version_compare($one_version, $version_info['version']) < 1 ) continue;

                // open the manifest
                // check the to version info
                $manifest = new manifest_reader("$upgrade_dir/$one_version");
                if( $one_version != $manifest->to_version() ) {
                    throw new Exception(lang('error_internal',732));
                }

                // delete all 'deleted' files
                // if they are supposed to be in the installation, the copy from the archive
                // will restore them.
                $deleted = $manifest->get_deleted();
                $ndeleted = 0;
                $nfailed = 0;
                $nmissing = 0;
                if( is_array($deleted) && count($deleted) ) {
                    foreach( $deleted as $rec ) {
                        $fn = "{$top_dir}{$rec['filename']}";
                        if( !file_exists($fn) ) {
                            $this->verbose("file $fn does not exist... but we planned to delete it anyway");
                            $nmissing++;
                        }
                        else if( !is_writable($fn) ) {
                            $this->error("file $fn is not writable, could not delete it");
                            $nfailed++;
                        }
                        else {
                            if( is_dir($fn) ) {
                                if( is_file($fn.'/index.html') ) @unlink($fn.'/index.html');
                                $res = @rmdir($fn);
                                if( !$res ) {
                                    $this->error('problem removing directory: '.$fn);
                                    $nfailed++;
                                }
                                else {
                                    $this->verbose('removed directory: '.$fn);
                                    $ndeleted++;
                                }
                            }
                            else {
                                $res = @unlink($fn);
                                if( !$res ) {
                                    $this->error("problem deleting: $fn");
                                    $nfailed++;
                                }
                                else {
                                    $this->verbose('removed file: '.$fn);
                                    $ndeleted++;
                                }
                            }
                        }
                    }
                }

                $this->message($ndeleted.' files/folders deleted for version '.$one_version.": ".$nmissing.' missing, '.$nfailed.' failed');
            }
        }
    }

    protected function display()
    {
        parent::display();
        $wiz = $this->get_wizard();
        $action = $wiz->get_data('action');

        $smarty = smarty();
        $smarty->assign('next_url',$wiz->next_url());
        $smarty->display('wizard_step7.tpl');
        flush();

        try {
            switch( $action ) {
            case 'upgrade':
                $tmp = $wiz->get_data('version_info'); // populated only for refreshes & upgrades
                if( is_array($tmp) && count($tmp) ) {
                    $siteinfo = $wiz->get_data('siteinfo');
                    $this->clear_langs($siteinfo['removelanguages']);
                    $this->do_manifests();
                    $this->do_files($siteinfo['extlanguages']);
                    break;
                }
                else {
                    throw new Exception(lang('error_internal',753));
                }
                // no break here
            case 'freshen':
                $siteinfo = $wiz->get_data('siteinfo');
                $this->clear_langs($siteinfo['removelanguages']);
                $this->do_files($siteinfo['extlanguages']);
                break;
            case 'install':
                $this->do_files();
                break;
            default:
                throw new Exception(lang('error_internal',755));
            }

            // [re-]create index.html files in directories.
            $this->do_index_html();
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }

        $this->finish();
    }

} // end of class
