<?php
# Module CMSContentManager tab populator
# (c) 2025 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

$all_attrs = [];
$content = new Content();
$list = $content->GetProperties();
if( $list && is_array($list) ) {
    for( $i = 0, $n = count($list); $i < $n; $i++ ) {
        $obj = $list[$i];
        if( $obj->tab == $content::TAB_PERMS ) continue; // ignore permission-related properties
        if( !isset($all_attrs[$obj->tab]) ) {
            $all_attrs[$obj->tab] = array('label'=>lang($obj->tab),'value'=>[]);
        }
        $all_attrs[$obj->tab]['value'][] = array('label'=>lang($obj->name),'value'=>$obj->name);
    }
}
$tpl->assign('all_attributes',$all_attrs);
$tpl->assign('all_contenttypes',ContentOperations::get_instance()->ListContentTypes(FALSE,FALSE));
$basic_attrs = cms_siteprefs::get('basic_attributes');
$tpl->assign('basic_attributes',explode(',',$basic_attrs));
$tpl->assign('content_autocreate_flaturls',(bool)cms_siteprefs::get('content_autocreate_flaturls',0));
$tpl->assign('content_autocreate_urls',(bool)cms_siteprefs::get('content_autocreate_urls',0));
$tpl->assign('content_cssnameisblockname',(bool)cms_siteprefs::get('content_cssnameisblockname',1));
$tpl->assign('content_imagefield_path',cms_siteprefs::get('content_imagefield_path'));
$tpl->assign('content_mandatory_urls',(bool)cms_siteprefs::get('content_mandatory_urls',0));
$tpl->assign('content_thumbnailfield_path',cms_siteprefs::get('content_thumbnailfield_path'));
$tpl->assign('contentimage_path',cms_siteprefs::get('contentimage_path'));
$disallowed_contenttypes = cms_siteprefs::get('disallowed_contenttypes');
$tpl->assign('disallowed_contenttypes',explode(',',$disallowed_contenttypes));
$tpl->assign('pretty_urls',$config['url_rewriting'] != 'none');
