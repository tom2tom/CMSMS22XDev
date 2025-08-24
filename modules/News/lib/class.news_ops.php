<?php
#News module class: news_ops
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

final class news_ops
{
  private static $_categories_loaded;
  private static $_cached_categories;
  private static $_cached_fielddefs;
  private static $_cached_fieldvals;

  // block object creation, this must be a singleton due to its properties
  private function __construct() {}

  /**
   *
   * @param string $id request parameters id/prefix
   * @param array $params
   * @param int $returnid Default -1
   * @return array
   */
  public static function get_categories($id,$params,$returnid = -1)
  {
    $tmp = self::get_all_categories();
    if( !$tmp ) return [];

    $catinfo = array();
    if( !isset($params['category']) || $params['category'] == '' ) {
      $catinfo = $tmp;
    }
    else {
      $categories = explode(',', $params['category']);
      foreach( $categories as $onecat ) {
        if( strpos($onecat,'*') !== FALSE ) {
          foreach( $tmp as $rec ) {
            if( fnmatch($onecat,$rec['long_name']) ) {
              $catinfo[] = $rec;
            }
          }
        }
        else {
          foreach( $tmp as $rec ) {
            if( $rec['long_name'] == $onecat ) {
              $catinfo[] = $rec;
            }
          }
        }
      }
    }
    unset($tmp);

    $cat_count = isset($catinfo) ? count($catinfo) : 0;
    if( $cat_count == 0 ) return [];

    $cat_ids = array();
    for( $i = 0, $n = count($catinfo); $i < $n; $i++ ) {
      $cat_ids[] = $catinfo[$i]['news_category_id'];
    }
    sort($cat_ids);
    $cat_ids = array_unique($cat_ids);

    // get counts.
    $depth = 1;
    $db = CmsApp::get_instance()->GetDb();
    $counts = array();
    $now = $db->DBTimeStamp(time());

    $q2 = 'SELECT news_category_id,COUNT(news_id) AS cnt FROM '.CMS_DB_PREFIX.'module_news WHERE news_category_id IN (';
    $q2 .= implode(',',$cat_ids).") AND (start_time IS NULL OR start_time <= $now)";
    if( empty($params['showarchive']) ) {
      $q2 .= " AND (end_time IS NULL OR end_time > $now) AND status = 'published'";
    }
    else {
      $q2 .= " AND status != 'draft'";
    }
    $q2 .= ' GROUP BY news_category_id';
    $tmp = $db->GetArray($q2);
    if( $tmp ) {
      for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
        $counts[$tmp[$i]['news_category_id']] = $tmp[$i]['cnt'];
      }
    }

