<?php
#CMS Made Simple module: Search
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

define( 'NON_INDEXABLE_CONTENT', '<!-- pageAttribute: NotSearchable -->' );

class Search extends CMSModule
{
    private $_tools_loaded = false;

    private function load_tools()
    {
        require_once __DIR__.DIRECTORY_SEPARATOR.'search.tools.php';
        $this->_tools_loaded = true;
    }

    public function LazyLoadFrontend() { return true; }
    public function LazyLoadAdmin() { return true; }
    public function GetName() { return 'Search'; }
    public function GetFriendlyName() { return $this->Lang('search'); }
    public function IsPluginModule() { return true; }
    public function HasAdmin() { return true; }
    public function HandlesEvents () { return true; }
    public function GetVersion() { return '1.55'; }
    public function MinimumCMSVersion() { return '2.0'; }
    public function GetAdminDescription() { return $this->Lang('description'); }
    public function VisibleToAdminUser() { return $this->CheckPermission('Manage Search'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetAuthor() { return 'Ted Kulp'; }
    public function GetAuthorEmail() { return 'ted@cmsmadesimple.org'; }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetEventDescription( $eventname ) { return $this->lang('eventdesc-' . $eventname); }
    public function GetEventHelp( $eventname ) { return $this->lang('eventhelp-' . $eventname); }

    public function InitializeAdmin()
    {
        $this->CreateParameter('inline','false',$this->Lang('param_inline'));
        $this->CreateParameter('passthru_*','null',$this->Lang('param_passthru'));
        $this->CreateParameter('modules','null',$this->Lang('param_modules'));
        $this->CreateParameter('resultpage', 'null', $this->Lang('param_resultpage'));
        $this->CreateParameter('searchtext','null',$this->Lang('param_searchtext'));
        $this->CreateParameter('detailpage','null',$this->Lang('param_detailpage'));
        $this->CreateParameter('submit',$this->Lang('searchsubmit'),$this->Lang('param_submit'));
        $this->CreateParameter('action','default',$this->Lang('param_action'));
        $this->CreateParameter('pageid','null',$this->Lang('param_pageid'));
        $this->CreateParameter('count','null',$this->Lang('param_count'));
        $this->CreateParameter('use_or','true',$this->Lang('param_useor'));
        $this->CreateParameter('search_method','get',$this->Lang('search_method'));
        $this->CreateParameter('formtemplate','',$this->Lang('param_formtemplate'));
        $this->CreateParameter('resulttemplate','',$this->Lang('param_resulttemplate'));
    }

    public function InitializeFrontend()
    {
        $this->RestrictUnknownParams();

        $this->SetParameterType('inline',CLEAN_STRING);
        $this->SetParameterType(CLEAN_REGEXP.'/passthru_.*/',CLEAN_STRING);
        $this->SetParameterType('modules',CLEAN_STRING);
        $this->SetParameterType('resultpage',CLEAN_STRING);
        $this->SetParameterType('detailpage',CLEAN_STRING);
        $this->SetParameterType('searchtext',CLEAN_STRING);
        $this->SetParameterType('searchinput',CLEAN_STRING);
        $this->SetParameterType('submit',CLEAN_STRING);
        $this->SetParameterType('origreturnid',CLEAN_INT);
        $this->SetParameterType('pageid',CLEAN_INT);
        $this->SetParameterType('count',CLEAN_INT);
        $this->SetParameterType('use_or',CLEAN_INT);
        $this->SetParameterType('search_method',CLEAN_STRING);
        $this->SetParameterType('formtemplate',CLEAN_STRING);
        $this->SetParameterType('resulttemplate',CLEAN_STRING);
    }
    //@return string
    protected function GetSearchHtmlTemplate()
    {
        return <<<'EOT'
{$startform}
{if !empty($hidden)} {$hidden}{/if}
 <label for="{$search_actionid}searchinput">{$searchprompt}:</label>&nbsp;
 <input type="text" class="search-input" id="{$search_actionid}searchinput" name="{$search_actionid}searchinput" size="20" maxlength="50" placeholder="{$searchtext}">
{* <br>
 <input type="checkbox" name="{$search_actionid}use_or" value="1">
*}
 <input type="submit" class="search-button" name="{$search_actionid}submit" data-ui-icon="ui-icon-search" value="{$submittext}">
{$endform}
EOT;
    }
    //@return string
    protected function GetResultsHtmlTemplate()
    {
        return <<<'EOT'
<h3>{$searchresultsfor} &quot;{$phrase}&quot;</h3>
{if $itemcount > 0}
<ul>
{foreach $results as $entry}
  <li>{$entry->title} - <a href="{$entry->url}">{$entry->urltxt}</a> ({$entry->weight}%)</li>
{* You can also implement custom behaviour on a module-by-module basis by
   processing the ->module and ->modulerecord properties of $entry e.g.
   {if $entry->module == 'News'}{News action='detail' article_id=$entry->modulerecord detailpage='News'}{/if}
   For content pages the module is 'content' and modulerecord the page id.
*}
{/foreach}
</ul>
<p>{$timetaken}: {$timetook}</p>
{else}
<p><strong>{$noresultsfound}</strong></p>
{/if}
EOT;
    }
    //@return string
    protected function DefaultStopWords()
    {
        return $this->Lang('default_stopwords');
    }
    //@return array
    public function RemoveStopWordsFromArray($words)
    {
        if( !is_array($words) ) return [];
        $stop_words = $this->GetPreference('stopwords');
        if( !$stop_words ) $stop_words = $this->DefaultStopWords();
        if( !$stop_words ) return $words;
        $stop_words = preg_split("/[\s,]+/", $stop_words);
        return array_diff($words, $stop_words);
    }
    //@return array, maybe empty
    public function StemPhrase($phrase)
    {
        if( !$this->_tools_loaded ) $this->load_tools();
        return search_StemPhrase($this,$phrase);
    }

    public function AddWords($module = 'Search', $id = -1, $attr = '', $content = '', $expires = NULL) // mixed timestamp or null
    {
        if( !$this->_tools_loaded ) $this->load_tools();
        search_AddWords($this,$module,$id,$attr,$content,$expires);
    }

    public function DeleteWords($module = 'Search', $id = -1, $attr = '')
    {
        if( !$this->_tools_loaded ) $this->load_tools();
        search_DeleteWords($this,$module,$id,$attr);
    }

    public function DeleteAllWords($module = 'Search', $id = -1, $attr = '')
    {
        $db = CmsApp::get_instance()->GetDb();
        //minimise race-risk here
        $q1 = 'TRUNCATE '.CMS_DB_PREFIX.'module_search_index';
        $q2 = 'TRUNCATE '.CMS_DB_PREFIX.'module_search_items';
        $q3 = 'UPDATE '.CMS_DB_PREFIX.'module_search_items_seq SET id=0';
        $db->Execute($q1);
        $db->Execute($q2);
        $db->Execute($q3);
        CMSMS\HookManager::do_hook('Search::SearchAllItemsDeleted');
    }

    public function RegisterEvents()
    {
        $this->AddEventHandler('Core', 'ContentEditPost', false);
        $this->AddEventHandler('Core', 'ContentDeletePost', false);
        $this->AddEventHandler('Core', 'AddTemplatePost', false);
        $this->AddEventHandler('Core', 'EditTemplatePost', false);
        $this->AddEventHandler('Core', 'DeleteTemplatePost', false);
        $this->AddEventHandler('Core', 'ModuleUninstalled', false);
    }

    public function Reindex()
    {
        if( !$this->_tools_loaded ) $this->load_tools();
        search_Reindex($this);
    }

    public function DoEvent($originator,$eventname,&$params)
    {
        if( !$this->_tools_loaded ) $this->load_tools();
        search_DoEvent($this, $originator, $eventname, $params);
    }

    public function HasCapability($capability,$params = array())
    {
        switch( $capability ) {
        case CmsCoreCapabilities::SEARCH_MODULE:
        case CmsCoreCapabilities::PLUGIN_MODULE:
            return true;
        }
        return false;
    }

    public static function page_type_lang_callback($str)
    {
        $mod = cms_utils::get_module('Search');
        if( is_object($mod) ) return $mod->Lang('type_'.$str);
        return '';
    }

    public static function reset_page_type_defaults(CmsLayoutTemplateType $type)
    {
        if( $type->get_originator() != 'Search' ) {
            throw new CmsLogicException('Cannot reset contents for this template type');
        }
        $mod = cms_utils::get_module('Search');
        if( !is_object($mod) ) return '';
        switch( $type->get_name() ) {
        case 'searchform':
            return $mod->GetSearchHtmlTemplate();
        case 'searchresults':
            return $mod->GetResultsHtmlTemplate();
        }
        return '';
    }
}

?>
