<?php
#Module Search support functions
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

/**
 * @param Search $module
 * @param string $phrase
 * @return array
 */
function search_StemPhrase($module, $phrase)
{
    // strip out Smarty tags
    $phrase = preg_replace(['/{.*?}/', '/[\{\}]/'], [' ', ''], $phrase);

    // strip out html and PHP stuff
    $phrase = strip_tags($phrase);

    // insert spaces between html tags
    $phrase = str_replace(['<', '>'], [' <', '> '], $phrase);

    // escape meta characters
    $phrase = preg_quote($phrase);

    // strtolower isn't friendly to non-ASCII charsets
    $phrase = preg_replace_callback("/([A-Z]+?)/",
                                    function($matches) {
                                        return strtolower($matches[1]);
                                    },
                                    $phrase);

    // split into words
    $words = preg_split('/[\s,!.;:\?()+\-\/\\\\]+/u', $phrase);
    if( !$words || !is_array($words) ) {
        return [];
    }

    // ignore 1-digit numbers and non-numbers < 3 bytes
    $words = array_filter($words, function($a) {
        return ($l = strlen($a)) > 2 || ($l > 1 && is_numeric($a));
    });
    if( !$words ) {
        return [];
    }

    // ignore stop words
    $words = $module->RemoveStopWordsFromArray($words);
    if( !$words ) {
        return [];
    }

    $ret = array();
    $stemmer = null; // no object
    // will we stem words ?
    if( $module->GetPreference('usestemming', 0) ) {
        require_once __DIR__.DIRECTORY_SEPARATOR.'PorterStemmer.class.php';
        $stemmer = new PorterStemmer();
    }

    foreach( $words as $word ) {
        // get rid of whitespace and/or wrapping quotes
        $word = trim($word, " \t\n\r\0\x0B\"'");
        if( strlen($word) < 3 ) continue;

        if( $stemmer ) {
            $ret[] = $stemmer->stem($word);
        }
        else {
            $ret[] = $word;
        }
    }
    return $ret;
}

/**
 * @param $obj Search module?
 * @param string $module Default 'Search'
 * @param int $id Content id Default -1
 * @param string $attr Extra attr Default ''
 * @param string $content Default ''
 * @param mixed $expires timestamp | null Default null
 */
function search_AddWords($obj, $module = 'Search', $id = -1, $attr = '', $content = '', $expires = NULL)
{
    $obj->DeleteWords($module, $id, $attr);

    if( strpos($content, NON_INDEXABLE_CONTENT) !== FALSE ) return;

    CMSMS\HookManager::do_hook('Search::SearchItemAdded', [ $module, $id, $attr, &$content, $expires ]);

    if( $content ) {
        //Clean up the content
//      if( function_exists('utf8_decode') ) $content = utf8_decode($content);
        $content = html_entity_decode($content);

        $stemmed_words = $obj->StemPhrase($content); //not actually stemmed unless module preference set
        $tmp = array_count_values($stemmed_words);
        if( !$tmp ) {
            return;
        }
        $words = array();
        foreach( $tmp as $key => $val ) {
            $words[] = array('word'=>$key, 'count'=>$val);
        }

        $q = 'SELECT id FROM '.CMS_DB_PREFIX.'module_search_items WHERE module_name=?';
        $parms = array($module);

        if( $id != -1 ) {
            $q .= ' AND content_id=?';
            $parms[] = $id;
        }
        if( $attr != '' ) {
            $q .= ' AND extra_attr=?';
            $parms[] = $attr;
        }

        $db = CmsApp::get_instance()->GetDb();
        $dbresult = $db->Execute($q, $parms); //recordset

        $db->BeginTrans();
        if ($dbresult && $dbresult->RecordCount() > 0 && $row = $dbresult->FetchRow()) {
            $itemid = (int) $row['id'];
        }
        else {
            $itemid = (int) $db->GenID(CMS_DB_PREFIX.'module_search_items_seq');
            $db->Execute('INSERT INTO '.CMS_DB_PREFIX.'module_search_items (id,module_name,content_id,extra_attr,expires) VALUES (?,?,?,?,?)', array($itemid, $module, $id, $attr, ($expires != NULL ? trim($db->DBTimeStamp($expires), "'") : NULL) ));
        }

        $stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX."module_search_index (item_id,word,`count`) VALUES ($itemid,?,?)");
        $stmt->Bind($words);
        while( !$stmt->EOF() ) {
            $stmt->Execute();
            $stmt->MoveNext();
        }
        $db->CommitTrans();

        if( $dbresult ) {
            $dbresult->Close();
        }
    }
}

