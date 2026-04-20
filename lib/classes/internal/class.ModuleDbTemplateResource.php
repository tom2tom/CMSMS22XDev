<?php
#CMS Made Simple class ModuleDbTemplateResource
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

namespace CMSMS\internal;

/**
 * A simple class to handle a module database template.
 *
 * @ignore
 * @internal
 * @since 1.11
 * @package CMS
 */
class ModuleDbTemplateResource extends Fixed_Resource_Custom
{
    protected function fetch($name,&$source,&$mtime)
    {
        \debug_buffer('','CMSModuleDbTemplateResource start'.$name);
        $db = \CmsApp::get_instance()->GetDb();

        $tmp = explode(';',$name);
        $query = "SELECT * from ".\CMS_DB_PREFIX."module_templates WHERE module_name = ? and template_name = ?";
        $parts = explode(';',$name);
        $row = $db->GetRow($query, $parts);
        if ($row) {
            $source = $row['content'];
            $mtime = $db->UnixTimeStamp($row['modified_date']);
        }
        else {
            // fallback to the layout stuff.
            try {
                $obj = \CmsLayoutTemplate::load($parts[1]);
                $source = $obj->get_content();
                $mtime = $obj->get_modified();
            }
            catch( Exception $e ) {
                // nothing here.
            }
        }
        \debug_buffer('','CMSModuleDbTemplateResource end'.$name);
    }
}
class_alias(ModuleDbTemplateResource::class,'CMSModuleDbTemplateResource',false); //deprecated
