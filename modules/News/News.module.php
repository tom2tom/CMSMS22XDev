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

use News\CreateDraftAlertJob;

class News extends CMSModule
{
    public function AllowSmartyCaching() { return TRUE; }
    public function GetAdminDescription() { return $this->Lang('description'); }
    public function GetAdminSection() { return 'content'; }
    public function GetAuthor() { return 'Ted Kulp'; }
    public function GetAuthorEmail() { return 'wishy@cmsmadesimple.org'; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetDependencies() { return ['FilePicker'=>'1.1']; }
    public function GetEventDescription($eventname) { return $this->Lang('eventdesc-' . $eventname); }
    public function GetEventHelp($eventname) { return $this->Lang('eventhelp-' . $eventname); }
    public function GetFriendlyName() { return $this->Lang('news'); }
    public function GetName() { return 'News'; }
    public function GetVersion() { return '2.51.17'; }
    public function HasAdmin() { return TRUE; }
    public function InstallPostMessage() { return $this->Lang('postinstall');  }
    public function IsPluginModule() { return TRUE; }
    public function LazyLoadAdmin() { return TRUE; }
    public function LazyLoadFrontend() { return TRUE; } // OR false to handle an intra-module news_image tag?
    public function MinimumCMSVersion() { return '2.2.23F2'; } //for Jobs processing

    public function GetHelp()
    {
        $this->CreateParameter('action', 'default', $this->Lang('helpaction')); // always?
        $this->CreateParameter('articleid', '', $this->Lang('help_articleid'));
        $this->CreateParameter('browsecat', 0, $this->Lang('helpbrowsecat'));
        $this->CreateParameter('browsecattemplate', '', $this->Lang('helpbrowsecattemplate'));
        $this->CreateParameter('category', '', $this->Lang('helpcategory'));
        $this->CreateParameter('detailpage', '', $this->Lang('helpdetailpage'));
        $this->CreateParameter('detailtemplate', '', $this->Lang('helpdetailtemplate'));
        $this->CreateParameter('formtemplate', '', $this->Lang('helpformtemplate'));
        $this->CreateParameter('idlist', '', $this->Lang('help_idlist'));
        $this->CreateParameter('moretext', $this->Lang('more'), $this->Lang('helpmoretext'));
//      $val = (int)$this->GetPreference('article_pagelimit', 10);
        $this->CreateParameter('number', 0, $this->Lang('helpnumber'));
        $this->CreateParameter('pagelimit', 0, $this->Lang('help_pagelimit'));
        $this->CreateParameter('showall', 0, $this->Lang('helpshowall'));
        $this->CreateParameter('showarchive', 0, $this->Lang('helpshowarchive'));
        $this->CreateParameter('sortasc', 'true', $this->Lang('helpsortasc'));
        $this->CreateParameter('sortby', 'news_date', $this->Lang('helpsortby'));
        $this->CreateParameter('start', 0, $this->Lang('helpstart'));
        $this->CreateParameter('summarytemplate', '', $this->Lang('helpsummarytemplate'));
        //TODO all the ones Set in InitializeFrontend()

        $out = $this->Lang('help');
        $out .= file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'modhelp-extra.htm');
        return $out;
    }

    public function InitializeFrontend()
    {
//      $this->RestrictUnknownParams(); does nothing
        $this->SetParameterType('articleid',CLEAN_INT);
        $this->SetParameterType('assign',CLEAN_STRING);
        $this->SetParameterType('browsecat',CLEAN_INT);
        $this->SetParameterType('browsecattemplate',CLEAN_STRING);
        $this->SetParameterType('category',CLEAN_STRING);
        $this->SetParameterType('category_id',CLEAN_INT);
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
        $this->SetParameterType('sortasc',CLEAN_STRING); // should be int or bool
        $this->SetParameterType('sortby',CLEAN_STRING);
        $this->SetParameterType('start',CLEAN_INT);
        $this->SetParameterType('summarytemplate',CLEAN_STRING);

        // form parameters
        $this->SetParameterType('cancel',CLEAN_STRING);
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

        $this->SetParameterType(CLEAN_REGEXP.'/news_currentfile_\d+/',CLEAN_STRING);
        $this->SetParameterType(CLEAN_REGEXP.'/news_customfield_\d+/',CLEAN_STRING);
        $this->SetParameterType(CLEAN_REGEXP.'/news_wantedfield_\d+/',CLEAN_STRING);
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
        $result = array();

        if ($attr == 'article') {
            $gCms = CmsApp::get_instance();
            $db = $gCms->GetDb();
            $q = "SELECT news_title,news_url FROM ".CMS_DB_PREFIX."module_news WHERE news_id = ?";
            $row = $db->GetRow($q, array($articleid));

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
                    $prettyurl = "news/$articleid/$detailpage/$aliased_title{$detailtemplate}";
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
        $db = CmsApp::get_instance()->GetDb();
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
                   'linkedfile'=>$this->Lang('linkedfile'), //file specified by entered url or selected from uploads tree using the FilePicker, no type restriction, similar to content image property
                   'file'=>$this->Lang('file') ]; // selected and uploaded file, no type-restriction
        return $items;
    }

    public function GetTypesDropdown( $id, $name, $selected = '' )
    {
        $items = $this->GetFieldTypes();
        return $this->CreateInputDropdown($id, $name, array_flip($items), -1, $selected);
    }

    public function get_tasks() // TODO GetTasks($aspaths)
    {
        if( !$this->GetPreference('alert_drafts',1) ) return [];
        $fp = cms_join_path(__DIR__,'lib','class.CreateDraftAlertJob.php');
        if( is_file($fp) ) {
            if( func_num_args() > 0 && func_get_arg(0) ) { // want filepath(s)
                return [$fp];
            } else {
                require_once $fp;
                return [new CreateDraftAlertJob()];
            }
        }
        return [];
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
                $db = CmsApp::get_instance()->GetDb();
                $query = 'SELECT COUNT(news_id) FROM '.CMS_DB_PREFIX.'module_news WHERE status != \'published\'
                  AND (end_time IS NULL OR end_time > NOW())';
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

        $db = CmsApp::get_instance()->GetDb();
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

        $query = 'SELECT news_id,news_url FROM '.CMS_DB_PREFIX.'module_news WHERE status = \'published\'' .
            ' AND news_url IS NOT NULL AND news_url != \'\'' .
            ' AND (start_time IS NULL OR start_time <= NOW()) AND (end_time IS NULL OR end_time > NOW())' .
            ' ORDER BY news_date DESC';
        $tmp = $db->GetArray($query);

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
