<?php
#Module MicroTiny class microtiny_utils
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

class microtiny_utils
{

  /**
   * Constructor
   *
   * @since 1.0
   */
  private function __construct() {}

  /**
   * Module API wrapper function
   *
   * @internal
   */
  public static function WYSIWYGGenerateHeader($selector='', $css_name='')
  {
      static $first_time = true;

      // Check if we are in object instance
      $config = cms_utils::get_config();
      $mod = cms_utils::get_module('MicroTiny');
      if(!is_object($mod)) throw new CmsLogicException('Could not find the microtiny module...');

      $frontend = CmsApp::get_instance()->is_frontend_request();
      list($languageid,$ltr) = self::GetLanguageId();
      $mtime = time() - 300; // default cache for 5 minutes ?

      // get the cssname that we're going to use (either passed in, or from profile)
      try {
          $profile = [];
          if( $frontend ) {
              $profile = microtiny_profile::load(MicroTiny::PROFILE_FRONTEND);
          }
          else {
              $profile = microtiny_profile::load(MicroTiny::PROFILE_ADMIN);
          }

          if( empty($profile['allowcssoverride']) ) {
              // not allowing override
              $css_name = '';
              $css_id = (int) $profile['dfltstylesheet'];
              if( $css_id > 0 ) $css_name = $css_id;
          }
      }
      catch( \Exception $e ) {
          // do nothing.
      }

      // if we have a stylesheet name, use its modification time as our mtime
      if( $css_name ) {
          try {
              $css = CmsLayoutStylesheet::load($css_name);
              $css_name = $css->get_name();
              $mtime = $css->get_modified();
          }
          catch( Exception $e ) {
              // couldn't load the stylesheet for some reason.
              $css_name = '';
          }
      }

      // if this is an action for MicroTiny disable caching
      $smarty = CmsApp::get_instance()->GetSmarty();
      $module = $smarty->get_template_vars('actionmodule');
      if( $module == $mod->GetName() ) $mtime = time() + 10; // do not cache when we're using this from within the MT module.

      // also disable caching if told to by config.php
      if( isset($config['mt_disable_cache']) && cms_to_bool($config['mt_disable_cache']) ) $mtime = time() + 10;

      $output = '';
      if( $first_time ) {
          // only once per request.
          $first_time = false;
          $output .= '<script src="'.$config->smart_root_url().'/modules/MicroTiny/lib/js/tinymce/tinymce.min.js" defer></script>'; //TODO this form of root deprecated since 2.2
      }

      if( $frontend ) {
          $ip = cms_utils::get_real_ip();
          $hash_salt = $ip.$selector.$css_name.$languageid.session_id().__DIR__;
      }
      else {
          $userid = get_userid(false);
          $hash_salt = __DIR__.session_id().$selector.$css_name.$userid.$languageid.$_SESSION[CMS_USER_KEY];
      }
      $fn = cms_join_path(PUBLIC_CACHE_LOCATION,'mt_'.md5($hash_salt).'.js');
      if( !file_exists($fn) || filemtime($fn) < $mtime ) {
          // we have to generate a config file.
          $langdir = ($ltr) ? 'ltr':'rtl';
          self::_save_static_config($fn,$frontend,$selector,$css_name,$languageid,$langdir);
      }

      $configurl = $config['public_cache_url'].'/'.basename($fn);
      $output .= '<script src="'.$configurl.'" defer></script>';

      return $output;
  }

  private static function _save_static_config($fn, $frontend=false, $selector='', $css_name='', $languageid='', $langdir='')
  {
      if( !$fn ) return;
      $configcontent = self::_generate_config($frontend, $selector, $css_name, $languageid, $langdir);
      $res = file_put_contents($fn,$configcontent);
      if( !$res ) throw new CmsFileSystemException('Problem writing data to '.$fn);
  }

