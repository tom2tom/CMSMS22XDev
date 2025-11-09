<?php

namespace __appbase;

use Exception;
use Smarty;

require_once \dirname(__DIR__).'/smarty/Smarty.class.php';

class cms_smarty extends Smarty
{
  private static $_instance;

  public function __construct()
  {
    parent::__construct();

    $app = get_app();
    $tmpdir = $app->get_tmpdir().'/m'.md5(__FILE__);
    $appdir = $app->get_appdir();

    $this->setTemplateDir($appdir.'/templates');
    $this->setConfigDir($appdir.'/configs');
    $this->setCompileDir($tmpdir.'/templates_c');
    $this->setCacheDir($tmpdir.'/cache');

    $dirs = array($this->compile_dir,$this->cache_dir);
    for( $i = 0; $i < count($dirs); $i++ ) {
      @mkdir($dirs[$i],0777,TRUE);
      if( !is_dir($dirs[$i]) ) throw new Exception('Required directory '.$dirs[$i].' does not exist');
    }
    // the installer is a closed system, so no need for Smarty's security mechanisms
    $this->registerPlugin('modifier','tr',array($this,'modifier_tr')); //for Smarty5, wherein unregistered methods are not supported
    // $_call->func(args) can be used in templates instead of func(args) for Smarty5
    $this->assignGlobal('_call', new Install_TemplateCaller($this)); //for Smarty 4.5.1+, wherein PHP function-calls are deprecated then (in 5+) blocked
    // _call::class__method(args) can be used in templates instead of unregistered class::method(args) for Smarty 4.5.1+
    $this->registerClass('_call', Install_TemplateCaller::class);
  }

  public static function get_instance()
  {
    if( !self::$_instance ) self::$_instance = new self();
    return self::$_instance;
  }

  public function modifier_tr(...$args)
  {
    return langtools::get_instance()->translate(...$args);
  }
}

/**
 * Workaround for Smarty5's disabling of all PHP function calls and
 * un-registered static-method calls in templates
 * $_call->func(args) can be used instead of an unregistered func(args)
 * _call::class__method(args) can be used instead of an unregistered class::method(args)
 * No name-checking (because no security-policy use in the installer)
 * @since 2.2.19F2
 */
class Install_TemplateCaller
{
  #[\ReturnTypeWillChange]
  public function __call($name,$args)
  {
    return $name(...$args);
  }

  #[\ReturnTypeWillChange]
  public static function __callStatic($name, $args)
  {
    $pos = strpos($name,'__');
    if( $pos !== FALSE ) {
      $classname = substr($name,0,$pos);
      $name = substr_replace($name,'::',$pos,2); // replace 1st occurrence
      return $name(...$args);
    }
    return "<!-- malformed static function $name called -->";
  }
}

?>
