<?php
#CMSMS News module class: news_admin_ops
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

use CMSMS\HookManager;

final class news_admin_ops
{
    public static function delete_article($articleid)
    {
        HookManager::do_hook('News::NewsArticleDeletedPre', ['news_id'=>$articleid]);

        $db = cmsms()->GetDb();

        // Remove the article
        $query = "DELETE FROM ".CMS_DB_PREFIX."module_news WHERE news_id = ?";
        $db->Execute($query, array($articleid));

        // Delete any associated custom fields
        $query = 'DELETE FROM '.CMS_DB_PREFIX.'module_news_fieldvals WHERE news_id = ?';
        $db->Execute($query, array($articleid));

        // Delete any uploads for 'file' fields
        $config = cmsms()->GetConfig();
        $p = cms_join_path($config['uploads_path'], 'news', 'id'.$articleid);
        if (is_dir($p)) recursive_delete($p);

        news_admin_ops::delete_static_route($articleid);

        // Update search index
        $mod = cms_utils::get_module('News');
        $module = cms_utils::get_search_module();
        if ($module) $module->DeleteWords($mod->GetName(), $articleid, 'article');

        HookManager::do_hook('News::NewsArticleDeleted', ['news_id'=>$articleid]);

        // Put mention into the admin log
        audit($articleid, $mod->GetName().' article', "Deleted: $articleid");
    }

    public static function handle_upload($itemid, $fieldname, &$error)
    {
        $config = cmsms()->GetConfig();

        $mod = cms_utils::get_module('News');
        if ($_FILES[$fieldname]['size'] > $config['max_upload_size']) {
            $error = $mod->Lang('error_filesize');
            return FALSE;
        }

        $filename = basename($_FILES[$fieldname]['name']);

        // Get the file extension
        $xp = (int)strrpos($filename, '.');
        $ext = ($xp > 0) ? strtolower(substr($filename, $xp + 1)) : ''; // hidden files ignored

        // Compare it against allowed extensions
        $exts = explode(',', $mod->GetPreference('allowed_upload_types', ''));
        if ($exts && !in_array($ext, array_map('strtolower', $exts))) {
            $error = $mod->Lang('error_invalidfiletype');
            return FALSE;
        }

        $p = $config['uploads_path'].DIRECTORY_SEPARATOR.'news'.DIRECTORY_SEPARATOR.'id'.$itemid; //module uploads folder created on-demand
        if (!is_dir($p)) {
            if (!@mkdir($p, 0777, TRUE)) {
                $error = $mod->Lang('error_mkdir', $p);
                return FALSE;
            }
            touch($p.DIRECTORY_SEPARATOR.'index.html');
            $dir = $p;
            while ($dir != $config['uploads_path']) {
                $dir = dirname($dir);
                $fp = $dir.DIRECTORY_SEPARATOR.'index.html';
                if (is_file($fp)) {
                    break;
                }
                touch($fp);
            }
        }

        $dest = $p.DIRECTORY_SEPARATOR.$filename;
        if (!@cms_move_uploaded_file($_FILES[$fieldname]['tmp_name'], $dest)) {
            $error = $mod->Lang('error_movefile', $dest);
            return FALSE;
        }
        chmod($dest, 0644);

        return $filename; // its path will be handled in-context
    }

    public static function UpdateHierarchyPositions()
    {
        $db = cmsms()->GetDb();

        $query = "SELECT news_category_id, item_order, news_category_name FROM ".CMS_DB_PREFIX."module_news_categories";
        $dbresult = $db->Execute($query);
        if ($dbresult) {
            $query = "SELECT news_category_id, item_order, news_category_name, parent_id FROM ".CMS_DB_PREFIX."module_news_categories WHERE news_category_id = ?";
            $stmt = $db->Prepare("UPDATE ".CMS_DB_PREFIX."module_news_categories SET hierarchy = ?, long_name = ? WHERE news_category_id = ?");
            while ($row = $dbresult->FetchRow()) {
                $current_hierarchy_position = "";
                $current_long_name = "";
                $content_id = $row['news_category_id'];
                $current_parent_id = $row['news_category_id'];
                $count = 0;

                while ($current_parent_id > -1) {
                    $row2 = $db->GetRow($query, array($current_parent_id));
                    if ($row2) {
                        //3-wide hierarchy positions are more than enough
                        $current_hierarchy_position = str_pad((string)$row2['item_order'], 3, '0', STR_PAD_LEFT) . "." . $current_hierarchy_position;
                        $current_long_name = $row2['news_category_name'] . ' | ' . $current_long_name;
                        $current_parent_id = $row2['parent_id'];
                        $count++;
                    }
                    else {
                        $current_parent_id = 0;
                    }
                }

                if (($l = strlen($current_hierarchy_position)) > 0) {
                    $current_hierarchy_position = substr($current_hierarchy_position, 0, $l - 1);
                }

                if (($l = strlen($current_long_name)) > 0) {
                    $current_long_name = substr($current_long_name, 0, $l - 3);
                }

                $stmt->Execute(array($current_hierarchy_position, $current_long_name, $content_id));
            }
            $dbresult->Close();
        }
    }

    public static function delete_static_route($news_article_id)
    {
        return cms_route_manager::del_static('', 'News', $news_article_id);
    }

    public static function register_static_route($news_url, $news_article_id, $detailpage = 0)
    {
        if ($detailpage <= 0) {
            $module = cms_utils::get_module('News');
            $detailpage = $module->GetPreference('detail_returnid', -1);
            if ($detailpage == -1) {
                $gCms = cmsms();
                $detailpage = $gCms->GetContentOperations()->GetDefaultContent();
            }
        }
        $parms = array('action'=>'detail', 'returnid'=>$detailpage, 'articleid'=>$news_article_id);
        $route = CmsRoute::new_builder($news_url, 'News', $news_article_id, $parms, TRUE);
        return cms_route_manager::add_static($route);
    }

    public static function optionstext_to_array($txt)
    {
        $txt = trim($txt);
        if (!$txt) return [];

        $arr_options = array();
        $tmp1 = explode("\n", $txt);
        foreach ($tmp1 as $tmp2) {
            $tmp2 = trim($tmp2);
            if ($tmp2 == '') continue;
            $tmp2_k = $tmp2_v = $tmp2;
            if (strpos($tmp2, '=') !== FALSE) {
                list($tmp2_k, $tmp2_v) = explode('=', $tmp2, 2);
            }
            if ($tmp2_k == '' || $tmp2_v == '') continue;
            $arr_options[$tmp2_k] = $tmp2_v;
        }
        return $arr_options;
    }

    public static function array_to_optionstext($arr)
    {
        $txt = '';
        foreach ($arr as $key => $val) {
            $txt .= "$key=$val\n";
        }
        return trim($txt);
    }
} // end of class

#
# EOF
#
