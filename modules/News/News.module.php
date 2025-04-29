<?php
#CMS Made Simple module: News
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
//if( !isset($gCms) ) exit;

class News extends CMSModule
{
    public function AllowSmartyCaching() { return TRUE; }
    public function GetAdminDescription() { return $this->Lang('description'); }
    public function GetAdminSection() { return 'content'; }
    public function GetAuthor() { return 'Ted Kulp'; }
    public function GetAuthorEmail() { return 'wishy@cmsmadesimple.org'; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetEventDescription($eventname) { return $this->Lang('eventdesc-' . $eventname); }
    public function GetEventHelp($eventname) { return $this->Lang('eventhelp-' . $eventname); }
    public function GetFriendlyName() { return $this->Lang('news'); }
    public function GetName() { return 'News'; }
    public function GetVersion() { return '2.51.15'; }
    public function HasAdmin() { return TRUE; }
    public function InstallPostMessage() { return $this->Lang('postinstall');  }
    public function IsPluginModule() { return TRUE; }
    public function LazyLoadAdmin() { return TRUE; }
    public function LazyLoadFrontend() { return TRUE; }
    public function MinimumCMSVersion() { return '2.2.16'; } //for localedate_format modifier

    public function GetHelp()
    {
        //TODO default values per respective News preferences
        $this->CreateParameter('action', 'default', $this->Lang('helpaction'));
        $this->CreateParameter('articleid', '', $this->Lang('help_articleid'));
        $this->CreateParameter('browsecat', 0, $this->Lang('helpbrowsecat'));
        $this->CreateParameter('browsecattemplate', '', $this->Lang('helpbrowsecattemplate'));
        $this->CreateParameter('category', '', $this->Lang('helpcategory'));
        $this->CreateParameter('detailpage', '', $this->Lang('helpdetailpage'));
        $this->CreateParameter('detailtemplate', '', $this->Lang('helpdetailtemplate'));
        $this->CreateParameter('formtemplate', '', $this->Lang('helpformtemplate'));
        $this->CreateParameter('idlist', '', $this->Lang('help_idlist'));
        $this->CreateParameter('moretext', 'more...', $this->Lang('helpmoretext')); // TODO More...
        $this->CreateParameter('number', 1000, $this->Lang('helpnumber')); //TODO 100?
        $this->CreateParameter('pagelimit', 1000, $this->Lang('help_pagelimit')); //TODO match 'number'
        $this->CreateParameter('showall', 0, $this->Lang('helpshowall'));
        $this->CreateParameter('showarchive', 0, $this->Lang('helpshowarchive'));
        $this->CreateParameter('sortasc', 'true', $this->Lang('helpsortasc')); //TODO allow bool
        $this->CreateParameter('sortby', 'news_date', $this->Lang('helpsortby'));
        $this->CreateParameter('start', 0, $this->Lang('helpstart'));
        $this->CreateParameter('summarytemplate', '', $this->Lang('helpsummarytemplate'));
/*
$val = $this->GetPreference('alert_drafts',1);
$val = $this->GetPreference('allcategories',1);
$val = $this->GetPreference('allow_fesubmit',1);
$val = $this->GetPreference('allow_summary_wysiwyg',1);
$val = $this->GetPreference('allowed_upload_types','bmp,jpg,jpeg,gif,png,svg,webp,ico');
$val = $this->GetPreference('article_category','');
$val = $this->GetPreference('article_pagelimit',10);
$val = $this->GetPreference('article_sortby','news_date');
$val = $this->GetPreference('auto_create_thumbnails','gif,jpg,jpeg,png');
$val = $this->GetPreference('clear_category',0);
$val = $this->GetPreference('current_detail_template','');
$val = $this->GetPreference('date_format','%e %B %Y %l:%M %p');
$val = $this->GetPreference('default_category',1);
$val = $this->GetPreference('detail_returnid',-1);
$val = $this->GetPreference('email_subject',$val = $this->Lang('subject_newnews'));
$val = $this->GetPreference('email_subject',$val = $this->Lang('subject_newnews'));
$val = $this->GetPreference('email_template','Article Approval-Request Email');
$val = $this->GetPreference('email_to','');
$val = $this->GetPreference('expired_searchable',1);
$val = $this->GetPreference('expired_viewable',0);
$val = $this->GetPreference('expiry_interval',30);
$val = $this->GetPreference('fesubmit_redirect',0);
$val = $this->GetPreference('fesubmit_status',0);
$val = $this->GetPreference('formsubmit_emailaddress','');
$val = $this->GetPreference('hide_summary_field',0);
ALL $params USED IN FRONTEND ACTIONS - BUT POSSIBLY OTHERS IN FRONTEND-FORM SUBMISSION ?
$this->CreateParameter('action', 'default', $this->Lang('helpaction'));
$this->CreateParameter('approve', '', $this->Lang('helpapprove')qqq_);
$this->CreateParameter('articleid', '', $this->Lang('helparticleid')qqq_);
$this->CreateParameter('browsecat', '', $this->Lang('helpbrowsecat')qqq_);
$this->CreateParameter('browsecattemplate', '', $this->Lang('helpbrowsecattemplate')qqq_);
$this->CreateParameter('category_id', '', $this->Lang('helpcategory_id')qqq_);
$this->CreateParameter('category', '', $this->Lang('helpcategory')qqq_);
$this->CreateParameter('content', '', $this->Lang('helpcontent')qqq_);
$this->CreateParameter('detailpage', '', $this->Lang('helpdetailpage')qqq_);
$this->CreateParameter('detailtemplate', '', $this->Lang('helpdetailtemplate')qqq_);
$this->CreateParameter('enddate_Day', '', $this->Lang('helpenddate_Day')qqq_);
$this->CreateParameter('enddate_Hour', '', $this->Lang('helpenddate_Hour')qqq_);
$this->CreateParameter('enddate_Minute', '', $this->Lang('helpenddate_Minute')qqq_);
$this->CreateParameter('enddate_Month', '', $this->Lang('helpenddate_Month')qqq_);
$this->CreateParameter('enddate_Second', '', $this->Lang('helpenddate_Second')qqq_);
$this->CreateParameter('enddate_Year', '', $this->Lang('helpenddate_Year')qqq_);
$this->CreateParameter('extra', '', $this->Lang('helpextra')qqq_);
$this->CreateParameter('formtemplate', '', $this->Lang('helpformtemplate')qqq_);
$this->CreateParameter('idlist', '', $this->Lang('helpidlist')qqq_);
$this->CreateParameter('input_category', '', $this->Lang('helpinput_category')qqq_);
$this->CreateParameter('lang', '', $this->Lang('helplang')qqq_);
$this->CreateParameter('moretext', '', $this->Lang('helpmoretext')qqq_);
$this->CreateParameter('news_customfield_'.$onefield['id', '', $this->Lang('help')qqq_);
$this->CreateParameter('number', '', $this->Lang('helpnumber')qqq_);
$this->CreateParameter('origid', '', $this->Lang('helporigid')qqq_);
$this->CreateParameter('pagelimit', '', $this->Lang('helppagelimit')qqq_);
$this->CreateParameter('pagenumber', '', $this->Lang('helppagenumber')qqq_);
$this->CreateParameter('preview', '', $this->Lang('helppreview')qqq_);
$this->CreateParameter('showall', '', $this->Lang('helpshowall')qqq_);
$this->CreateParameter('showarchive', '', $this->Lang('helpshowarchive')qqq_);
$this->CreateParameter('sortasc', '', $this->Lang('helpsortasc')qqq_);
$this->CreateParameter('sortby', '', $this->Lang('helpsortby')qqq_);
$this->CreateParameter('start', '', $this->Lang('helpstart')qqq_);
$this->CreateParameter('startdate_Day', '', $this->Lang('helpstartdate_Day')qqq_);
$this->CreateParameter('startdate_Hour', '', $this->Lang('helpstartdate_Hour')qqq_);
$this->CreateParameter('startdate_Minute', '', $this->Lang('helpstartdate_Minute')qqq_);
$this->CreateParameter('startdate_Month', '', $this->Lang('helpstartdate_Month')qqq_);
$this->CreateParameter('startdate_Second', '', $this->Lang('helpstartdate_Second')qqq_);
$this->CreateParameter('startdate_Year', '', $this->Lang('helpstartdate_Year')qqq_);
$this->CreateParameter('submit', '', $this->Lang('helpsubmit')qqq_);
$this->CreateParameter('summary', '', $this->Lang('helpsummary')qqq_);
$this->CreateParameter('summarytemplate', '', $this->Lang('helpsummarytemplate')qqq_);
$this->CreateParameter('title', '', $this->Lang('helptitle')qqq_);
*/
        $out = $this->Lang('help');
        $out .= file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'modhelp-extra.htm');
        return $out;
    }

