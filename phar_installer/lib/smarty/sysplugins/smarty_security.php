<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage Security
 * @author     Uwe Tews
 */
/**
 * FIXME: Smarty_Security API
 *      - getter and setter instead of public properties would allow cultivating an internal cache properly
 *      - current implementation of isTrustedResourceDir() assumes that Smarty::$template_dir and Smarty::$config_dir
 *      are immutable the cache is killed every time either of those variables changes. That means that two distinct
 *      Smarty objects with differing
 *        $template_dir or $config_dir should NOT share the same Smarty_Security instance,
 *        as this would lead to (severe) performance penalty! how should this be handled?
 */

/**
 * This class contains the security settings
 */
class Smarty_Security
{

    /**
     * This is the list of template directories that are considered secure.
     * $template_dir is in this list implicitly.
     *
     * @var array
     */
    public $secure_dir = array();

    /**
     * This is an array of directories where trusted php scripts reside.
     * {@link $security} is disabled during their inclusion/execution.
     *
     * @var array
     */
    public $trusted_dir = array();

    /**
     * List of regular expressions (PCRE) that include trusted URIs
     *
     * @var array
     */
    public $trusted_uri = array();

    /**
     * List of trusted constants names
     *
     * @var array
     */
    public $trusted_constants = array();

    /**
     * This is an array of trusted static classes.
     * If empty access to all static classes is allowed.
     * If set to 'none' none is allowed.
     *
     * @var array
     */
    public $static_classes = array();

    /**
     * This is an nested array of trusted classes and static methods.
     * If empty access to all static classes and methods is allowed.
     * Format:
     * array (
     *         'class_1' => array('method_1', 'method_2'), // allowed methods listed
     *         'class_2' => array(),                       // all methods of class allowed
     *       )
     * If set to null none is allowed.
     *
     * @var array
     */
    public $trusted_static_methods = array();

    /**
     * This is an array of trusted classes and static properties.
     * If empty access to all static classes and properties is allowed.
     * Format:
     * array (
     *         'class_1' => array('prop_1', 'prop_2'), // allowed properties listed
     *         'class_2' => array(),                   // all properties of class allowed
     *       )
     * If set to null none is allowed.
     *
     * @var array
     */
    public $trusted_static_properties = array();

    /**
     * This is an array of trusted PHP functions.
     * If empty all functions are allowed.
     * To disable all PHP functions set $php_functions = null.
     *
     * @var array
     */
    public $php_functions = array('isset', 'empty', 'count', 'sizeof', 'in_array', 'is_array', 'time');

    /**
     * This is an array of trusted PHP modifiers etc.
     * If empty all modifiers are allowed.
     * To disable all modifier set $php_modifiers = null.
     *
     * @var array
     */
    public $php_modifiers = array('escape', 'count', 'sizeof', 'nl2br'); //'escape' not PHP callable, of course

    /**
     * This is an array of allowed tags.
     * If empty no restriction by allowed_tags.
     *
     * @var array
     */
    public $allowed_tags = array();

    /**
     * This is an array of disabled tags.
     * If empty no restriction by disabled_tags.
     *
     * @var array
     */
    public $disabled_tags = array();

    /**
     * This is an array of allowed modifier plugins.
     * If empty no restriction by allowed_modifiers.
     *
     * @var array
     */
    public $allowed_modifiers = array();

    /**
     * This is an array of disabled modifier plugins.
     * If empty no restriction by disabled_modifiers.
     *
     * @var array
     */
    public $disabled_modifiers = array();

    /**
     * This is an array of disabled special $smarty variables.
     *
     * @var array
     */
    public $disabled_special_smarty_vars = array();

    /**
     * This is an array of trusted streams.
     * If empty all streams are allowed.
     * To disable all streams set $streams = null.
     *
     * @var array
     */
    public $streams = array('file');

    /**
     * + flag if constants can be accessed from template
     *
     * @var boolean
     */
    public $allow_constants = true;

    /**
     * + flag if super globals can be accessed from template
     *
     * @var boolean
     */
    public $allow_super_globals = true;

    /**
     * max template nesting level
     *
     * @var int
     */
    public $max_template_nesting = 0;

    /**
     * current template nesting level
     *
     * @var int
     */
    private $_current_template_nesting = 0;

    /**
     * Cache for resource-directories
     *
     * @var array
     * each member like dir-path-with-trailing-separator => true
     */
    protected $_resource_dir = array();

    /**
     * Cache for template-directories
     *
     * @var array
     */
    protected $_template_dir = array();

