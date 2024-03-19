<?php

namespace __appbase;

use Exception;
use Smarty;

require_once \dirname(__DIR__).'/Smarty/Smarty.class.php';

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
 * un-registered static-method calls
 * @since 2.2.19#2
 */
class Install_TemplateCaller
{
    private $php_functions; // normally, unset means no use, empty array means no restriction
    private static $static_classes; // ditto

    public function __construct($smarty)
    {
        if ($smarty->security_policy) {
            $this->php_functions = &$smarty->security_policy->php_functions; //TODO check with Smarty5
            self::$static_classes = &$smarty->security_policy->static_classes; //TODO check with Smarty5
        } else {
            // Smarty4 defaults + tr = OK here?
            $this->php_functions = ['count', 'empty', 'in_array', 'is_array', 'isset', 'sizeof', 'time', 'tr'];
            self::$static_classes = [];
        }
    }

    public function __call($name, $args) {
        if (!$this->php_functions || in_array($name, $this->php_functions)) {
            return $name(...$args);
        }
        return "<!-- prohibited function $name called -->";
    }

    public static function __callStatic($name, $args)
    {
        if (self::$static_classes !== null && (!self::$static_classes ||
           ($classname = substr($name, 0, strpos($name, '__')) && in_array($classname, self::$static_classes)))) {
            $num = 1;
            $name = str_replace('__', '::', $name, $num);
            return $name(...$args);
        }
        return "<!-- prohibited static function $name called -->";
    }
}

?>
