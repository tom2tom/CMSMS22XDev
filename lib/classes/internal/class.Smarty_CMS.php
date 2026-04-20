<?php
#CMS Made Simple class Smarty_CMS
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
#$Id$

/**
 * Extends the Smarty class for content.
 *
 * @package CMS
 * @since 0.1
 */
class Smarty_CMS extends CMSSmartyBase
{
    /**
     * @var string | null
     * Optional cache_id prefix
     */
    protected $_global_cache_id;

    /**
     * @var Smarty_CMS
     * Singleton object
     */
    private static $_instance;

    /**
     * @var array
     * Stack of Template objects for simulating parent and child scopes
     * while directly using Smarty_CMS::fetch()
     * @todo is this deprecated ?
     */
    private $_tpl_stack = [];

    /**
     * Constructor
     */
    public function __construct()
    {
//      global $CMS_INSTALL_PAGE; //see CmsApp::STATE_INSTALL usage
        parent::__construct();

//Smarty 2,3      $this->direct_access_security = true;
        // Set template_c and cache dirs
        $this->setCompileDir(TMP_TEMPLATES_C_LOCATION);
        $this->setCacheDir(TMP_CACHE_LOCATION);
        $this->assignGlobal('app_name','CMSMS'); //deprecated Smarty5+

        if( CMS_DEBUG ) $this->error_reporting = E_ALL;

        // sub-class Smarty Template, to do some init for all templates
        $this->template_class = 'CMS_Smarty_Template';

        // common resources.
//      $this->registerResource('file',new CMSMS\internal\FileResource()); TODO subclass Smarty resource with more checks
        $this->registerResource('module_db_tpl',new CMSMS\internal\ModuleDbTemplateResource());
        $this->registerResource('module_file_tpl',new CMSMS\internal\ModuleFileTemplateResource());
        $this->registerResource('cms_template',new CMSMS\internal\TemplateResource()); // <- Should proably be global and removed from parser?
        $this->registerResource('template',new CMSMS\internal\TemplateResource()); // <- Should proably be global and removed from parser? // deprecated
        $this->registerResource('cms_stylesheet',new CMSMS\internal\StylesheetResource());

        // register default plugin handler
        $this->registerDefaultPluginHandler(array($this,'defaultPluginHandler'));

        // Load User Defined Tags
        $_gCms = CmsApp::get_instance();
        if( !$_gCms->test_state(CmsApp::STATE_INSTALL) ) {
            $utops = UserTagOperations::get_instance();
            $usertags = $utops->ListUserTags();

            if( !empty( $usertags ) ) {
                foreach( $usertags as $id => $name ) {
                    $function = $utops->CreateTagFunction($name);
                    $this->registerPlugin('function',$name,$function,false);
                }
            }
        }

        $config = cms_config::get_instance();
        $this->addPluginsDir($config['assets_path'].'/plugins');
        $this->addPluginsDir(cms_join_path(CMS_ROOT_PATH,'plugins')); // deprecated
        $this->addPluginsDir(cms_join_path(CMS_ROOT_PATH,'lib','plugins'));
        $this->addTemplateDir(cms_join_path(CMS_ROOT_PATH,'lib','assets','templates'));

        if( $_gCms->is_frontend_request() ) {
            $this->addTemplateDir($config['assets_path'].'/templates');
            $this->setConfigDir([$config['assets_path'].'/configs',TMP_CONFIG_LOCATION]);

            // Check if we are at install page, don't register anything if so, cause nothing below is needed.
//see STATE_INSTALL below            if(isset($CMS_INSTALL_PAGE)) return;

            if( is_sitedown() ) {
                $this->setCaching(Smarty::CACHING_OFF);
                $this->force_compile = true; // redundant with CACHING_OFF ?
            }
            else {
                if( cms_siteprefs::get('use_smartycache',false) ) {
                    $val = (int)cms_siteprefs::get('SmartyFrontcacheLife',60); // minutes
                    if( $val > 0 ) {
                        $this->setCacheLifetime($val * 60);
                        $val = (CMS_DEBUG || cms_siteprefs::get('use_smartycompilecheck',true)) ? Smarty::COMPILECHECK_ON : Smarty::COMPILECHECK_CACHEMISS;
                        $this->setCompileCheck($val);
                        $val = Smarty::CACHING_LIFETIME_SAVED;
                    }
                    else {
                        $val = Smarty::CACHING_OFF;
                    }
                }
                else {
                    $val = Smarty::CACHING_OFF;
                }
                $this->setCaching($val); // might be changed in index.php per page cachability
            }

            // Load resources
            $this->registerResource('tpl_top',new CMSMS\internal\TemplateResource('top'));
            $this->registerResource('tpl_head',new CMSMS\internal\TemplateResource('head'));
            $this->registerResource('tpl_body',new CMSMS\internal\TemplateResource('body'));
            $this->registerResource('content',new CMSMS\internal\ContentTemplateResource());

            // just for frontend actions.
            $this->registerPlugin('compiler','content','CMS_Content_Block::smarty_compile_fecontentblock',false);
            $this->registerPlugin('function','content_image','CMS_Content_Block::smarty_fetch_imageblock',false);
            $this->registerPlugin('function','content_module','CMS_Content_Block::smarty_fetch_moduleblock',false);
            $this->registerPlugin('function','process_pagedata','CMS_Content_Block::smarty_fetch_pagedata',false);

            // Autoload filters
            $this->autoloadFilters();

            // Enable custom security, permissive or not
            $this->enableSecurity('CMSSmartySecurityPolicy');
        }
        elseif( $_gCms->test_state(CmsApp::STATE_ADMIN_PAGE) ) {
            /* Some pages e.g. CM and DM polled-lists hate a cached template
               So admin caching is disabled here but might be enabled in-context */
            $this->setCaching(Smarty::CACHING_OFF);
            $admin_dir = $config['admin_path'];
            $this->addPluginsDir($admin_dir.DIRECTORY_SEPARATOR.'plugins');
            $this->setTemplateDir($admin_dir.DIRECTORY_SEPARATOR.'templates');
            $this->setConfigDir([$config['assets_path'].DIRECTORY_SEPARATOR.'configs',$admin_dir.DIRECTORY_SEPARATOR.'configs',TMP_CONFIG_LOCATION]);
            $this->registerResource('admin_tpl',new CMSMS\internal\AdminTemplateResource());
            $this->registerResource('theme_file_tpl',new CMSMS\internal\AdminThemeTemplateResource());
            // TODO custom security for admin might be a breaker
            $this->enableSecurity('CMSSmartySecurityPolicy');
        }
        elseif( $_gCms->test_state(CmsApp::STATE_INSTALL) ) {
            $this->addTemplateDir($config['assets_path'].DIRECTORY_SEPARATOR.'templates');
            // no change to default security during installer run
        }
        // $_call->func(args) can be used in templates instead of func(args) esp. for Smarty 4.5.1+
        $this->assignGlobal('_call',new Smarty_TemplateCaller($this));
        // _call::class__method(args) can be used in templates instead of class::method(args) esp. for Smarty 4.5.1+
        $this->registerClass('_call',Smarty_TemplateCaller::class);
    }

