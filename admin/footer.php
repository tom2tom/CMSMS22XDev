<?php
// $USE_THEME inherited from parent scope.
if (!isset($USE_THEME) || $USE_THEME ) {
    // if using the theme... echo the footer to stdout
    $themeObject->do_footer();
}

$gCms = CmsApp::get_instance();
$config = cms_config::get_instance();
if ($config['debug']) { // aka CMS_DEBUG
    // echo debug output to stdout
    echo '<div id="DebugFooter">';
    $arr = $gCms->get_errors();
    foreach ($arr as $error) {
        echo $error;
    }
    echo '</div> <!-- end DebugFooter -->';
}

// Pull the stuff out of the buffer...
// $bodycontent should contain what goes in the main content area of the page.
$bodycontent = '';
if (!(isset($USE_OUTPUT_BUFFERING) && $USE_OUTPUT_BUFFERING == false)) {
  $bodycontent = @ob_get_contents();
  @ob_end_clean();
}

// Do any header replacements (this is for WYSIWYG stuff)
$userid = get_userid();
$modops = ModuleOperations::get_instance();
// initialize the requested wysiwyg modules
// because those can change during module action etc, it's done here in the footer.
$list = CmsFormUtils::get_requested_wysiwyg_modules();
if( $list && is_array($list) ) {
    foreach( $list as $module_name => $info ) {
        $obj = $modops->get_module_instance($module_name);
        if( !is_object($obj) ) {
            audit('','Core','WYSIWYG module '.$module_name.' requested, but could not be instantiated');
            continue;
        }

        // parse the list once and get all of the stylesheet names (if any)
        // preload all of the named stylesheets.  to minimize queries.
        $css = [];
        $cssnames = array();
        foreach( $info as $rec ) {
            if( $rec['stylesheet'] == '' || $rec['stylesheet'] == CmsFormUtils::NONE ) continue;
            $cssnames[] = $rec['stylesheet'];
        }
        $cssnames = array_unique($cssnames);
        if( $cssnames ) $css = CmsLayoutStylesheet::load_bulk($cssnames);

        // adjust the cssnames array to only contain the list of the stylesheets we actually found.
        if( is_array($css) && count($css) ) {
            $tmpnames = array();
            foreach( $css as $stylesheet ) {
                $name = $stylesheet->get_name();
                if( !in_array($name,$tmpnames) ) $tmpnames[] = $name;
            }
            $cssnames = $tmpnames;
        }
        else {
            $cssnames = [];
        }

        // initialize each 'specialized' textarea.
        $need_generic = FALSE;
        foreach( $info as $rec ) {
            $selector = $rec['id'];
            $cssname = $rec['stylesheet'];

            if( $cssname == CmsFormUtils::NONE ) $cssname = '';
            if( !$cssname || !is_array($cssnames) || !in_array($cssname,$cssnames) || $selector == CmsFormUtils::NONE ) {
                $need_generic = TRUE;
                continue;
            }

            $selector = 'textarea#'.$selector;
            $themeObject->add_headtext($obj->WYSIWYGGenerateHeader($selector,$cssname));
        }

        // now, do we need a generic iniitialization?
        if( $need_generic ) {
            $themeObject->add_headtext($obj->WYSIWYGGenerateHeader());
        }
    }
}

// initialize the requested syntax hilighter modules
$list = CmsFormUtils::get_requested_syntax_modules();
if( $list && is_array($list) ) {
    foreach( $list as $one ) {
        $obj = $modops->get_module_instance($one);
        if( is_object($obj) ) $themeObject->add_headtext($obj->SyntaxGenerateHeader());
    }
}

$list = CMSMS\HookManager::do_hook_accumulate('admin_add_footertext');
if( $list && is_array($list) ) {
    foreach( $list as $one ) {
        $one = trim($one);
        if( $one ) $themeObject->add_footertext($one);
    }
}

$bodycontent = $themeObject->postprocess($bodycontent);
echo $bodycontent;

if (!isset($USE_THEME) || $USE_THEME) {
    if( strpos($bodycontent,'</body') === FALSE ) echo '</body></html>';
    if( isset($config['show_performance_info']) ) {
        $memory = (function_exists('memory_get_usage')?memory_get_usage():0);
        $memory_net = 'n/a';
        if( isset($orig_memory) ) $memory_net = $memory - $orig_memory;
        $memory_peak = (function_exists('memory_get_peak_usage')?memory_get_peak_usage():0);
        $endtime = microtime();
        $db = $gCms->GetDb();
        echo '<div style="clear: both;">'.microtime_diff($starttime,$endtime)." / ".(isset($db->query_count)?$db->query_count:'')." queries / Net Memory: {$memory_net} / End: {$memory} / Peak: {$memory_peak}</div>\n";
    }
}

$obj = new CMSMS\JobCheck();
$obj->initiate_background_processing();

?>
