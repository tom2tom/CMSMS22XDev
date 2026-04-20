<?php
#CMSMS News module class: DraftMessageAlert
#(c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

namespace News;

class DraftMessageAlert extends \CMSMS\AdminAlerts\TranslatableAlert
{
    public function __construct($count)
    {
        parent::__construct([ 'Approve News' ]);
        $this->name = __CLASS__;
        $this->priority = self::PRIORITY_LOW;
        $this->titlekey = 'title_draft_entries';
        $this->module = 'News';
        $this->msgkey = 'notify_n_draft_items';
        $this->msgargs = $count;
    }
}
