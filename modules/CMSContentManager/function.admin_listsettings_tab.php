<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module CMSContentManager tab populator
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#-------------------------------------------------------------------------
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
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE

$opts = array('title'=>$this->Lang('prompt_page_title'),
          'menutext'=>$this->Lang('prompt_page_menutext'));
$tpl->assign('namecolumnopts',$opts);
$tpl->assign('list_namecolumn',$this->GetPreference('list_namecolumn','title'));

$dflts = 'expand,icon1,hier,page,alias,url,template,friendlyname,owner,active,default,move,view,copy,edit,delete,multiselect';
$tmp = explode(',',$dflts);
$opts = array();
foreach( $tmp as $one ) {
  $opts[$one] = $this->Lang('colhdr_'.$one);
}
$tpl->assign('visible_column_opts',$opts);
$tmp = explode(',',$this->GetPreference('list_visiblecolumns',$dflts));
$tpl->assign('list_visiblecolumns',$tmp);