    $rowcounter = 0;
    $items = array();
    $depth = 1;
    for( $i = 0, $n = count($catinfo); $i < $n; $i++ ) {
      $row =& $catinfo[$i];
      $row['index'] = $rowcounter++;
      $row['count'] = (isset($counts[$row['news_category_id']])) ? $counts[$row['news_category_id']] : 0;
      $row['prevdepth'] = $depth;
      $depth = count(explode('.', $row['hierarchy']));
      $row['depth']=$depth;

      // changes so that parameters supplied to the tag
      // get carried down through the links
      // screw pretty urls
      $parms = $params;
      unset($parms['browsecat']);
      unset($parms['category']);
      $parms['category_id'] = $row['news_category_id'];

      $pageid = (isset($params['detailpage']) && $params['detailpage']!='')?$params['detailpage']:$returnid;
      $mod = cms_utils::get_module('News');
      $row['url'] = $mod->CreateLink($id,'default',$pageid,$row['news_category_name'],$parms,'',true);
      $items[] = $row;
    }
    return $items;
  }

  /**
   * Get cached ordered categories
   *
   * @return array
   */
  public static function get_all_categories()
  {
    if( !self::$_categories_loaded ) {
      $db = CmsApp::get_instance()->GetDb();
      $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
      $dbresult = $db->GetArray($query);
      if( $dbresult ) { self::$_cached_categories = $dbresult; }
      else { self::$_cached_categories = []; }
      self::$_categories_loaded = TRUE;
    }
    return self::$_cached_categories;
  }

  /**
   * Get categories map: (long name|short name) => numeric id
   *
   * @return array
   */
  public static function get_category_list()
  {
    self::get_all_categories();
    $categorylist = array();
    if( self::$_cached_categories ) {
      for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
        $row = self::$_cached_categories[$i];
        $key = ($row['long_name']) ?: $row['news_category_name'];
        $categorylist[$key] = $row['news_category_id'];
      }
    }
    return $categorylist;
  }

  /**
   * Get categories map: numeric id => (short) name
   *
   * @return array
   */
  public static function get_category_names_by_id()
  {
    self::get_all_categories();
    $list = array();
    if( !empty(self::$_cached_categories) ) {
      for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
        $list[self::$_cached_categories[$i]['news_category_id']] = self::$_cached_categories[$i]['news_category_name'];
      }
    }
    return $list;
  }

  /**
   * Get category name corresponding to the specified id
   *
   * @return string maybe empty
   */
  public static function get_category_name_from_id($id)
  {
    self::get_all_categories();
    if( !empty(self::$_cached_categories) ) {
      for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
        if( $id == self::$_cached_categories[$i]['news_category_id'] ) {
          return self::$_cached_categories[$i]['news_category_name'];
        }
      }
    }
    return '';
  }

  /**
   * Get all cached article field-properties
   *
   * @param bool $publiconly Default true
   *
   * @return array
   */
  public static function get_fielddefs($publiconly = TRUE)
  {
    if( !is_array(self::$_cached_fielddefs) ) {
      $db = CmsApp::get_instance()->GetDb();
      if( $publiconly ) {
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE public = 1 ORDER BY item_order';
      }
      else {
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs ORDER BY item_order';
      }
      $tmp = $db->GetArray($query);

      self::$_cached_fielddefs = array();
      if( $tmp ) {
        for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
          self::$_cached_fielddefs[$tmp[$i]['id']] = $tmp[$i];
        }
      }
    }
    return self::$_cached_fielddefs;
  }

  /**
   * Get a field-object
   *
   * @param array $row Parameters to be used in the object
   *
   * @return mixed news_field object | null
   */
  public static function get_field_from_row($row)
  {
    if( !isset($row['id']) ) return null; // no object

    $res = new news_field();
    foreach( ['name','type','extra'] as $fld ) {
      if( !isset($row[$fld]) ) $row[$fld] = '';
    }
    foreach( $row as $key => $value ) {
      switch( $key ) {
      case 'id':
      case 'name':
      case 'type':
      case 'item_order':
      case 'public':
      case 'extra': // unserialized where (if non-null) ?
      case 'value':
        $res->$key = $value;
        break;
      }
    }
    return $res;
  }

  /**
   * Populate the specified article-object
   *
   * @param news_article $news object to be populated
   * @param array $params properties to be set in $news
   * @param bool $handle_uploads Default false.
   * @param bool $handle_deletes Default false.
   *
   * @return news_article $news
   */
  public static function fill_article_from_formparams(news_article $news,$params,$handle_uploads = FALSE,$handle_deletes = FALSE)
  {
    foreach( $params as $key => $value ) {
      switch( $key ) {
      case 'articleid':
        $news->id = $value;
        break;

      case 'author_id':
      case 'title':
      case 'content':
      case 'summary':
      case 'status':
      case 'icon':
      case 'news_url':
      case 'useexp':
      case 'extra':
        $news->$key = $value;
        break;

      case 'category':
        $list = self::get_category_names_by_id();
        foreach( $list as $cid => $name ) {
          if( $name == $value ) {
            $news->category_id = $cid;
            break 2;
          }
        }
        $news->category_id = 0;
        break;

      case 'postdate_Month':
        $news->postdate = mktime($params['postdate_Hour'], $params['postdate_Minute'], $params['postdate_Second'], $params['postdate_Month'], $params['postdate_Day'], $params['postdate_Year']);
        break;

      case 'startdate_Month':
        $news->startdate = mktime($params['startdate_Hour'], $params['startdate_Minute'], $params['startdate_Second'], $params['startdate_Month'], $params['startdate_Day'], $params['startdate_Year']);
        break;

      case 'startdate_Month':
        $news->enddate = mktime($params['enddate_Hour'], $params['enddate_Minute'], $params['enddate_Second'], $params['enddate_Month'], $params['enddate_Day'], $params['enddate_Year']);
        break;
      }
    }

    if( isset($params['customfield']) && is_array($params['customfield']) ) {
      $fielddefs = self::get_fielddefs();
      foreach( $params['customfield'] as $fid => $value ) {
        if( !isset($fielddefs[$fid]) ) continue;

        $field = self::get_field_from_row($fielddefs[$fid]);
        $field->value = $value;
        $news->set_field($field);
      }
    }
    // 'file' fields have different format
    $ffields = preg_filter('/^customfield_(\d+)$/', '$1', array_keys($params));
    if( $ffields && !isset($fielddefs) ) { $fielddefs = self::get_fielddefs(); }
    foreach( $ffields as $fid ) {
      if( isset($fielddefs[$fid]) ) {
        $field = self::get_field_from_row($fielddefs[$fid]);
        $field->value = $value;
        $news->set_field($field);
      }
    }

    return $news;
  }

  /**
   * @ignore
   * @param mixed $row should be array of db table data
   * @param string $get_fields Optional fields-type specifier Default 'PUBLIC'
   * @return mixed news_article object | null
   */
  private static function get_article_from_row($row,$get_fields = 'PUBLIC')
  {
    if( !is_array($row) ) return null; // no object
    $article = new news_article();
    foreach( $row as $key => $value ) {
      switch( $key ) {
      case 'news_id':
        $article->id = $value;
        break;

      case 'news_category_id':
        $article->category_id = $value;
        break;

      case 'news_category_name':
        $article->category_name = (string)$value;
        break;

      case 'news_title':
        $article->title = $value;
        break;

      case 'news_data':
        $article->content = $value;
        break;

      case 'news_date':
        $article->postdate = $value;
        break;

      case 'summary':
        $article->summary = $value;

      case 'start_time':
        $article->startdate = $value;
        break;

      case 'end_time':
        $article->enddate = $value;
        break;

      case 'status':
        $article->status = $value;
        break;

      case 'icon':
        $article->image_url = self::useformat_url($value);
        break;

      case 'create_date':
        $article->create_date = $value;
        break;

      case 'modified_date':
        $article->modified_date = $value;
        break;

      case 'author_id':
        $article->author_id = $value;
        break;

      case 'news_extra':
        $article->extra = $value;
        break;

      case 'news_url':
        $article->news_url = $value;
        break;
//      case 'searchable': $article->searchable = (bool)$value; break; irrelevant for display
      }
    }

    if( $get_fields && $get_fields != 'NONE' && $article->id ) {
      self::preloadFieldData($article->id);
      $fields = self::get_fields($article->id);
      if( $fields ) {
        foreach( $fields as $field ) {
          $article->set_field($field);
        }
      }
    }

    return $article;
  }

  /**
   * Get an article representing the latest 'news_date'
   *
   * @param bool $for_display Default true.
   *
   * @return mixed news_article object | null
   */
  public static function get_latest_article($for_display = TRUE)
  {
    $db = CmsApp::get_instance()->GetDb();
    $now = $db->DBTimeStamp(time());
    $query = 'SELECT mn.*, mnc.news_category_name FROM '.CMS_DB_PREFIX.'module_news mn
LEFT OUTER JOIN '.CMS_DB_PREFIX."module_news_categories mnc
ON mn.news_category_id = mnc.news_category_id
WHERE status = 'published' AND (start_time IS NULL OR start_time <= $now) AND (end_time IS NULL OR end_time > $now)
ORDER BY news_date DESC LIMIT 1";
    $row = $db->GetRow($query);

    return self::get_article_from_row($row,($for_display)?'PUBLIC':'ALL');
  }

  /**
   * Get an article representing the specified id
   *
   * @param type $article_id article numeric id
   * @param bool $for_display Default true
   * @param bool $allow_expired Default false
   * @return mixed news_article object | null
   */
  public static function get_article_by_id($article_id,$for_display = TRUE,$allow_expired = FALSE)
  {
    $db = CmsApp::Get_instance()->GetDb();
    $now = $db->DBTimeStamp(time());
    $query = 'SELECT mn.*, mnc.news_category_name FROM '.CMS_DB_PREFIX.'module_news mn
LEFT OUTER JOIN '.CMS_DB_PREFIX."module_news_categories mnc
ON mnc.news_category_id = mn.news_category_id
WHERE news_id = ? AND status = 'published'
AND (start_time IS NULL OR start_time <= $now)";
    if( !$allow_expired ) {
      $query .= " AND (end_time IS NULL OR end_time > $now)";
    }
    $row = $db->GetRow($query, array($article_id));

    if( !$row ) return null; // no object

    return self::get_article_from_row($row,($for_display)?'PUBLIC':'ALL');
  }

  /**
   * Cache field-data for the specified articles
   *
   * mixed $ids int | int[] news article id(s)
   */
  public static function preloadFieldData($ids)
  {
    $fielddefs = self::get_fielddefs();
    if( !$fielddefs ) return;

    if( !is_array($ids) && is_numeric($ids) ) {
      $ids = array($ids);
    }
    elseif( is_array($ids) ) {
      $ids = array_unique($ids);
      sort($ids);
    }
    else return;

    $idlist = array();
    for( $i = 0, $nn = count($ids); $i < $nn; $i++ ) {
      if( !is_numeric($ids[$i]) ) continue;
      $n = (int)$ids[$i];
      if( $n < 0 ) continue;
      if( is_array(self::$_cached_fieldvals) && isset(self::$_cached_fieldvals[$n]) ) continue;
      $idlist[] = $n;
    }
    if( !$idlist ) return;

    $db = CmsApp::get_instance()->GetDb();
    $query = 'SELECT V.news_id,V.fielddef_id,V.value FROM '.CMS_DB_PREFIX.'module_news_fieldvals V
INNER JOIN '.CMS_DB_PREFIX.'module_news_fielddefs D
ON V.fielddef_id = D.id
WHERE news_id IN ('.implode(',',$idlist).')
ORDER BY V.news_id,D.item_order';
    $dbr = $db->GetArray($query);
    foreach( $dbr as $row ) {
      $flddef_id = $row['fielddef_id'];
      foreach( $fielddefs as $field ) {
        if( $field['id'] == $flddef_id ) {
          $obj = new news_field();
          foreach( $field as $k => $v ) {
            $obj->$k = $v;
          }
          $obj->value = $row['value'];
          $news_id = $row['news_id'];
          if( !isset(self::$_cached_fieldvals[$news_id]) ) {
             self::$_cached_fieldvals[$news_id] = array();
          }
          self::$_cached_fieldvals[$news_id][$flddef_id] = $obj;
        }
      }
    }
  }

  /**
   * Get cached fields-data for the specified article
   *
   * @param int $news_id article numeric id
   * @param bool $public_only Default true UNUSED
   * @param bool $filled_only Default false UNUSED
   * @return array maybe empty or each member like fieldname => dataobject
   */
  public static function get_fields($news_id,$public_only = TRUE,$filled_only = FALSE)
  {
    if( $news_id <= 0 ) return [];
    $fd = self::get_fielddefs();
    if( !$fd ) return [];

    $results = array();
    foreach( $fd as $field ) {
      if( isset(self::$_cached_fieldvals[$news_id][$field['id']]) ) {
        $results[$field['name']] =  self::$_cached_fieldvals[$news_id][$field['id']]; // TODO self::furnish() where relevant
      }
    }
    return $results;
  }

  /**
   * Munge risky content of the supplied string.
   * Intended for application to relevant untrusted values prior to their
   * storage and/or display in a page.
   * Handles php-start tags, script tags, js executables, '`' chars which
   * would be a problem in pages, templates, but TODO some might be ok in
   * UDT content in a textarea element?
   * Entitized content is interpreted, but not so for (url-, rawurl-, base64-)
   * encoded content.
   * Does not deal with image-file content. Inline <svg/> will be handled anyway.
   * Does not deal with Smarty tags like {stuff}
   * @since 2.51.14
   * @see https://portswigger.net/web-security/cross-site-scripting/cheat-sheet
   * @see https://owasp.org/www-community/xss-filter-evasion-cheatsheet
   * @see http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php
   *
   * @param mixed $val input value, string (maybe empty) or null
   * @return string
   */
  public static function execSpecialize($val)
  {
    if( !$val ) return (string)$val;

    $flags = ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_XHTML; // OR ENT_HTML5 ?
    $tmp = html_entity_decode($val, $flags, 'UTF-8');
    if( $tmp === $val ) {
      $revert = false;
    }
    else {
      $revert = true;
      $val = $tmp;
    }
    // munge start-PHP tags (TODO might be insufficient change)
    $val = preg_replace(['/(<|%3c)(\?|%3f)php/i', '/(<|%3c)(\?|%3f)=/i', '/(<|%3c)(\?|%3f)(\s|\n)/i'], ['&#60;&#63;php', '&#60;&#63;=', '&#60;&#63; '], $val);
    //TODO maybe disable SmartyBC-supported {php}{/php}
    //$val = preg_replace('~\{/?php\}~i', '', $val); but with current smarty delim's
    $val = str_replace('`', '&#96;', $val);
    foreach( [
       // script tags like <script or <script> or <script X> X = e.g. 'defer'
      '/(<|%3c)\s*(scrip)t([^>]*)((>|%3f)?)/i' => function($matches) {
        return '&#60;'.$matches[2].'&#116;'.($matches[3] ? ' '.trim($matches[3]) : '').($matches[4] ? '&#62;' : '');
      },
      // explicit script
      '/jav(.+?)(scrip)t\s*:\s*(.+)?/i' => function($matches) {
        if( $matches[3] ) {
          return 'ja&#118;'.trim($matches[1]).$matches[2].'&#116;&#58;'.strtr($matches[3], ['(' => '&#40;', ')' => '&#41;']);
        }
        return $matches[0];
      },
      // inline scripts like on*="dostuff" or on*=dostuff (TODO others e.g. FSCommand(), seekSegmentTime() @ http://help.dottoro.com)
      // TODO invalidly processes non-event-related patterns like ontopofold='smoky'
      '/\b(on[\w.:\-]{4,})\s*=\s*(["\']?.+?["\']?)/i' => function($matches) {
        return $matches[1].'&#61;'.strtr($matches[2], ['"' => '&#34;', "'" => '&#39;', '(' => '&#40;', ')' => '&#41;']);
      },
      //callables like class::func
      '/([a-zA-Z0-9_\x80-\xff]+?)\s*?::\s*?([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*?)\s*?\(/' => function($matches) {
        return $matches[1] . '&#58;&#58;' . $matches[1] . '&#40;';
      },
      // embeds
      '/(embe)(d)/i' => function($matches) {
        return $matches[1].'&#'.ord($matches[2]).';';
      }
      ] as $regex => $replacer ) {
        $val = preg_replace_callback($regex, $replacer, $val);
      }

    if( $revert ) {
      // preserve valid content like <p>
      $tmp = strtr($val, '<>', "\2\3");
      $tmp2 = htmlentities($tmp, $flags, 'UTF-8', false);
      $val = strtr($tmp2, "\2\3", '<>');
    }
    return $val;
  }

  /**
   * Adjust $url to a consistent format for storage
   *
   * @param string $url
   * @return string
   */
  public static function storeformat_url($url)
  {
    if( startswith($url,CMS_ROOT_URL) ) { $ret = str_replace(CMS_ROOT_URL, '', $url); }
    elseif( startswith($url,'[ROOT_URL]') ) { $ret = str_replace('[ROOT_URL]', '', $url); }
    else { $ret = $url; }
    //any other processing goes here
    return $ret;
  }

  /**
   * Adjust $url (retrieved from storage) to a consistent usable format
   *
   * @param string $url '/'-prefixed if site-root-relative
   * @return string
   */
  public static function useformat_url($url)
  {
    if( $url ) {
      if( startswith($url,'//') ) { return $url; }
      if( $url[0] != '/' ) { return $url; }
      return CMS_ROOT_URL.$url;
    }
    return '';
  }

} // class

?>