/**
 * @param Search|null $obj UNUSED
 * @param string $module Name Default 'Search'
 * @param int $id Content id Default -1
 * @param string $attr Extra attr Default ''
 */
function search_DeleteWords($obj, $module = 'Search', $id = -1, $attr = '')
{
    $parms = array( $module );
    $q = 'DELETE FROM '.CMS_DB_PREFIX.'module_search_items WHERE module_name=?';
    if( $id != -1 ) {
        $q .= ' AND content_id=?';
        $parms[] = $id;
    }
    if( $attr != '' ) {
        $q .= ' AND extra_attr=?';
        $parms[] = $attr;
    }
    $db = CmsApp::get_instance()->GetDb();
    $db->BeginTrans();
    $db->Execute($q, $parms);
    $db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_search_index WHERE item_id NOT IN (SELECT id FROM '.CMS_DB_PREFIX.'module_search_items)');
    $db->CommitTrans();
    CMSMS\HookManager::do_hook('Search::SearchItemDeleted', [ $module, $id, $attr ]);
}

/**
 * @param Search $module
 */
function search_Reindex($module)
{
    @set_time_limit(999);
    $module->DeleteAllWords();

    // have to load all the content, and properties, (in chunks)
    $full_list = array_keys( cmsms()->GetHierarchyManager()->getFlatList());
    $npages = count($full_list);
    $nperloop = min(200, $npages);
    $contentops = ContentOperations::get_instance();
    $offset = 0;

    while( $offset < $npages ) {
        // figure out the content to load.
        $idlist = array();
        for( $i = 0; $i < $nperloop && $offset+$i < $npages; $i++ ) {
            $idlist[] = $full_list[$offset+$i];
        }
        $offset += $i;
        $idlist = array_unique($idlist);

        // load the content for this tree.
        $contentops->LoadChildren(-1, TRUE, FALSE, $idlist);

        // index each content page.
        foreach( $idlist as $one ) {
            $content_obj = $contentops->LoadContentFromId($one);
            $parms = array('content'=>$content_obj);
            search_DoEvent($module, 'Core', 'ContentEditPost', $parms);
            cms_content_cache::unload($one);
        }
    }

    $modops = ModuleOperations::get_instance();
    /* TODO deprecate engagement with modules which do not declare SEARCH_MODULE capability
    $modules = $modops->get_modules_with_capability(CmsCoreCapabilities::SEARCH_MODULE); foreach( $modules as $obj ) {}
    */
    $modules = $modops->GetInstalledModules();
    foreach( $modules as $name ) {
        if( !$name || $name == 'Search' ) continue;
        $obj = $modops->get_module_instance($name);
        if( is_object($obj) && method_exists($obj, 'SearchReindex')) {
            $obj->SearchReindex($module);
        }
    }
}

/**
 * @param Search $module
 * @param string $originator
 * @param string $eventname
 * @param array $params
 */
function search_DoEvent($module, $originator, $eventname, &$params )
{
    if ($originator != 'Core') return;

    switch( $eventname ) {
    case 'ContentEditPost':
        if( empty($params['content']) ) return;
        $content = $params['content'];
        if( !is_object($content) ) return;

        search_DeleteWords(null, $module->GetName(), $content->Id(), 'content');
        if( $content->Active() && $content->IsSearchable() ) {

            $text = str_repeat(' '.$content->Name(), 2) . ' ' .
                    str_repeat(' '.$content->MenuText(), 2);

            $props = $content->Properties();
            if( $props && is_array($props) ) {
                $text .= ' ' . implode(' ', $props);
            }

            // check for content indicating page is not indexable
            if( strpos($text, NON_INDEXABLE_CONTENT) === FALSE ) {
                $text = trim(strip_tags($text));
                if( $text ) {
                    search_AddWords($module, $module->GetName(), $content->Id(), 'content', $text);
                }
            }
        }
        break;

    case 'ContentDeletePost':
        if( !empty($params['content']) ) {
            $content = $params['content'];
            if( is_object($content) ) {
                search_DeleteWords(null, $module->GetName(), $content->Id(), 'content');
            }
        }
        break;

    case 'ModuleUninstalled':
        $module_name = $params['name'];
        search_DeleteWords(null, $module_name);
        break;
    }
}

/**
 * @param string $text
 * @return string
 */
function search_CleanupText($text)
{
    $text = strip_tags($text);
    return $text;
}
