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

$tpl->assign('locktimeout',$this->GetPreference('locktimeout'));
$tpl->assign('lockrefresh',$this->GetPreference('lockrefresh'));

$tmp = [];
$orders = $this->ListPreferencesByPrefix('order_TAB_');
foreach( $orders as $key ) {
  $nm = $this->GetPreference('name_TAB_'.$key);
  if( $nm ) {
    $flag = false; // not a default tab
  } else {
    $nm = $key; //TODO translated names have lang keys like '??_*_tab__'
    $flag = true;
  }
  $tmp[$key] = [(int)$this->GetPreference('order_TAB_'.$key),ucfirst(strtolower($nm)),$flag]; //TODO UTF8 reformat name
}
uasort($tmp,function($a,$b) {
  $res = Collator::compare($a,$b);
  return ($res !== false) ? $res : 0;
});
$tpl->assign('tab_orders',$tmp);

$opts = array(
  'all'=>$this->Lang('opt_alltemplates'),
  'alldesign'=>$this->Lang('opt_alldesign'),
  'allpage'=>$this->Lang('opt_allpage'),
  'designpage'=>$this->Lang('opt_designpage')
);
$tpl->assign('template_list_opts',$opts);
$tpl->assign('template_list_mode',$this->GetPreference('template_list_mode','designpage'));