  /**
   * Generate a tinymce initialization file.
   *
   * @since 1.0
   * @param bool $frontend Default false
   * @param string $selector Default ''
   * @param string $css_name Default ''
   * @see also https://www.tiny.cloud/docs/tinymce/6/add-css-options/#content_css
   *  Valid values are:
   *  1. name of a stylesheet recorded for this site, and containing
   *    style-classes recognised by TinyMCE
   *  2. 'default','dark','document','writer' or some other custom-styles
   *    folder name, located in the .../skins/content folder in the
   *    TMCE sources tree, and in which is a styles-file 'content.min.css'
   *  3. absolute url(s) or relative url(s) of relevant css file(s),
   *    comma-separated if > 1
   *  url(s) in init property-values can be:
   *    absolute e.g. https://www.example.com/plugin.min.js.
   *    relative to the root directory of the web-server (include a leading "/") e.g. /plugin.min.js.
   *    relative to the TMCE base_url (no leading "/") e.g. ../../myplugins/plugin.min.js.
   *     The default base_url is the directory containing the main TMCE js file.
   *
   * NOTE the TinyMCE 'skin_url' setting also affects TMCE styling
   * @param string $languageid Default 'en'
   * @param string $langdir Default 'ltr' Since 2.2.6
   * @return string
   */
  private static function _generate_config($frontend=false, $selector='', $css_name='', $languageid='en', $langdir='ltr')
  {
      try {
          //TODO are non-default profiles ever relevant?
          if( $frontend ) {
              $profile = microtiny_profile::load(MicroTiny::PROFILE_FRONTEND);
          }
          else {
              $profile = microtiny_profile::load(MicroTiny::PROFILE_ADMIN);
          }
      }
      catch( Exception $e ) {
          exit($e->Getmessage());
      }

      $ajax_url = function($url) {
          return str_replace('&amp;','&',$url).'&showtemplate=false';
      };

      $mod = cms_utils::get_module('MicroTiny');
      $custombase = $mod->GetModuleURLPath().'/lib/js';
      $_gCms = CmsApp::get_instance();
      $config = $_gCms->GetConfig();
      $smarty = $_gCms->GetSmarty();
      $page_id = ($_gCms->is_frontend_request()) ? $smarty->getTemplateVars('content_id') : '';
      $tpl_ob = $smarty->CreateTemplate('module_file_tpl:MicroTiny;tinymce_config.js',null,null,$smarty); // child of the global smarty
      $tpl_ob->assign('MT',$mod);
      $tpl_ob->assign('MicroTiny',$mod);
//    $tpl_ob->clear_assign('mt_profile'); // ?
      $tpl_ob->assign('mt_profile',$profile);
      $tpl_ob->assign('mt_actionid','m1_');
      $tpl_ob->assign('isfrontend',$frontend);
      $tpl_ob->assign('languageid',$languageid);
      $tpl_ob->assign('langdir',$langdir);
      $tpl_ob->assign('rooturl',CMS_ROOT_URL);
      $tpl_ob->assign('custombase',$custombase);
      $tpl_ob->assign('uploadsurl',$config['uploads_url']);
      $fp = cms_utils::get_filepicker_module();
      if( $fp ) {
          $url = $fp->get_browser_url();
          $url = $ajax_url($url);
          $st1 = $fp->Lang('select_a_file');
          $st2 = $fp->Lang('select_an_image');
          $st3 = $fp->Lang('select_a_media_file');
      }
      else {
          $url = ''; //TODO abort any downstream picker setup
          $st1 = '';
          $st2 = '';
          $st3 = '';
      }
      $tpl_ob->assign('filepicker_url',$url);
      $tpl_ob->assign('filebrowse_title', $st1);
      $tpl_ob->assign('imagebrowse_title', $st2);
      $tpl_ob->assign('mediabrowse_title', $st3);

      $url = $mod->create_url('m1_','linker',$page_id);
      $tpl_ob->assign('linker_url',$ajax_url($url));
      $url = $mod->create_url('m1_','ajax_getpages',$page_id);
      $tpl_ob->assign('getpages_url',$ajax_url($url));
      if( $selector ) $tpl_ob->assign('mt_selector',$selector);
      else $tpl_ob->clear_assign('mt_selector'); // ?

      // styling
      $done = false;
      $val = $profile['styler'];
      if( $val == 'sheet' ) {
          $num = $profile['dfltstylesheet'];
          if ( $num && $num != -1 ) {
              $num = (int)$num;
              $query = new CmsLayoutStylesheetQuery(['id'=>$num]);
              if( $query && !$query->EOF ) {
                  $val = $smarty->fetch("string:{cms_stylesheet id=$num nolinks=1}"); //requires updated cms_stylesheet tag
                  if( $val ) {
                      $tpl_ob->assign('mt_contentcss',$val);
                  }
/* for original tag
                  $obj = $query->GetObject();
                  if( $obj ) {
                      $name = $obj->get_name();
                      $val = $smarty->fetch("string:{cms_stylesheet name='$name' nolinks=1}");
                      if( $val ) {
                          $tpl_ob->assign('mt_contentcss',$val);
                      }
                  }
*/
              }
          }
          $done = true; //even if failed, no more tests
      }
      if( !$done ) {
          $bp = $mod->GetModulePath().DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'js';
          $places = glob(cms_join_path($bp,'CMSMSstyles','content','*'),GLOB_NOESCAPE|GLOB_ONLYDIR);
          foreach( $places as $fp ) {
              if( ($fn = basename($fp)) == $val ) {
                 $tpl_ob->assign('mt_contentcss',$custombase.'/CMSMSstyles/content/'.$val);
                 $done = true;
                 break;
              }
          }
      }
      if( !$done ) {
          $places = glob(cms_join_path($bp,'tinymce','skins','content','*'),GLOB_NOESCAPE|GLOB_ONLYDIR);
          foreach( $places as $fp ) {
              if( ($fn = basename($fp)) == $val ) {
                 $tpl_ob->assign('mt_contentcss',$val);
                 $done = true;
                 break;
              }
          }
      }

      if( $css_name && $profile['allowcssoverride'] ) {
          // check if it's a recorded-stylesheet name
          $query = new CmsLayoutStylesheetQuery(['fullname'=>$css_name]);
          if( $query && !$query->EOF ) {
              $val = $smarty->fetch("string:{cms_stylesheet name='$css_name' nolinks=1}");
              if( $val ) {
                 $tpl_ob->assign('mt_contentcss',$val);
              }
          }
      }

      $done = false;
      $val = $profile['theme'];
      $bp = $mod->GetModulePath().DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'js';
      $places = glob(cms_join_path($bp,'CMSMSstyles','ui','*'),GLOB_NOESCAPE|GLOB_ONLYDIR);
      foreach( $places as $fp ) {
          if( ($fn = basename($fp)) == $val ) {
             $tpl_ob->assign('mt_skinurl',$custombase.'/CMSMSstyles/ui/'.$val);
             $done = true;
             break;
          }
      }
      if( !$done ) {
          $places = glob(cms_join_path($bp,'tinymce','skins','ui','*'),GLOB_NOESCAPE|GLOB_ONLYDIR);
          foreach( $places as $fp ) {
              if( ($fn = basename($fp)) == $val ) {
                 $tpl_ob->assign('mt_skin',$val);
                 break;
              }
          }
      }
      return $tpl_ob->fetch();
  }