    public function InitializeFrontend()
    {
        $this->RestrictUnknownParams();

        $this->SetParameterType('articleid',CLEAN_INT);
        $this->SetParameterType('assign',CLEAN_STRING);
        $this->SetParameterType('browsecat',CLEAN_INT);
        $this->SetParameterType('browsecattemplate',CLEAN_STRING);
        $this->SetParameterType('category',CLEAN_STRING);
        $this->SetParameterType('category_id',CLEAN_STRING); //TODO INT?
        $this->SetParameterType('detailpage',CLEAN_STRING);
        $this->SetParameterType('detailtemplate',CLEAN_STRING);
        $this->SetParameterType('formtemplate',CLEAN_STRING);
        $this->SetParameterType('idlist',CLEAN_STRING);
        $this->SetParameterType('inline',CLEAN_STRING);
        $this->SetParameterType('moretext',CLEAN_STRING);
        $this->SetParameterType('number',CLEAN_INT);
        $this->SetParameterType('origid',CLEAN_INT);
        $this->SetParameterType('pagelimit',CLEAN_INT);
        $this->SetParameterType('pagenumber',CLEAN_INT);
        $this->SetParameterType('preview',CLEAN_STRING);
        $this->SetParameterType('showall',CLEAN_INT);
        $this->SetParameterType('showarchive',CLEAN_INT);
        $this->SetParameterType('showtemplate',CLEAN_STRING);
        $this->SetParameterType('sortasc',CLEAN_STRING); // should be int, or boolean
        $this->SetParameterType('sortby',CLEAN_STRING);
        $this->SetParameterType('start',CLEAN_INT);
        $this->SetParameterType('summarytemplate',CLEAN_STRING);

        // form parameters
        $this->SetParameterType('cancel',CLEAN_STRING);
        $this->SetParameterType('category',CLEAN_STRING);
        $this->SetParameterType('category_id',CLEAN_INT); //TODO STRING above
        $this->SetParameterType('content',CLEAN_STRING);
        $this->SetParameterType('enddate',CLEAN_STRING);
        $this->SetParameterType('enddate_Day',CLEAN_STRING);
        $this->SetParameterType('enddate_Hour',CLEAN_STRING);
        $this->SetParameterType('enddate_Minute',CLEAN_STRING);
        $this->SetParameterType('enddate_Month',CLEAN_STRING);
        $this->SetParameterType('enddate_Second',CLEAN_STRING);
        $this->SetParameterType('enddate_Year',CLEAN_STRING);
        $this->SetParameterType('extra',CLEAN_STRING);
        $this->SetParameterType('input_category',CLEAN_STRING);
        $this->SetParameterType('postdate',CLEAN_STRING);
        $this->SetParameterType('postdate_Day',CLEAN_STRING);
        $this->SetParameterType('postdate_Hour',CLEAN_STRING);
        $this->SetParameterType('postdate_Minute',CLEAN_STRING);
        $this->SetParameterType('postdate_Month',CLEAN_STRING);
        $this->SetParameterType('postdate_Second',CLEAN_STRING);
        $this->SetParameterType('postdate_Year',CLEAN_STRING);
        $this->SetParameterType('startdate',CLEAN_STRING);
        $this->SetParameterType('startdate_Day',CLEAN_STRING);
        $this->SetParameterType('startdate_Hour',CLEAN_STRING);
        $this->SetParameterType('startdate_Minute',CLEAN_STRING);
        $this->SetParameterType('startdate_Month',CLEAN_STRING);
        $this->SetParameterType('startdate_Second',CLEAN_STRING);
        $this->SetParameterType('startdate_Year',CLEAN_STRING);
        $this->SetParameterType('submit',CLEAN_STRING);
        $this->SetParameterType('summary',CLEAN_STRING);
        $this->SetParameterType('title',CLEAN_STRING);
        $this->SetParameterType('useexp',CLEAN_INT);

        $this->SetParameterType(CLEAN_REGEXP.'/news_customfield_.*/',CLEAN_STRING);
        $this->SetParameterType('junk',CLEAN_STRING);
    }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Modify News') ||
            $this->CheckPermission('Modify Site Preferences') ||
            $this->CheckPermission('Approve News');
    }

    public function GetDfltEmailTemplate()
    {
        return <<<'EOS'
A new News article has been posted to your website. The details are as follows:
Title:      {$title}
Summary:    {$summary|strip_tags}
Start Date: {$startdate|localedate_format}
End Date:   {$enddate|localedate_format}
IP Address: {$ipaddress}

EOS;
    }

    public function SearchResultWithParams($returnid, $articleid, $attr = '', $params = '')
    {
        $gCms = CmsApp::get_instance();
        $result = array();

        if ($attr == 'article') {
            $db = $this->GetDb();
            $q = "SELECT news_title,news_url FROM ".CMS_DB_PREFIX."module_news WHERE news_id = ?";
            $row = $db->GetRow( $q, array( $articleid ) );

            if ($row) {
                //0 position is the prefix displayed in the list results.
                $result[0] = $this->GetFriendlyName();

                //1 position is the title
                $result[1] = $row['news_title'];

                //2 position is the URL to the title.
                $detailpage = $returnid;
                if( isset($params['detailpage']) ) {
                    $manager = $gCms->GetHierarchyManager();
                    $node = $manager->sureGetNodeByAlias($params['detailpage']);
                    if (isset($node)) {
                        $detailpage = $node->getID();
                    }
                    else {
                        $node = $manager->sureGetNodeById($params['detailpage']);
                        if (isset($node)) $detailpage = $params['detailpage'];
                    }
                }
                if( $detailpage == '' ) $detailpage = $returnid;

                $detailtemplate = '';
                if( isset($params['detailtemplate']) ) {
                    $manager = $gCms->GetHierarchyManager();
                    $node = $manager->sureGetNodeByAlias($params['detailtemplate']);
                    if (isset($node)) $detailtemplate = '/d,' . $params['detailtemplate'];
                }

                $prettyurl = $row['news_url'];
                if( $prettyurl == '' ) {
                    $aliased_title = munge_string_to_url($row['news_title']);
                    $prettyurl = 'news/' . $articleid . "/$detailpage/$aliased_title" . $detailtemplate;
                }

                $parms = array();
                $parms['articleid'] = $articleid;
                if( !empty($params['detailtemplate']) ) $parms['detailtemplate'] = $params['detailtemplate'];
                $result[2] = $this->create_url('cntnt01', 'detail', $detailpage, $parms, FALSE, TRUE, $prettyurl); //!inline, targetcontentonly
            }
        }

        return $result;
    }

    public function SearchReindex($module)
    {
        $db = $this->GetDb();

        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news WHERE searchable = 1 AND status = ? ORDER BY news_date';
        $result = $db->Execute($query,array('published'));
        if( $result ) {
            $src = $this->GetName();
            $exp = $this->GetPreference('expired_searchable',0);
            while (!$result->EOF) {
                if ($result->fields['status'] == 'published') {
                    $module->AddWords($src,
                                      $result->fields['news_id'], 'article',
                                      $result->fields['news_data'] . ' ' . $result->fields['summary'] . ' ' . $result->fields['news_title'] . ' ' . $result->fields['news_title'],
                                      ($result->fields['end_time'] != NULL && $exp == 0) ? $db->UnixTimeStamp($result->fields['end_time']) : NULL); //null for no datetime field value
                }
                $result->MoveNext();
            }
            $result->Close();
        }
    }

    public function GetFieldTypes()
    {
        $items = [ 'textbox'=>$this->Lang('textbox'),
                   'checkbox'=>$this->Lang('checkbox'),
                   'textarea'=>$this->Lang('textarea'),
                   'dropdown'=>$this->Lang('dropdown'),
                   'linkedfile'=>$this->Lang('linkedfile'),
                   'file'=>$this->Lang('file') ];
        return $items;
    }

    public function GetTypesDropdown( $id, $name, $selected = '' )
    {
        $items = $this->GetFieldTypes();
        return $this->CreateInputDropdown($id, $name, array_flip($items), -1, $selected);
    }

    public function get_tasks()
    {
        if( !$this->GetPreference('alert_drafts',0) ) return [];
        $out = array();
        $out[] = new \News\CreateDraftAlertTask();
        return $out;
    }

    public function GetNotificationOutput($priority = 2)
    {
        // if this user has permission to change News articles from
        // draft to published, and there are draft news articles,
        // then display a nice message.
        // this is a priority 2 item.
        if( $priority >= 2 ) {
            $output = array();
            if( $this->CheckPermission('Approve News') ) {
                $db = $this->GetDb();
                $query = 'SELECT COUNT(news_id) FROM '.CMS_DB_PREFIX.'module_news n WHERE status != \'published\'
                  AND (end_time IS NULL OR end_time > NOw())';
                $count = $db->GetOne($query);
                if( $count ) {
                    $obj = new stdClass();
                    $obj->priority = 2;
                    $link = $this->CreateLink('m1_','defaultadmin','', $this->Lang('notify_n_draft_items_sub',$count));
                    $obj->html = $this->Lang('notify_n_draft_items',$link);
                    $output[] = $obj;
                }
            }
        }
        return $output;
    }

    public function CreateStaticRoutes()
    {
        cms_route_manager::del_static('',$this->GetName());

        $db = \CmsApp::get_instance()->GetDb();
        $str = $this->GetName();
        $c = strtoupper($str[0]);
        $x = substr($str,1);
        $x1 = '['.$c.strtolower($c).']'.$x;

        $route = new CmsRoute('/'.$x1.'\/(?P<articleid>[0-9]+)\/(?P<returnid>[0-9]+)\/(?P<junk>.*?)\/d,(?P<detailtemplate>.*?)$/',
                              $this->GetName());
        cms_route_manager::add_static($route);
        $route = new CmsRoute('/'.$x1.'\/(?P<articleid>[0-9]+)\/(?P<returnid>[0-9]+)\/(?P<junk>.*?)$/',$this->GetName());
        cms_route_manager::add_static($route);
        $route = new CmsRoute('/'.$x1.'\/(?P<articleid>[0-9]+)\/(?P<returnid>[0-9]+)$/',$this->GetName());
        cms_route_manager::add_static($route);
        $route = new CmsRoute('/'.$x1.'\/(?P<articleid>[0-9]+)$/',$this->GetName(),
                              array('returnid'=>$this->GetPreference('detail_returnid',-1)));
        cms_route_manager::add_static($route);

        $query = 'SELECT news_id,news_url FROM '.CMS_DB_PREFIX.'module_news WHERE status = ? AND news_url != ? AND '
            . '('.$db->ifNull('start_time',$db->DbTimeStamp(1)).' < NOW()) AND '
            . '(('.$db->IfNull('end_time',$db->DbTimeStamp(1)).' = '.$db->DbTimeStamp(1).') OR (end_time > NOW()))';
        $query .= ' ORDER BY news_date DESC';
        $tmp = $db->GetArray($query,array('published',''));

        if( is_array($tmp) ) {
            foreach( $tmp as $one ) {
                news_admin_ops::register_static_route($one['news_url'],$one['news_id']);
            }
        }
    }

    public static function page_type_lang_callback($str)
    {
        $mod = cms_utils::get_module('News');
        if( is_object($mod) ) return $mod->Lang('type_'.$str);
        return '';
    }

    public static function template_help_callback($str)
    {
        $mod = cms_utils::get_module('News');
        if( is_object($mod) ) {
            $str = trim((string)$str);
            $file = $mod->GetModulePath().'/doc/tpltype_'.$str.'.inc';
            if( is_file($file) ) return file_get_contents($file);
        }
        return '';
    }

    public static function reset_page_type_defaults(CmsLayoutTemplateType $type)
    {
        if( $type->get_originator() != 'News' ) throw new CmsLogicException('Cannot reset contents for this template type');

        $fn = '';
        switch( $type->get_name() ) {
        case 'summary':
            $fn = 'orig_summary_template.tpl';
            break;

        case 'detail':
            $fn = 'orig_detail_template.tpl';
            break;

        case 'form':
            $fn = 'orig_form_template.tpl';
            break;

        case 'browsecat':
            $fn = 'browsecat.tpl';
        }

        if( $fn ) {
            $fn = cms_join_path(__DIR__,'templates',$fn);
            if( file_exists($fn) ) return @file_get_contents($fn);
        }
        return '';
    }

    public function HasCapability($capability, $params = array())
    {
        switch( $capability ) {
        case CmsCoreCapabilities::PLUGIN_MODULE:
        case CmsCoreCapabilities::SEARCH_MODULE:
        case CmsCoreCapabilities::ADMINSEARCH:
        case CmsCoreCapabilities::TASKS:
            return TRUE;
        }
        return FALSE;
    }

    public function get_adminsearch_slaves()
    {
        return array('News_AdminSearch_slave');
    }

    public function GetAdminMenuItems()
    {
        $out = array();
        if( $this->VisibleToAdminUser() ) $out[] = CmsAdminMenuItem::from_module($this);

        if( $this->CheckPermission('Modify Site Preferences') ) {
            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'siteadmin';
            $obj->title = $this->Lang('title_news_settings');
            $obj->description = $this->Lang('desc_news_settings');
            $obj->action = 'admin_settings';
            $out[] = $obj;
        }
        return $out;
    }
} // end of class
