<?php
#CMSMS News module action: browsecat
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;

if (isset($params['browsecattemplate'])) {
    $template = trim($params['browsecattemplate']);
}
else {
    $tpl = CmsLayoutTemplate::load_dflt_by_type('News::browsecat');
    if( !is_object($tpl) ) {
        audit('',$this->GetName().':browsecat','No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}

$cache_id = '|ns'.md5(serialize($params));
$modname = $this->GetName();
$tpl = $smarty->createTemplate($this->GetTemplateResource($template),$cache_id,$modname,$smarty);
if( !$tpl->IsCached() ) {
    $items = news_ops::get_categories($id, $params, $returnid);
    if( $items ) {
        $tpl->assign('cats', $items);
        $tpl->assign('count', count($items));
    }
    else {
        $tpl->assign('cats', []);
        $tpl->assign('count', 0);
    }
}

$tpl->display();

?>
