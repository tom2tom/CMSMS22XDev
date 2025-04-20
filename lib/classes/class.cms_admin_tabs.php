<?php
/**
 * A simple convenience class for creating a tabbed interface in the CMSMS admin console
 * NOTE: it might be necessary to explicitly call cms_admin_tabs::reset()
 * to get this to this work properly with > 1 tabs-layout in a single request
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell
 */
final class cms_admin_tabs
{
  /**
   * @ignore
   */
  private function __construct() {}

  /**
   * @ignore
   * string active-tab key
   */
  private static $_current_tab = '';

  /**
   * @ignore
   * bool
   */
  private static $_start_headers_sent = FALSE;

  /**
   * @ignore
   */
  private static $_end_headers_sent = FALSE;

  /**
   * @ignore
   */
  private static $_start_content_sent = FALSE;

  /**
   * @ignore
   * true from start_tab() until end_tab()
   */
  private static $_in_tab = FALSE;

  /**
   * @ignore
   */
  private static $_tab_idx = 0;

  /**
   * @since 2.2.21F2
   */
  public static function reset()
  {
    self::$_current_tab = '';
    self::$_start_headers_sent = FALSE;
    self::$_end_headers_sent = FALSE;
    self::$_start_content_sent = FALSE;
    self::$_in_tab = FALSE;
    self::$_tab_idx = 0;
  }

  /**
   * Set the current active tab
   *
   * @param string $tabid The tab key
   */
  public static function set_current_tab($tabid)
  {
    self::$_current_tab = $tabid;
  }

  /**
   * Begin output of tab headers
   *
   * @param bool $infill Whether to automatically interpolate related elements. Default false.
   *
   * @return string
   */
  public static function start_tab_headers($infill = FALSE)
  {
    if( $infill ) {
      self::reset();
      self::$_start_headers_sent = TRUE;
    }
    return "\n<div id=\"page_tabs\"><!-- StartTabHeaders -->\n";
  }

  /**
   * Create a tab header
   *
   * @param string $tabid The tab key
   * @param string $title The label to display in the tab
   * @param bool   $active Whether the tab is active. Default false.
   *  If false, and $tabid matches the recorded active tab identifier,
   *  then the tab will be treated as active.
   * @param bool   $infill Whether to automatically interpolate related elements. Default false.
   * @return string
   */
  public static function set_tab_header($tabid,$title,$active = FALSE,$infill = FALSE)
  {
    $out = "\n";
    if( $infill ) {
      if( !self::$_start_headers_sent ) {
        $out = self::start_tab_headers(TRUE); //might include a properties-reset
      }
    }

    if( !$active ) {
      if( (self::$_tab_idx == 0 && self::$_current_tab == '') || self::$_current_tab == $tabid ) {
        $active = true;
      }
    }
    if( $active && !self::$_current_tab ) {
      self::$_current_tab = $tabid;
      $a = ' class="active"';
    } else {
      $a = '';
    }
    $tabid = strtolower(str_replace(' ','_',$tabid));

    return $out . '<div id="'.$tabid.'"'.$a.' tabindex="'.self::$_tab_idx++.'">'.$title."</div>\n";
  }

  /**
   * Finish outputting tab headers
   *
   * @param bool $infill Whether to automatically interpolate related elements. Default false.
   *
   * @return string
   */
  public static function end_tab_headers($infill = FALSE)
  {
    if( $infill ) {
      self::$_end_headers_sent = TRUE;
    }
    return "\n</div><!-- EndTabHeaders -->\n";
  }

  /**
   * Start the content portion of the tabbed layout
   *
   * @param bool $infill Whether to automatically interpolate related elements. Default false.
   *
   * @return string
   */
  public static function start_tab_content($infill = FALSE)
  {
    $out = "\n";
    if( $infill ) {
      if( !self::$_end_headers_sent ) {
        $out = self::end_tab_headers(true);
      }
      self::$_start_content_sent = true;
    }
    return $out . "<div class=\"clearb\"></div>\n<div id=\"page_content\"><!-- StartTabContent-->\n";
  }

  /**
   * Finish the content portion of the tabbed layout
   *
   * @param bool $infill Whether to automatically interpolate related elements. Default false.
   *
   * @return string
   */
  public static function end_tab_content($infill = FALSE)
  {
    $out = "\n";
    if( $infill ) {
      if( self::$_in_tab ) {
        $out = self::end_tab(TRUE);
      }
      self::reset(); // in case there will be more tab-layouts in this request
    }
    return $out . "</div><!-- EndTabContent -->\n";
  }

  /**
   * Start the content portion of a specific tab
   *
   * @param string $tabid The tab key
   * @param array  $params parameters for the tab. Default empty.
   *  Only the 'tab_message' parameter, if any, is used here
   * @param bool $infill Whether to automatically interpolate related elements. Default false.
   *
   * @return string
   */
  public static function start_tab($tabid,$params = array(),$infill = FALSE)
  {
    $message = '';
    if( $tabid == self::$_current_tab && !empty($params['tab_message']) ) {
      $theme = cms_utils::get_theme_object();
      if( is_object($theme) ) $message = $theme->ShowMessage($params['tab_message']);
    }

    if( $infill ) {
      $out = '';
      if( !self::$_start_content_sent ) $out .= self::start_tab_content(TRUE);
      if( self::$_in_tab ) $out .= self::end_tab(TRUE);
      if (!$out) { $out = "\n"; }
      self::$_in_tab = TRUE;
    } else {
      $out = "\n";
    }
    return $out . '<div id="' . strtolower(str_replace(' ', '_', $tabid)) . "_c\"><!-- StartTab -->\n".$message;
  }

  /**
   * End the content portion of a single tab
   *
   * @param bool $infill Whether to automatically interpolate related elements. Default false.
   *
   * @return string
   */
  public static function end_tab($infill = FALSE)
  {
    if( $infill ) {
      if( !self::$_in_tab ) {
        return '';
      }
      self::$_in_tab = FALSE;
    }
    return "\n</div><!-- EndTab -->\n";
  }
} // end of class

?>
