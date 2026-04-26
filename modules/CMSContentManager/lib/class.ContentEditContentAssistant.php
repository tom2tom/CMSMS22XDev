<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module CMSContentManager class ContentEditContentAssistant
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#-------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: https://www.gnu.org/licenses/licenses.html#GPL
#-------------------------------------------------------------------------
#END_LICENSE

class ContentEditContentAssistant extends EditContentAssistant
{
  public function getExtraCode()
  {
    // get javascript for editcontent for the Content object, and its derived objects.
//  $mod = cms_utils::get_module('CMSContentManager');
//  $modname = $mod->GetName();
    $modname = 'CMSContentManager';
    $smarty = cmsms()->GetSmarty();
    $tpl = $smarty->createTemplate("module_file_tpl:$modname;content_editcontent_extra.tpl",null,$modname); //no parent
    return $tpl->fetch();
  }
}
?>