    /**
     * Cache for config-directories
     *
     * @var array
     */
    protected $_config_dir = array();

    /**
     * Cache for secure-directories
     *
     * @var array
     */
    protected $_secure_dir = array();

    /* *
     * Cache for php_resource directories
     * Now-unused relic from Smarty3's isTrustedPHPDir()
     *
     * @var array
     */
//   protected $_php_resource_dir = null;

    /**
     * Cache for trusted-directories
     *
     * @var array
     */
    protected $_trusted_dir = null;

    /**
     * Cache for include path status
     *
     * @var bool
     */
    protected $_include_path_status = false;

    /**
     * Cache for included-directories
     *
     * @var array
     */
    protected $_include_dir = array();

    /**
     * Global Smarty instance
     *
     * @var object
     */
    protected $smarty;

    /**
     * Constructor
     *
     * @param Smarty $smarty
     */
    public function __construct($smarty)
    {
        $this->smarty = $smarty;
    }

    /**
     * Check if PHP function is trusted.
     *
     * @param string $function_name
     * @param object $compiler compiler object
     *
     * @return boolean                 true if function is trusted
     */
    public function isTrustedPhpFunction($function_name, $compiler)
    {
        if (isset($this->php_functions)
            && (empty($this->php_functions) || in_array($function_name, $this->php_functions))
        ) {
            return true;
        }
        $compiler->trigger_template_error("PHP function '{$function_name}' not allowed by security setting");
        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if static class is trusted.
     *
     * @param string $class_name
     * @param object $compiler compiler object
     *
     * @return boolean                 true if class is trusted
     */
    public function isTrustedStaticClass($class_name, $compiler)
    {
        if (isset($this->static_classes)
            && (empty($this->static_classes) || in_array($class_name, $this->static_classes))
        ) {
            return true;
        }
        $compiler->trigger_template_error("access to static class '{$class_name}' not allowed by security setting");
        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if static class method/property is trusted.
     *
     * @param string $class_name
     * @param array $params with members
     *  [0] name of property/method, '$'-prefixed for a property
     *  [1] UNUSED
     *  [2] N/A or 'method' or anything non-falsy to process a property
     * @param object $compiler compiler object
     *
     * @return boolean                 true if class method/property is trusted
     */
    public function isTrustedStaticClassAccess($class_name, $params, $compiler)
    {
        if (empty($params[ 2 ])) {
            // fall back
            return $this->isTrustedStaticClass($class_name, $compiler);
        }
        if ($params[ 2 ] === 'method') {
            $allowed = $this->trusted_static_methods;
            $name = substr($params[ 0 ], 0, strpos($params[ 0 ], '('));
        } else {
            $allowed = $this->trusted_static_properties;
            // strip '$'
            $name = substr($params[ 0 ], 1);
        }
        if (isset($allowed)) {
            if (!$allowed) {
                // fall back
                return $this->isTrustedStaticClass($class_name, $compiler);
            }
            if (isset($allowed[ $class_name ])
                && (empty($allowed[ $class_name ]) || in_array($name, $allowed[ $class_name ]))
            ) {
                return true;
            }
        }
        $compiler->trigger_template_error("access to static class '{$class_name}' {$params[2]} '{$name}' not allowed by security setting");
        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if PHP modifier is trusted.
     *
     * @param string $modifier_name
     * @param object $compiler compiler object
     * @return boolean                 true if modifier is trusted
     */
    public function isTrustedPhpModifier($modifier_name, $compiler)
    {
        if (isset($this->php_modifiers)
            && (empty($this->php_modifiers) || in_array($modifier_name, $this->php_modifiers))
        ) {
            return true;
        }
        $compiler->trigger_template_error("modifier '{$modifier_name}' not allowed by security setting");
        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if tag is trusted.
     *
     * @param string $tag_name
     * @param object $compiler compiler object
     *
     * @return boolean                 true if tag is trusted
     */
    public function isTrustedTag($tag_name, $compiler)
    {
        // check for internal always required tags
        if (in_array(
            $tag_name,
            array(
                'assign', 'call', 'private_filter', 'private_block_plugin', 'private_function_plugin',
                'private_object_block_function', 'private_object_function', 'private_registered_function',
                'private_registered_block', 'private_special_variable', 'private_print_expression',
                'private_modifier'
            )
        )
        ) {
            return true;
        }
        // check security settings
        if (empty($this->allowed_tags)) {
            if (empty($this->disabled_tags) || !in_array($tag_name, $this->disabled_tags)) {
                return true;
            } else {
                $compiler->trigger_template_error("tag '{$tag_name}' not allowed by security setting", null, true);
            }
        } elseif (in_array($tag_name, $this->allowed_tags) && !in_array($tag_name, $this->disabled_tags)) {
            return true;
        } else {
            $compiler->trigger_template_error("tag '{$tag_name}' not allowed by security setting", null, true);
        }
        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if special $smarty variable is trusted.
     *
     * @param string $var_name
     * @param object $compiler compiler object
     *
     * @return boolean                 true if tag is trusted
     */
    public function isTrustedSpecialSmartyVar($var_name, $compiler)
    {
        if (!in_array($var_name, $this->disabled_special_smarty_vars)) {
            return true;
        } else {
            $compiler->trigger_template_error(
                "special variable '\$smarty.{$var_name}' not allowed by security setting",
                null,
                true
            );
        }
        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if modifier plugin is trusted.
     *
     * @param string $modifier_name
     * @param object $compiler compiler object
     *
     * @return boolean                 true if tag is trusted
     */
    public function isTrustedModifier($modifier_name, $compiler)
    {
        // check for internal always allowed modifier
        if (in_array($modifier_name, array('default'))) {
            return true;
        }
        // check security settings
        if (empty($this->allowed_modifiers)) {
            if (empty($this->disabled_modifiers) || !in_array($modifier_name, $this->disabled_modifiers)) {
                return true;
            } else {
                $compiler->trigger_template_error(
                    "modifier '{$modifier_name}' not allowed by security setting",
                    null,
                    true
                );
            }
        } elseif (in_array($modifier_name, $this->allowed_modifiers)
                  && !in_array($modifier_name, $this->disabled_modifiers)
        ) {
            return true;
        } else {
            $compiler->trigger_template_error(
                "modifier '{$modifier_name}' not allowed by security setting",
                null,
                true
            );
        }
        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if constant is enabled or trusted.
     *
     * @param string $const    constant name
     * @param object $compiler compiler object
     *
     * @return bool
     */
    public function isTrustedConstant($const, $compiler)
    {
        if (in_array($const, array('true', 'false', 'null'))) {
            return true;
        }
        if (!empty($this->trusted_constants)) {
            if (!in_array(strtolower($const), $this->trusted_constants)) {
                $compiler->trigger_template_error("Security: access to constant '{$const}' not permitted");
                return false;
            }
            return true;
        }
        if ($this->allow_constants) {
            return true;
        }
        $compiler->trigger_template_error("Security: access to constant '{$const}' not permitted");
        return false;
    }

    /**
     * Check if stream is trusted.
     *
     * @param string $stream_name The stream's scheme/wrapper
     *
     * @return boolean         true if stream is trusted
     * @throws SmartyException if stream is not trusted
     */
    public function isTrustedStream($stream_name)
    {
        if (isset($this->streams) && (!$this->streams || in_array($stream_name, $this->streams))) {
            return true;
        }
        throw new SmartyException("stream '{$stream_name}' not allowed by security setting");
    }

    /**
     * Check if directory of file resource is trusted.
     *
     * @param string    $filepath
     * @param bool|null $isConfig Default null
     *
     * @return bool true if directory is trusted
     */
    public function isTrustedResourceDir($filepath, $isConfig = null)
    {
        $needle = dirname($filepath);
        if (!is_dir($needle)) {
            return false;
        }

        if ($this->_include_path_status !== $this->smarty->use_include_path) {
            $_dir = $this->smarty->use_include_path ?
                $this->smarty->ext->_getIncludePath->getIncludePathDirs($this->smarty) : array();
            if ($this->_include_dir !== $_dir) {
                $this->_updateResourceDir($this->_include_dir, $_dir);
                $this->_include_dir = $_dir;
            }
            $this->_include_path_status = $this->smarty->use_include_path;
        }
        $_dir = $this->smarty->getTemplateDir();
        if ($this->_template_dir !== $_dir) {
            $this->_updateResourceDir($this->_template_dir, $_dir);
            $this->_template_dir = $_dir;
        }
        $_dir = $this->smarty->getConfigDir();
        if ($this->_config_dir !== $_dir) {
            $this->_updateResourceDir($this->_config_dir, $_dir);
            $this->_config_dir = $_dir;
        }
        if ($this->_secure_dir !== $this->secure_dir) {
            $this->secure_dir = (array)$this->secure_dir;
            foreach ($this->secure_dir as $k => $d) {
                $this->secure_dir[ $k ] = $this->smarty->_realpath($d . DIRECTORY_SEPARATOR, true);
            }
            $this->_updateResourceDir($this->_secure_dir, $this->secure_dir);
            $this->_secure_dir = $this->secure_dir;
        }

        $nc = strtr($needle,'\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $props = ($isConfig) ? ['_include_dir', '_config_dir'] : ['_include_dir', '_template_dir', '_secure_dir'];
        foreach ($props as $propval) {
            if ($this->$propval) {
                foreach ($this->$propval as $one) {
                    $oc = strtr($one, '\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
                    if (strpos($oc, $nc) === 0) {
                        if (!isset($this->_resource_dir[$nc])) {
                            $this->_resource_dir[$nc] = true;
                        }
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check if URI is trusted (e.g. for {fetch} or {html_image}).
     * To simplify things, isTrustedUri() resolves all input to "{$PROTOCOL}://{$HOSTNAME}".
     * So "http://username:password@hello.world.example.org:8080/some-path?some=query-string"
     * is reduced to "http://hello.world.example.org" prior to applying the patterns from {@link $trusted_uri}.
     *
     * @param string $uri
     *
     * @return boolean         true if URI is trusted
     * @throws SmartyException if URI is not trusted
     * @uses   $trusted_uri for list of patterns to match against $uri
     */
    public function isTrustedUri($uri)
    {
        $_uri = parse_url($uri);
        if (!empty($_uri[ 'scheme' ]) && !empty($_uri[ 'host' ])) {
            $_uri = $_uri[ 'scheme' ] . '://' . $_uri[ 'host' ];
            foreach ($this->trusted_uri as $pattern) {
                if (preg_match($pattern, $_uri)) {
                    return true;
                }
            }
        }
        throw new SmartyException("URI '{$uri}' not allowed by security setting");
    }

    /**
     * Remove old directories and sub-directories, add new directories
     *
     * @param array $oldDir
     * @param array $newDir
     */
    private function _updateResourceDir(array $oldDir, array $newDir)
    {
        foreach ($oldDir as $directory) {
//          $directory = $this->smarty->_realpath($directory, true);
            $length = strlen($directory);
            foreach ($this->_resource_dir as $dir => $v) { // $v always true
                if (strncmp($dir, $directory, $length) == 0) {
                    unset($this->_resource_dir[$dir]);
                }
            }
        }
        foreach ($newDir as $directory) {
//          $directory = $this->smarty->_realpath($directory, true);
            $this->_resource_dir[$directory] = true;
        }
    }

    /**
     * Apply or revert a security class
     *
     * @param \Smarty                     $smarty
     * @param string|Smarty_Security|null $security_class If a string is used, it must be class-name
     *
     * @return \Smarty current Smarty instance for chaining
     * @throws \SmartyException when an invalid class name is provided
     */
    public static function enableSecurity(Smarty $smarty, $security_class)
    {
        if ($security_class instanceof Smarty_Security) {
            $smarty->security_policy = $security_class;
            return $smarty;
        } elseif (is_object($security_class)) {
            throw new SmartyException("Class '" . get_class($security_class) . "' must extend Smarty_Security.");
        }
        if ($security_class === null) {
            $security_class = $smarty->security_class;
        }
        if (!class_exists($security_class)) {
            throw new SmartyException("Security class '$security_class' is not defined");
        } elseif ($security_class !== 'Smarty_Security' && !is_subclass_of($security_class, 'Smarty_Security')) {
            throw new SmartyException("Class '$security_class' must extend Smarty_Security.");
        } else {
            $smarty->security_policy = new $security_class($smarty);
        }
        return $smarty;
    }

    /**
     * Method called at start of template rendering
     *
     * @param $template
     *
     * @throws SmartyException
     */
    public function startTemplate($template)
    {
        if ($this->max_template_nesting > 0 && $this->_current_template_nesting++ >= $this->max_template_nesting) {
            throw new SmartyException("maximum template nesting level of '{$this->max_template_nesting}' exceeded when calling '{$template->template_resource}'");
        }
    }

    /**
     * Method called at end of template rendering
     */
    public function endTemplate()
    {
        if ($this->max_template_nesting > 0) {
            $this->_current_template_nesting--;
        }
    }

    /**
     * Register functions called at start/end of template rendering
     *
     * @param \Smarty_Internal_Template $template
     */
    public function registerCallBacks(Smarty_Internal_Template $template)
    {
        $template->startRenderCallbacks[] = array($this, 'startTemplate');
        $template->endRenderCallbacks[] = array($this, 'endTemplate');
    }
}