    /**
     * get_instance method
     *
     * @return object $this
     */
    public static function get_instance()
    {
        if( !self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Load filters from CMSMS plugins folder
     *
     * @return void
     */
    private function autoloadFilters()
    {
        $pre = array();
        $post = array();
        $output = array();

        foreach( $this->plugins_dir as $onedir ) {
            if( !is_dir($onedir) ) continue;

            $files = glob($onedir.'/*php');
            if( !is_array($files) || count($files) == 0 ) continue;

            foreach( $files as $onefile ) {
                $onefile = basename($onefile);
                $parts = explode('.',$onefile);
                if( !is_array($parts) || count($parts) != 3 ) continue;

                switch( $parts[0] ) {
                case 'outputfilter':
                    $output[] = $parts[1];
                    break;

                case 'prefilter':
                    $pre[] = $parts[1];
                    break;

                case 'postfilter':
                    $post[] = $parts[1];
                    break;
                }
            }
        }

        $this->autoload_filters = array('pre'=>$pre,'post'=>$post,'output'=>$output);
    }

    /**
     * Register class to be used in templates, and explicitly enable its use
     *
     * @param string $name key in recorded classes array
     * @param string $classname possibly-namespaced name understood by PHP
     */
    public function registerClass($name, $classname)
    {
        if( $this->security_policy ) {
            if( $this->security_policy->static_classes === null ) {
                //return; TODO non-compat but consistent with permissive smarty etc
                $this->security_policy->static_classes = [$name]; //deprecated since 2.2.19 ? or ok ?
            }
            elseif( $this->security_policy->static_classes !== [] ) {
                $this->security_policy->static_classes[] = $name; //top-up the whitelist
            }
        }
        parent::registerClass($name,$classname);
    }

    /**
     * Register a plugin for use in templates, without any duplication-error
     *
     * @param string   $type       plugin type
     * @param string   $tag        name of template tag
     * @param callback $callback   PHP callback to register
     * @param bool     $cacheable  whether this plugin is cachable Default true
     * @param array|null $cache_attr caching attributes if any
     * @return Smarty_Internal_Templatebase current Smarty_Internal_Templatebase (or Smarty or Smarty_Internal_Template) instance for chaining
     * @throws SmartyException when the plugin tag is invalid
     */
    public function registerPlugin($type, $tag, $callback, $cacheable = true, $cache_attr = null)
    {
        if( !isset($this->registered_plugins[$type][$tag]) ) { // don't barf if already registered
            return parent::registerPlugin($type,$tag,$callback,$cacheable,$cache_attr);
        }
        return $this;
    }

    /**
     * defaultPluginHandler
     * NOTE: Registered in constructor
     *
     * @param string $name
     * @param string $type
     * @param string $template
     * @param string $callback
     * @param string $script
     * @return bool true on success, false on failure
     */
    public function defaultPluginHandler($name, $type, $template, &$callback, &$script, &$cachable)
    {
        debug_buffer('',"Start Load Smarty Plugin $name/$type");

        // plugins with the smarty_cms_function
        $cachable = true;
        $dirs = [];
        $dirs[] = cms_join_path(CMS_ROOT_PATH,'assets','plugins',$type.'.'.$name.'.php');
        $dirs[] = cms_join_path(CMS_ROOT_PATH,'plugins',$type.'.'.$name.'.php');
        $dirs[] = cms_join_path(CMS_ROOT_PATH,'lib','plugins',$type.'.'.$name.'.php');
        foreach( $dirs as $fn ) {
            if( !is_file($fn) ) continue;

            require_once($fn);
            $script = $fn;

            $funcs = [];
            $funcs[] = 'smarty_nocache_'.$type.'_'.$name;
            $funcs[] = 'smarty_cms_'.$type.'_'.$name;
            foreach( $funcs as $func ) {
                if( !function_exists($func) ) continue;

                $callback = $func;
                $cachable = false;
                debug_buffer('',"End Load Smarty Plugin $name/$type");
                return true;
            }
        }

//        if( CmsApp::get_instance()->is_frontend_request() ) { also allow for admin requests
            $row = cms_module_smarty_plugin_manager::load_plugin($name,$type);
            if( $row && !empty($row['callback']) &&
                is_array($row['callback']) && count($row['callback']) == 2 &&
                is_string($row['callback'][0]) && is_string($row['callback'][1]) ) {
                $callback = $row['callback'][0].'::'.$row['callback'][1];
                $cachable = $row['cachable'];
                return true;
            }
//        }

        return false;
    }

    /**
     * Test if a Smarty function-plugin with the specified name is registered.
     *
     * @param string the plugin name
     * @return bool
     */
    public function is_registered($name)
    {
        return isset($this->registered_plugins['function'][$name]);
    }

    /**
     * Set or clear the global cache id.
     * This is a prefix that is used when smarty caching is enabled.
     *
     * @param mixed $id
     * @internal
     * @return void
     */
    public function set_global_cacheid($id)
    {
        if( $id === null || $id === false || $id === '' || strcasecmp($id,'nocache') == 0 ) {
            $this->_global_cache_id = null;
        }
        else {
            $this->_global_cache_id = $id;
        }
    }

    /**
     * Get the global cache id if any.
     *
     * @internal
     * @return mixed
     */
    public function get_global_cacheid()
    {
        return $this->_global_cache_id;
    }

    /**
     * Get a suitable parent template for a new template.
     *
     * Smarty uses a stack of ancestor templates. The parent of this
     * one will be the closest template on that stack, or if there is
     * no ancestor, then the global Smarty object is used.
     *
     * e.g.
     * <code>$smarty->createTemplate('somefile.tpl',$cache_id,$compile_id,$smarty->get_template_parent());</code>
     *
     * @since 2.0.1
     * @deprecated
     * @return Smarty_Internal_Template
     */
    public function get_template_parent()
    {
        // no parent specified, see if there is a stack of parents.
        if( $this->_tpl_stack ) {
            $parent = $this->_tpl_stack[count($this->_tpl_stack)-1];
        }
        else {
            // no stack, so use this (Smarty_CMS) class.
            $parent = $this;
        }
        return $parent;
    }

    /**
     * Fetch a rendered Smarty template
     * Fetching via this method instead of using a template-object's fetch
     * method directly is done by deprecated methods like
     * CMSModule::ProcessTemplate and CMSModule::ProcessTemplateFromDatabase etc
     *
     * @param mixed $template   resource handle of the template file, or template object, or null Default null
     * @param mixed $cache_id   cache id to be used with this template Default null
     * @param mixed $compile_id compile id to be used with this template Default null
     * @param mixed $parent     next higher level of Smarty variables Default null
     *
     * @throws Exception
     * @throws SmartyException
     * @return string rendered template output
     */
    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        if( CMS_DEBUG ) {
            if( is_object($template) ) {
                $name = $template->source->resource;
            }
            elseif( is_string($template) ) {
                $name = $template;
            }
            else {
                throw new Exception('Unknown template-value provided to '.__METHOD__);
            }
            if( startswith($name,'string:') ) {
                if( strlen($name) > 22 ) {
                    $name = substr($name,0,22) . '...';
                }
            }
            debug_buffer('','Fetch '.$name.' start');
        }

        if( is_object($template) ) {
            $_tpl = $template;
        }
        else {
            if( !$parent ) {
                // get the parent off of the stack.
                $parent = $this->get_template_parent();
            }
            $_tpl = $this->createTemplate($template,$cache_id,$compile_id,$parent);
        }

        //put the new template onto the stack, and do our work, to handle recursive calls
        $this->_tpl_stack[] = $_tpl;
        $tmp = $_tpl->fetch(); //downstream needs to populate its template variable
        // and pop off the stack again
        array_pop($this->_tpl_stack);

        // admin requests are a bit fugged up... lots of stuff relies on a single smarty scope.
        // gotta fix that.
        if( CMS_DEBUG ) {
            debug_buffer('','Fetch '.$name.' end');
        }
        return $tmp;
    }

    /**
     * Create a template object
     *
     * @param string $template   resource handle of the template file
     * @param mixed  $cache_id   cache id to be used with this template Default null
     * @param mixed  $compile_id compile id to be used with this template Default null
     * @param mixed  $parent     next higher level of Smarty variables Default null
     * @param bool   $do_clone   flag whether to clone Smarty object Default true
     *
     * @return Smarty_Internal_Template object
     * @throws LogicException or SmartyException
     */
    public function createTemplate($template, $cache_id = null, $compile_id = null, $parent = null, $do_clone = true)
    {
        if( !(strncasecmp($template,'string:',7) == 0 || strncasecmp($template,'eval:',5) == 0)) { // TODO mebbe a sanity-check for eval: content ?
            if( strpos($template,'*') !== false ) { throw new LogicException("$template is an invalid CMSMS resource specification"); }
            if( strncasecmp($template,'file:',5) == 0 ) {
                // validate the specified file
                //$file = trim(substr($template,5));
            }
            elseif( (strpos($template,'/')) > 0 ) { throw new LogicException("$template is an invalid CMSMS resource specification"); }
        }
        if( $cache_id === null || $cache_id === false || $cache_id === '' ) {
            $cache_id = $this->_global_cache_id;
        }
        elseif( $cache_id[0] == '|' ) {
            $cache_id = $this->_global_cache_id . $cache_id;
        }
        elseif( strcasecmp($cache_id,'nocache') == 0 ) { // since 2.2.23F2
            $cache_id = null;
        }
        return parent::createTemplate($template,$cache_id,$compile_id,$parent,$do_clone);
    }

    /**
     * Clear cache for a template
     *
     * @param string $template_name
     * @param mixed $cache_id Default null
     * @param mixed $compile_id Default null
     * @param mixed $exp_time expiration time Default null
     * @param mixed $type resource type Default null
     * @return mixed
     */
    public function clearCache($template_name, $cache_id = null, $compile_id = null, $exp_time = null, $type = null)
    {
        if( $cache_id === null || $cache_id === false || $cache_id === '' ) {
            $cache_id = $this->_global_cache_id;
        }
        elseif( $cache_id[0] == '|' ) {
            $cache_id = $this->_global_cache_id . $cache_id;
        }
        elseif( strcasecmp($cache_id,'nocache') == 0 ) {
            $cache_id = null;
        }
        return parent::clearCache($template_name,$cache_id,$compile_id,$exp_time,$type);
    }

    /**
     * Get whether a template is cached
     *
     * @param mixed $template Default null
     * @param mixed $cache_id Default null
     * @param mixed $compile_id Default null
     * @param mixed $parent Default null
     * @return mixed
     */
    public function isCached($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        if( $cache_id === null || $cache_id === false || $cache_id === '' ) {
            $cache_id = $this->_global_cache_id;
        }
        elseif( $cache_id[0] == '|' ) {
            $cache_id = $this->_global_cache_id . $cache_id;
        }
        elseif( $cache_id == 'nocache' ) {
            $cache_id = null;
        }
        return parent::isCached($template,$cache_id,$compile_id,$parent);
    }

    /**
     * Error console
     *
     * @param object Exception $e
     * @return html
     * @author Stikki
     */
    public function errorConsole(Exception $e)
    {
        $this->force_compile = true;
        //$this->debugging = get_userid(false);

        $this->assign('e_line',$e->getLine());
        $this->assign('e_file',$e->getFile());
        $this->assign('e_message',$e->getMessage());
        $this->assign('e_trace',htmlentities($e->getTraceAsString()));
        $this->assign('loggedin',get_userid(false));

        // put mention into the admin log
        audit('','Smarty','Error: '.$e->getMessage().'. Backtrace in debug.log.');
        // and into the debug log
        $btarr = $e->getTrace();
        $btarr[] = ['file'=>$e->getFile(),'function'=>'unknown','line'=>$e->getLine()];
        debug_bt_to_log($btarr,3);

        $output = $this->fetch('cmsms-error-console.tpl');

        $this->force_compile = false;
//      $this->debugging = false;

        return $output;
    }

    /**
     * Try to find the handler of the named plugin or class
     * Note: this method overrides the one in the Smarty base class
     * and provides more testing.
     *
     * @param string $plugin_name wanted callable or filename or classname
     *   callable format: Smarty_PluginType_PluginName (any case) e.g. smarty_function_fixit
     *   filename format: plugintype.pluginname.php e.g. function.fixit.php
     *   classname format: same as callable
     *   [Pp]lugintype may be 'internal' to identify a system-plugin class
     * @param bool   $check       check if already loaded Default true
     *
     * @return string | bool filepath or true (if already loaded) or false (if not found)
     * @throws \SmartyException if format of $plugin_name is invalid
     */
    public function loadPlugin($plugin_name, $check = true)
    {
        // if function or class exists, nothing more to do
        if( $check && (is_callable($plugin_name) || class_exists($plugin_name,false)) ) return true;

        // plugin name is expected to be like: Smarty_[Type]_[Name]
        $_name_parts = explode('_',$plugin_name,3);

        // plugin name must have three parts to be valid
        // count($_name_parts) < 3 === !isset($_name_parts[2])
        if( !isset($_name_parts[2]) || strtolower($_name_parts[0]) !== 'smarty' ) {
            throw new SmartyException("plugin {$plugin_name} is not a valid name format");
            return false; //useless
        }

        // if type is "internal", get plugin from sysplugins
        if( strtolower($_name_parts[1]) == 'internal' ) {
            $file = SMARTY_SYSPLUGINS_DIR . strtolower($plugin_name) . '.php';
            if( file_exists($file) ) {
                require_once($file);
                return $file;
            }
            else {
                return false;
            }
        }

        // plugin filename is expected to be: [type].[name].php
        $_plugin_filename = "{$_name_parts[1]}.{$_name_parts[2]}.php";

        $_stream_resolve_include_path = function_exists('stream_resolve_include_path');

        // poll plugin dirs to find the plugin
        foreach( $this->getPluginsDir() as $_plugin_dir ) {
            $names = array($_plugin_dir . $_plugin_filename,
                           $_plugin_dir . strtolower($_plugin_filename)
                );

            foreach( $names as $file ) {
                if( file_exists($file) ) {
                    require_once($file);
                    if( is_callable($plugin_name) || class_exists($plugin_name,false) ) return $file;
                }

                if( $this->use_include_path &&
                    !preg_match('/^([\/\\\\]|[a-zA-Z]:[\/\\\\])/',$_plugin_dir) ) {
                    // try PHP include_path
                    if( $_stream_resolve_include_path ) {
                        $file = stream_resolve_include_path($file);
                    }
                    else {
                        $file = Smarty_Internal_Get_Include_Path::getIncludePath($file);
                    }
                    if( $file ) {
                        require_once $file;
                        if( is_callable($plugin_name) || class_exists($plugin_name,false) ) return $file;
                    }
                }
            }
        }
        // no match found
        return false;
    }

    /**
     * Change the current caching mode
     * For admin scripts (module-actions and others) to tailor their
     * templates' processing.
     * Does not involve any module's 'base' properties
     * @since 3.2.23F2
     *
     * @param bool $state The wanted cachability. Default true.
     * @param array $context parameters for checking whether to enable caching. Default []. UNUSED
     */
    public function changeCaching($state = true, array $context = [])
    {
        if( $state && $this->caching == Smarty::CACHING_OFF &&
            cms_siteprefs::get('use_smartycache',false) ) {
            if( $_REQUEST && !empty($_REQUEST['mact']) ) {
                //TODO maybe tailor per module & action in mact
                return; // no automatic caching for module-action requests
            }
            if( cmsms()->is_frontend_request() ) {
                $val = cms_siteprefs::get('SmartyAdmincacheLife',60); // mins
            }
            else {
                $val = cms_siteprefs::get('SmartyFrontcacheLife',30);
            }
            if( $val > 0 ) {
                $this->setCaching(Smarty::CACHING_LIFETIME_SAVED);
                $this->setCacheLifetime($val * 60); //secs
            }
            $this->force_compile = true;
        }
        elseif( !($state || $this->caching == Smarty::CACHING_OFF) ) {
            $this->setCaching(Smarty::CACHING_OFF);
            $this->force_compile = true;
        }
    }
} // class

/**
 * Workaround for some Smartys' (e.g. V5) disabling of all PHP function calls
 * $_call->func(args) can be used instead of an unregistered func(args)
 * _call::class__method(args) can be used instead of an unregistered class::method(args)
 * @since 2.2.19F2
 * @See also Install_TemplateCaller class
 */
class Smarty_TemplateCaller
{
    private $php_functions; // empty array means no restriction
    private static $static_classes; // ditto

    public function __construct($smarty)
    {
        $this->php_functions = &$smarty->security_policy->php_functions; //TODO check with Smarty5/6
        self::$static_classes = &$smarty->security_policy->static_classes; //TODO check with Smarty5/6
    }

    #[\ReturnTypeWillChange]
    public function __call($name, $args)
    {
        if( $this->php_functions !== null && (!$this->php_functions || in_array($name,$this->php_functions)) ) {
            return $name(...$args);
        }
        return "<!-- prohibited function $name called -->";
    }

    #[\ReturnTypeWillChange]
    public static function __callStatic($name, $args)
    {
        $pos = strpos($name, '__');
        if( self::$static_classes !== null && (!self::$static_classes ||
           ($classname = substr($name, 0, $pos) && in_array($classname, self::$static_classes))) ) {
            $name = substr_replace($name, '::', $pos, 2);
            return $name(...$args);
        }
        return "<!-- prohibited static function $name called -->";
    }
}
