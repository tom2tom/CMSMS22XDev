<?php

final class AdminSearch_content_slave extends AdminSearch_slave
{
    public function get_name()
    {
        $mod = cms_utils::get_module('AdminSearch');
        return $mod->Lang('lbl_content_search');
    }

    public function get_description()
    {
        $mod = cms_utils::get_module('AdminSearch');
        return $mod->Lang('desc_content_search');
    }

    public function check_permission()
    {
        $mod = cms_utils::get_module('CMSContentManager');
        return $mod->CanEditContent();
    }

    public function get_matches()
    {
        //key: db colums name, value: corresponding function name to get the value from the Content object
        $content_db_fields = array(
            'content_name' => [ 'function_name' => 'Name', 'translation' => CmsLangOperations::lang_from_realm('admin','name') ],
            'menu_text' => [ 'function_name' => 'MenuText', 'translation' => CmsLangOperations::lang_from_realm('admin','menutext') ],
            'content_alias' => [ 'function_name' => 'Alias', 'translation' => CmsLangOperations::lang_from_realm('admin','alias') ],
            'metadata' => [ 'function_name' => 'Metadata', 'translation' => CmsLangOperations::lang_from_realm('admin','metadata') ],
            'titleattribute' => [ 'function_name' => 'TitleAttribute', 'translation' => CmsLangOperations::lang_from_realm('admin','titleattribute') ],
            'page_url'  => [ 'function_name' => 'URL', 'translation' => CmsLangOperations::lang_from_realm('admin','page_url') ]
        );

        $userid = get_userid();
        $all = $this->include_inactive_items();

        $content_manager = cms_utils::get_module('CMSContentManager');
        $db = cmsms()->GetDb();

        //content table
        $query = 'SELECT DISTINCT content_id FROM '.CMS_DB_PREFIX.'content WHERE ';
        $where_clause = implode(' LIKE ? OR ', array_keys($content_db_fields));
        if( $all ) {
            $query .= $where_clause . ' LIKE ?'; //assumes content-field value can be matched regardless of case
        } else {
            $query .= ' active=1 AND (' . $where_clause . ' LIKE ?)';
        }

        $txt = '%'.$this->get_text().'%';

        $output = array();

        $resultSets = array();

//      $urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

        //check the table
        $this->process_query_string($query);

        $dbr = $db->GetCol($query, array_fill(0,count($content_db_fields),$txt));
        if( is_array($dbr) && count($dbr) ) {
            $pmod = check_permission($userid,'Manage All Content') || check_permission($userid,'Modify Any Page');
            $ops = cmsms()->GetContentOperations();
            foreach( $dbr as $content_id ) {
                if( !($pmod || $ops->CheckPageAuthorship($userid,$content_id)) ) {
                    // no access to this content page TODO why so? we're viewing, not editing
                    continue;
                }

                $content_obj = $ops->LoadContentFromId($content_id);
                if( !is_object($content_obj) ) continue;

                if (!$all && !$content_obj->Active()) continue; //TODO already filtered in query

                if (!isset($resultSets[$content_id])) {
                    $resultSets[$content_id] = $this->get_resultset($content_obj->Name(),$content_obj->Name(),$content_manager->create_url('m1_','admin_editcontent','',array('content_id'=>$content_id)));
                }

                // we're going to check several standard attributes of the content object
                foreach ($content_db_fields as $db_field => $value) {
                    $fname = $value['function_name'];
                    $content = $content_obj->$fname(); //get the attribute value (text)

                    $resultSets[$content_id]->count += $count = $this->get_number_of_occurrences($content);

                    if (!$this->show_snippets()) continue;
                    if( $count > 0 ) {
                        $snippets = $this->generate_snippets($content);
                        $resultSets[$content_id]->locations[$content_db_fields[$db_field]['translation']] = $snippets;
                    }
                }

            }
        }

        //content_props table
        if( $all ) {
            $query2 = 'SELECT DISTINCT content_id,prop_name,content FROM '.CMS_DB_PREFIX.'content_props WHERE content LIKE ?';
        } else {
            $query2 = 'SELECT DISTINCT P.content_id,P.prop_name,P.content FROM '.CMS_DB_PREFIX.'content_props P JOIN '.CMS_DB_PREFIX.'content C ON P.content_id=C.content_id WHERE C.active=1 AND P.content LIKE ?';
        }

        //check the table
        $this->process_query_string($query2);
        $dbr = $db->GetArray($query2, [$txt]);
        if( is_array($dbr) && count($dbr) ) {

            if( !isset($pmod) ) { $pmod = check_permission($userid,'Manage All Content') || check_permission($userid,'Modify Any Page'); }
            if( !isset($ops) ) { $ops = cmsms()->GetContentOperations(); }
            foreach( $dbr as $row ) {
                $content_id = $row['content_id'];
                if( !($pmod || !$ops->CheckPageAuthorship($userid,$content_id)) ) {
                    // no access to this content page TODO why so? we're viewing, not editing
                    continue;
                }

                $content_obj = $ops->LoadContentFromId($content_id);
                if( !is_object($content_obj) ) continue;
                //if( !$content_obj->HasSearchableContent() ) continue;

                if (!$all && !$content_obj->Active()) continue; //TODO already filtered in query

                if (!isset($resultSets[$content_id])) {
                    $resultSets[$content_id] = $this->get_resultset($content_obj->Name(),$content_obj->Name(),$content_manager->create_url('m1_','admin_editcontent','',array('content_id'=>$content_id)));
                }

                // here we could actually have a smarty template to build the description.
                $content = $row['content'];


                $resultSets[$content_id]->count += $count = $this->get_number_of_occurrences($content);

                if (!$this->show_snippets()) continue;
                if( $count > 0 ) {
                    $snippets = $this->generate_snippets($content);
                    if ($row['prop_name'] == 'content_en') {
                        $prop_name = CmsLangOperations::lang_from_realm('admin','content');
                    } elseif (CmsLangOperations::key_exists($row['prop_name'],'admin')) {
                      $prop_name = CmsLangOperations::lang_from_realm('admin',$row['prop_name']);
                    } else {
                      $prop_name = $row['prop_name'];
                    }

                    $resultSets[$content_id]->locations[$prop_name] = $snippets;
                }
            }
        }

        //process the results
        foreach ($resultSets as $cId => $result_object) {
            $output[] = json_encode($result_object);
        }

        return $output;
    }
} // end of class

?>
