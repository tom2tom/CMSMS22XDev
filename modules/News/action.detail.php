<?php
if (!isset($gCms)) exit;

//
// initialization
//
if (!empty($params['detailtemplate'])) {
    $template = trim($params['detailtemplate']);
}
else {
    $tpl = CmsLayoutTemplate::load_dflt_by_type('News::detail');
    if( !is_object($tpl) ) {
        audit('',$this->GetName().':detail','No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}

$article = null; // no object
$preview = FALSE;
$articleid = (isset($params['articleid'])) ? $params['articleid'] : -1;
$cache_id = 'nd'.md5(serialize($params));
$compile_id = 'nd'.$articleid;

if( $id == '_preview_' && isset($_SESSION['news_preview']) && isset($params['preview']) ) {
    // see if our data matches.
    if( md5(serialize($_SESSION['news_preview'])) == $params['preview'] ) {
        $fname = TMP_CACHE_LOCATION.'/'.$_SESSION['news_preview']['fname'];
        if( file_exists($fname) && (md5_file($fname) == $_SESSION['news_preview']['checksum']) ) {
            $data = unserialize(file_get_contents($fname));
            if( is_array($data) ) {
                // get passed data into a standard format.
                $article = new news_article();
                $article->set_linkdata($id,$params);
                news_ops::fill_article_from_formparams($article,$data,FALSE,FALSE);
                $compile_id = 'news_preview_'.time();
                $preview = TRUE;
            }
        }
    }
}

$tpl = $smarty->CreateTemplate($this->GetTemplateResource($template),$cache_id,$compile_id,$smarty);
if( $preview || !$tpl->IsCached() ) {
//$tpl = $smarty->CreateTemplate($this->GetTemplateResource($template),null,null,$smarty);
//if( $preview ) {
    if( isset($params['articleid']) && $params['articleid'] == -1 ) {
        $article = news_ops::get_latest_article();
    }
    else if( isset($params['articleid']) && (int)$params['articleid'] > 0 ) {
        $show_expired = $this->GetPreference('expired_viewable',1);
        if( !empty($params['showall']) ) $show_expired = 1;
        $article = news_ops::get_article_by_id((int)$params['articleid'],TRUE,$show_expired);
    }
    if( !$article ) {
        throw new CmsError404Exception('Article '.(int)$params['articleid'].' not found, or otherwise unavailable');
//      return; useless here
    }
    $article->set_linkdata($id,$params);

    if( !empty($params['origid']) ) {
        $returnid = (int)$params['origid'];
        //cache it for use in action.default (passing via url param N/A here)
        cms_utils::set_app_data('News::origid',$returnid);
    }

    $return_url = $this->CreateReturnLink($id, $returnid, $this->Lang('news_return'));
    $tpl->assign('return_url', $return_url);
    $tpl->assign('entry', $article);

    $catName = '';
    if (isset($params['category_id'])) {
        $catName = $db->GetOne('SELECT news_category_name FROM '.CMS_DB_PREFIX . 'module_news_categories where news_category_id=?',array((int)$params['category_id']));
    }
    $tpl->assign('category_name',$catName);
    unset($params['articleid']);
    $tpl->assign('category_link',$this->CreateLink($id, 'default', $returnid, $catName, $params));

    $tpl->assign('category_label', $this->Lang('category_label'));
    $tpl->assign('author_label', $this->Lang('author_label'));
    $tpl->assign('extra_label', $this->Lang('extra_label'));
}

$tpl->display();

?>
