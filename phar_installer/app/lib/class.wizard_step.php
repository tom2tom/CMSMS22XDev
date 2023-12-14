<?php

namespace cms_autoinstaller;

use __appbase\app;
use __appbase\wizard;
use __appbase\wizard_step as parent_step;
use Exception;
use function __appbase\get_app;
use function __appbase\lang;
use function __appbase\smarty;

abstract class wizard_step extends parent_step
{
  static $_registered;

  public function __construct()
  {
    $app = get_app();
    $dd = $app->get_destdir();
    if( !$dd ) throw new Exception('Session Failure');

    $smarty = smarty();
    if( !self::$_registered ) {
      $smarty->registerPlugin('function','wizard_form_start', array($this,'fn_wizard_form_start'))
       ->registerPlugin('function','wizard_form_end', array($this,'fn_wizard_form_end'))
       ->addPluginsDir(app::get_rootdir().'/lib/plugins');
      self::$_registered = 1;
    }

    $smarty->assign('version',$app->get_dest_version())
     ->assign('version_name',$app->get_dest_name())
     ->assign('dir',$dd)
     ->assign('in_phar',$app->in_phar())
     ->assign('cur_step',$this->cur_step());
  }

  public function fn_wizard_form_start($params, $smarty)
  {
      echo '<form method="POST" action="'.$_SERVER['REQUEST_URI'].'">';
  }

  public function fn_wizard_form_end($params, $smarty)
  {
      echo '</form>';
  }

  protected function get_primary_title()
  {
      $app = get_app();
      $action = $this->get_wizard()->get_data('action');
      switch( $action ) {
      case 'upgrade':
          $str = lang('action_upgrade',$app->get_dest_version());
          break;
      case 'freshen':
          $str = lang('action_freshen',$app->get_dest_version());
          break;
      case 'install':
      default:
          $str = lang('action_install',$app->get_dest_version());
      }
      return $str;
  }

  protected function display()
  {
      header('Content-Type:text/html; charset=UTF-8');
      // block page cacheing
      header('Expires: Thu, 1 Jan 1970 00:00:00 GMT');
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Cache-Control: post-check=0, pre-check=0',false);
      header('Pragma: no-cache');

      smarty()->assign('wizard_steps',$this->get_wizard()->get_nav())
        ->assign('title',$this->get_primary_title());
  }

  public function error($msg)
  {
      $msg = str_replace(['\\\\r\\\\n','\\\\n','\\\\r',"\n"],['\n','\n','\n','\n'],addslashes($msg));
      echo "<script>add_error('$msg');</script>\n";
      flush();
  }

  public static function verbose($msg)
  {
      $verbose = wizard::get_instance()->get_data('verbose');
      if( $verbose ) {
          $msg = str_replace(['\\\\r\\\\n','\\\\n','\\\\r',"\n"],['\n','\n','\n','\n'],addslashes($msg));
          echo "<script>add_verbose('$msg');</script>\n";
          flush();
      }
  }

  public function message($msg)
  {
      $msg = str_replace(['\\\\r\\\\n','\\\\n','\\\\r',"\n"],['\n','\n','\n','\n'],addslashes($msg));
      echo "<script>add_message('$msg');</script>\n";
      flush();
  }

  public function set_block_html($id,$html)
  {
      //$id is element id attribute, nothing fancy in there
      $html = str_replace(['\\\\r\\\\n','\\\\n','\\\\r'],["\n","\n","\n"],addslashes($html));
      echo "<script>set_block_html('$id','$html');</script>\n";
      flush();
  }

  protected function finish()
  {
      echo "<script>finish();</script>\n";
      flush();
  }

}

?>
