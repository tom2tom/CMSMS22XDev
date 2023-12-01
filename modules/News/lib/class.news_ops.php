<?php
#Module News class news_ops
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

final class news_ops
{
protected function __construct() {}

private static $_categories_loaded;
private static $_cached_categories;
private static $_cached_fielddefs;
private static $_cached_fieldvals;

public static function get_categories($id,$params,$returnid=-1)
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

    $cat_count = isset($catinfo) ? count($catinfo) : '';
    if( !$cat_count ) return [];

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
    $now = $db->DbTimeStamp(time());

    {
        $q2 = 'SELECT news_category_id,COUNT(news_id) AS cnt FROM '.CMS_DB_PREFIX.'module_news WHERE news_category_id IN (';
        $q2 .= implode(',',$cat_ids).')';
        if (isset($params['showarchive']) && $params['showarchive'] == true) {
            $q2 .= " AND (end_time < ".$db->DBTimeStamp(time()).") ";
        }
        else {
            $q2 .= " AND (".$db->IfNull('start_time',$db->DBTimeStamp(1))." < $now) ";
            $q2 .= " AND ((".$db->IfNull('end_time',$db->DBTimeStamp(1))." = ".$db->DBTimeStamp(1).") OR (end_time > $now)) ";
        }
        $q2 .= ' AND status = \'published\' GROUP BY news_category_id';
        $tmp = $db->GetArray($q2);
        if( count($tmp) ) {
            for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
                $counts[$tmp[$i]['news_category_id']] = $tmp[$i]['cnt'];
            }
        }
    }

    $rowcounter=0;
    $items = array();
    $depth = 1;
    for( $i = 0, $n = count($catinfo); $i < $n; $i++ ) {
        $row =& $catinfo[$i];
        $row['index'] = $rowcounter++;
        $row['count'] = (isset($counts[$row['news_category_id']]))?$counts[$row['news_category_id']]:0;
        $row['prevdepth'] = $depth;
        $depth = count(explode('.', $row['hierarchy']));
        $row['depth']=$depth;

        // changes so that parameters supplied to the tag
        // gets carried down through the links
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

public static function get_all_categories()
{
    if( !self::$_categories_loaded ) {
        $db = CmsApp::get_instance()->GetDb();
        $query = "SELECT * FROM ".CMS_DB_PREFIX."module_news_categories ORDER BY hierarchy";
        $dbresult = $db->GetArray($query);
        if( $dbresult ) { self::$_cached_categories = $dbresult; }
        else { self::$_cached_categories = []; }
        self::$_categories_loaded = TRUE;
    }
    return self::$_cached_categories;
}

public static function get_category_list()
{
    self::get_all_categories();
    $categorylist = array();
    if (self::$_cached_categories)
    {
        for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
            $row = self::$_cached_categories[$i];
            $categorylist[$row['long_name']] = $row['news_category_id'];
        }
    }
    return $categorylist;
}

public static function get_category_names_by_id()
{
    self::get_all_categories();
    if (!empty(self::$_cached_categories))
    {
        $list = array();
        for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
            $list[self::$_cached_categories[$i]['news_category_id']] = self::$_cached_categories[$i]['news_category_name'];
        }
    }
    return $list;
}

public static function get_category_name_from_id($id)
{
    self::get_all_categories();
    if (!empty(self::$_cached_categories))
    {
        for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
            if( $id == self::$_cached_categories[$i]['news_category_id'] ) {
                return self::$_cached_categories[$i]['news_category_name'];
            }
        }
    }
    return '';
}

