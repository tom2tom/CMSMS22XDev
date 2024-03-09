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
    $this->assignGlobal('_call', new Install_TemplateCaller($this)); //for Smarty5, wherein PHP function-calls are not supported
    // in templates use $_call->func(args) instead of just func(args)
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
 * Workaround for Smarty5's disabling of all PHP function calls
 * @since 2.2.19#2
 */
class Install_TemplateCaller
{
    private $php_functions; // normally, unset means no use, empty array means no restriction

    public function __construct($smarty)
    {
        if ($smarty->security_policy) {
            $this->php_functions = &$smarty->security_policy->php_functions; //TODO check with Smarty5
        } else {
            // Smarty4 defaults + tr = OK here?
            $this->php_functions = ['count', 'empty', 'in_array', 'is_array', 'isset', 'sizeof', 'time', 'tr'];
        }
    }

    public function __call($name, $args) {
        if (!$this->php_functions || in_array($name, $this->php_functions)) {
            return $name(...$args);
        }
        return "<!-- prohibited function $name called -->";
    }
}

?>
