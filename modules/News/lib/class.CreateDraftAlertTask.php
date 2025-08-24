<?php
#CMSMS News module class: CreateDraftAlertTask
#(c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

namespace News;

class CreateDraftAlertTask implements \CmsRegularTask
{
    public function get_name()
    {
        return basename(get_class($this));
    }

    public function get_description()
    {
        return $this->get_name();
    }

    public function test($time = '')
    {
        if( !$time ) $time = time();
        $mod = \cms_utils::get_module('News');
        $lastrun = (int) $mod->GetPreference('task1_lastrun');
        if( $lastrun >= ($time - 900) ) return FALSE; // hardcoded to 15 minutes
        return TRUE;
    }

    public function on_success($time = '')
    {
        IF( !$time ) $time = time();
        $mod = \cms_utils::get_module('News');
        $mod->SetPreference('task1_lastrun',$time);
    }

    public function on_failure($time = '') {}

    public function execute($time = '')
    {
        $db = \CmsApp::get_instance()->GetDb();
        if( !$time ) $time = time();

        $query = 'SELECT count(news_id) FROM '.CMS_DB_PREFIX.'module_news n WHERE status != \'published\'
                  AND (end_time IS NULL OR end_time > NOW())';
        $count = $db->GetOne($query);
        if( !$count ) return TRUE;

        $alert = new DraftMessageAlert($count);
        $alert->save();
        return TRUE;
    }
}