public static function get_fielddefs($publiconly = TRUE)
{
    if( !is_array(self::$_cached_fielddefs) ) {
        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE public = 1 ORDER BY item_order';
        if( !$publiconly ) {
            $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs ORDER BY item_order';
        }
        $tmp = $db->GetArray($query);

        self::$_cached_fielddefs = array();
        if( is_array($tmp) && count($tmp) ) {
            for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
                self::$_cached_fielddefs[$tmp[$i]['id']] = $tmp[$i];
            }
        }
    }
    return self::$_cached_fielddefs;
}

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
        case 'max_length':
        case 'item_order':
        case 'public':
        case 'extra':
        case 'value':
            $res->$key = $value;
            break;
        }
    }
    return $res;
}

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
        case 'news_url':
        case 'useexp':
        case 'extra':
            $news->$key = $value;
            break;

        case 'category':
            $list = self::get_category_names_by_id();
            for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
                if( $value == self::$_cached_categories[$i]['news_category_name'] )
                    $news->category_id = self::$_cached_categories[$i]['news_category_id'];
            }
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
        foreach( $params['customfield'] as $key => $value ) {
            if( !isset($fielddefs[$key]) ) continue;

            $field = self::get_field_from_row($fielddefs[$key]);
            $field->value = $value;
            $news->set_field($field);
        }
    }

    return $news;
}

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

        case 'news_title':
            $article->title = $value; // TODO rigorously sanitize potentially untrusted content
            break;

        case 'news_data':
            $article->content = $value; // TODO rigorously sanitize potentially untrusted content
            break;

        case 'news_date':
            $article->postdate = $value;
            break;

        case 'summary':
            $article->summary = $value; // TODO rigorously sanitize potentially untrusted content

        case 'start_time':
            $article->startdate = $value;
            break;

        case 'end_time':
            $article->enddate = $value;
            break;

        case 'status':
            $article->status = $value;
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
            $article->extra = $value; // TODO rigorously sanitize potentially untrusted content
            break;

        case 'news_url':
            $article->news_url = $value;
            break;
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

public static function get_latest_article($for_display = TRUE)
{
    $db = CmsApp::get_instance()->GetDb();
    $now = $db->DbTimeStamp(time());
    $query = "SELECT mn.*, mnc.news_category_name FROM ".CMS_DB_PREFIX."module_news mn LEFT OUTER JOIN ".CMS_DB_PREFIX."module_news_categories mnc ON mnc.news_category_id = mn.news_category_id WHERE status = 'published' AND ";
    $query .= "(".$db->IfNull('start_time',$db->DBTimeStamp(1))." < $now) AND ";
    $query .= "((".$db->IfNull('end_time',$db->DBTimeStamp(1))." = ".$db->DBTimeStamp(1).") OR (end_time > $now)) ";
    $query .= 'ORDER BY news_date DESC LIMIT 1';
    $row = $db->GetRow($query);

    return self::get_article_from_row($row,($for_display)?'PUBLIC':'ALL');
}

public static function get_article_by_id($article_id,$for_display = TRUE,$allow_expired = FALSE)
{
    $db = CmsApp::Get_instance()->GetDb();
    $query = 'SELECT mn.*, mnc.news_category_name FROM '.CMS_DB_PREFIX.'module_news mn
LEFT OUTER JOIN '.CMS_DB_PREFIX.'module_news_categories mnc ON mnc.news_category_id = mn.news_category_id
WHERE status = \'published\' AND news_id = ?
AND ('.$db->ifNull('start_time',$db->DbTimeStamp(1)).' < NOW())';
    if( !$allow_expired ) {
        $query .= 'AND (('.$db->ifNull('end_time',$db->DbTimeStamp(1)).' = '.$db->DbTimeStamp(1).') OR (end_time > NOW()))';
    }
    $row = $db->GetRow($query, array($article_id));

    if( !$row ) return null; // no object

    return self::get_article_from_row($row,($for_display)?'PUBLIC':'ALL');
}

public static function preloadFieldData($ids)
{
    if( !is_array($ids) && is_numeric($ids) ) $ids = array($ids);

    $tmp = array();
    for( $i = 0, $nn = count($ids); $i < $nn; $i++ ) {
        $n = (int)$ids[$i];
        if( $n < 0 ) continue;
        if( is_array(self::$_cached_fieldvals) && isset(self::$_cached_fieldvals[$n]) ) continue;
        $tmp[] = $n;
    }
    if( !count($tmp) ) return;
    sort($tmp);
    $idlist = array_unique($tmp);

    $fielddefs = self::get_fielddefs();
    if( !count($fielddefs) ) return;

    $db = CmsApp::get_instance()->GetDb();
    $query = 'SELECT A.news_id,A.fielddef_id,A.value FROM '.CMS_DB_PREFIX.'module_news_fieldvals A
              INNER JOIN '.CMS_DB_PREFIX.'module_news_fielddefs B
              ON A.fielddef_id = B.id
              WHERE news_id IN ('.implode(',',$idlist).')
              ORDER BY A.news_id,B.item_order';
    $dbr = $db->GetArray($query);
    if( !$dbr ) return;

    // initialization.
    if( !is_array(self::$_cached_fieldvals) ) self::$_cached_fieldvals = array();
    foreach( $idlist as $news_id ) {
        if( isset(self::$_cached_fieldvals[$news_id]) ) continue;

        self::$_cached_fieldvals[$news_id] = array();
        foreach( $fielddefs as $field ) {
            $obj = new news_field();
            foreach( $field as $k => $v ) {
                $obj->$k = $v;
            }
            $obj->value = null; // mixed unset value
            self::$_cached_fieldvals[$news_id][$field['id']] = $obj;
        }
    }

    // fill with values.
    foreach( $dbr as $row ) {
        $news_id = $row['news_id'];
        $flddef_id = $row['fielddef_id'];
        $value = $row['value'];

        if( !isset(self::$_cached_fieldvals[$news_id][$flddef_id]) ) continue;
        self::$_cached_fieldvals[$news_id][$flddef_id]->value = $value;
    }
}