  /**
   * Convert user's current language to something tinymce can prolly understand
   *
   * @since 1.0
   *
   * @return array 2 members: [0]language identifier(string) [1]ltr direction(bool)
   */
  private static function GetLanguageId()
  {
    $mylang = CmsNlsOperations::get_current_language();
    if( $mylang == '' ) return ['en',true]; //Lang setting "No default selected"

    $isocode = substr($mylang,0,2);
    $info = CmsNlsOperations::get_language_info($mylang);
    $langltr = $info->direction() != 'rtl';

    $langs = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'langs.manifest');
    if( $langs ) {
        if( strpos($langs, $mylang) !== false ) {
            return [$mylang,$langltr];
        }
        if( strpos($langs, $isocode) !== false ) {
            return [$isocode,$langltr];
        }
        return ['en',true]; // default
    }
    // we have to poll the files
    $patn = cms_join_path(__DIR__,'js','tinymce','langs',$isocode.'*.js');
    $files = glob($patn);
    if( $files ) {
        $langs = [];
        foreach( $files as $one ) {
            $langs[] = basename($one,'.js');
        }
        if( in_array($mylang,$langs) ) {
            return [$mylang,$langltr];
        }
        if( in_array($isocode,$langs) ) {
            return [$isocode,$langltr];
        }
    }
    return ['en',true];
  }

  /**
   * Get an img tag for a thumbnail file if one exists.
   *
   * @since 1.0
   * @param string $file
   * @param string $path
   * @param string $url
   * @return string
   */
  public static function GetThumbnailFile($file,$path,$url)
  {
      $imagepath = $path.DIRECTORY_SEPARATOR.'thumb_'.$file;
      if( !file_exists($imagepath) ) {
          $image = '';
      }
      else {
          $imageurl = self::Slashes($url.'/thumb_'.$file);
          $image = "<img src='".$imageurl."' alt='".$file."' title='".$file."'>";
      }
      return $image;
  }

  /**
   * Fix Slashes
   *
   * @since 1.0
   * @return string
   */
  private static function Slashes($url)
  {
      return str_replace("\\","/",$url);
  }

} // end of class

#
# EOF
#
?>