public static function get_fields($news_id,$public_only = true,$filled_only = FALSE)
{
    if( $news_id <= 0 ) return [];
    $fd = self::get_fielddefs();
    if( !count($fd) ) return [];

    $results = array();
    foreach( $fd as $field ) {
        if( isset(self::$_cached_fieldvals[$news_id][$field['id']]) ) {
            $obj = self::$_cached_fieldvals[$news_id][$field['id']];
        }
        else {
            // data for this field must not have been preloaded.
            // means there is no value, so just build one
            $obj = new news_field();
            foreach( $field as $k => $v ) {
                $obj->$k = $v;
            }
            $obj->value = null; // mixed unset value
        }
        $results[$field['name']] = $obj;
    }
    /*
    foreach( self::$_cached_fieldvals[$news_id] as $fid => $data ) {
        if( !$public_only || $data->public ) {
            if( !$filled_only || (isset($data->value) && $data->value != '') ) {
                $results[$data->name] = $data;
            }
        }
    }
    */
    return $results;
}

/**
 * Munge risky content of the supplied string.
 * Intended for application to relevant untrusted values prior to their display in a page.
 * Handles php-start tags, script tags, js executables, '`' chars which would
 * be a problem in pages, templates, but TODO some might be ok in UDT content
 * in a textarea element?
 * Entitized content is interpreted, but not (url-, rawurl-, base64-) encoded content.
 * Does not deal with image-file content. Inline <svg/> will be handled anyway.
 * @internal
 * @since 2.2.18
 * @see https://portswigger.net/web-security/cross-site-scripting/cheat-sheet
 * @see https://owasp.org/www-community/xss-filter-evasion-cheatsheet
 * @see http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php
 *
 * @param string $val input value, may be empty or null
 * @return string
 */
public static function execSpecialize($val)
{
    if (!$val) return (string)$val;

    $flags = ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_XHTML; // OR ENT_HTML5 ?
    $tmp = html_entity_decode($val, $flags, 'UTF-8');
    if ($tmp === $val) {
        $revert = false;
    } else {
        $revert = true;
        $val = $tmp;
    }
    // munge start-PHP tags
    $val = preg_replace(['/<\s*\?\s*php/i', '/<\s*\?\s*=/', '/<\s*\?(\s|\n)/'], ['&#60;&#63;php', '&#60;&#63;=', '&#60;&#63; '], $val);
    //TODO maybe disable SmartyBC-supported {php}{/php}
    //$val = preg_replace('~\{/?php\}~i', '', $val); but with current smarty delim's
    $val = str_replace('`', '&#96;', $val);
    foreach ([
         // script tags like <script or <script> or <script X> X = e.g. 'defer'
        '/<\s*(scrip)t([^>]*)(>?)/i' => function($matches) {
            return '&#60;'.$matches[1].'&#116;'.($matches[2] ? ' '.trim($matches[2]) : '').($matches[3] ? '&#62;' : '');
        },
        // explicit script
        '/jav(.+?)(scrip)t\s*:\s*(.+)?/i' => function($matches) {
            if ($matches[3]) {
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
        ] as $regex => $replacer) {
            $val = preg_replace_callback($regex, $replacer, $val);
        }

    if ($revert) {
        // preserve valid content like <p>
        $tmp = strtr($val, '<>', "\2\3");
        $tmp2 = htmlentities($tmp, $flags, 'UTF-8', false);
        $val = strtr($tmp2, "\2\3", '<>');
    }
    return $val;
}

} // end of class

#
# EOF
#
?>
