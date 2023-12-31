<?php
#CMS - CMS Made Simple
#(c)2004 by Ted Kulp (wishy@users.sf.net)
#Visit our homepage at: http://www.cmsmadesimple.org
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
#$Id: listtags.php 2772 2006-05-17 02:25:27Z wishy $

$CMS_ADMIN_PAGE=1;
$CMS_LOAD_ALL_PLUGINS=1;

require_once("../lib/include.php");
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();
$userid = get_userid();
$access = check_permission($userid, "Modify Events");

if (!$access) {
	die('Permission Denied');
	return;
}


// here we'll handle setting $action based on _POST['action']
$action = '';
$module = '';
$event = '';
$modulefilter = '';
if( isset( $_GET['action'] ) && $_GET['action'] != '' ) $action = $_GET['action'];
if( isset( $_GET['module'] ) && $_GET['module'] != '' ) $module = $_GET['module'];
if( isset( $_GET['event'] ) && $_GET['event'] != '' ) $event = $_GET['event'];
if( isset( $_GET['modulefilter'] ) && $_GET['modulefilter'] != '' ) $modulefilter = $_GET['modulefilter'];

// display the page
include_once("header.php");

$editImg = $themeObject->DisplayImage('icons/system/edit.gif', lang('edit'),'','','systemicon');
$infoImg = $themeObject->DisplayImage('icons/system/info.gif', lang('help'),'','','systemicon');

echo '<div class="pagecontainer">';
echo '<div class="pageoverflow">';
echo $themeObject->ShowHeader('eventhandlers');

switch( $action ) {
	case 'showeventhelp':
	{
		$desctext = '';
		$text = '';
		if ($module == 'Core') {
			$desctext = Events::GetEventDescription($event);
			$text = Events::GetEventHelp($event);
		}
		else {
		    $moduleobj = cms_utils::get_module($module);
		    if( is_object($moduleobj) ) {
				$desctext = $moduleobj->GetEventDescription($event);
				$text = $moduleobj->GetEventHelp($event);
		    }
		}

		echo "<h3>$event</h3>";
		if( $desctext != "" ) echo "<p><b>" . lang('description') . "</b>: " . $desctext . "</p>";
		if( $text == "" ) {
			echo "No helptext available...";
		}
		else {
			echo $text;
		}

		echo "<h4>".lang('eventhandler')."</h4>";
		$hlist = Events::ListEventHandlers( $module, $event );
		if ($hlist === false) {
			echo '<p>'.lang('none').'</p>';
        }
		else {
			echo '<ul>';
			foreach ($hlist as $te) {
					echo '<li>'.$te['handler_order'].'. ';
					if (!empty($te['tag_name'])) {
						echo lang('user_tag').': '.$te['tag_name'];
					}
					else if (!empty($te['module_name'])) {
						echo lang('module').': '.$te['module_name'];
					}
					echo '</li>';
				}
			echo '</ul>';
        }
		break;
	}

	default:
	{
		$events = Events::ListEvents();

		echo '<br /><form action="eventhandlers.php" method="get">';
		echo '<div><input type="hidden" name="'.CMS_SECURE_PARAM_NAME.'" value="'.$_SESSION[CMS_USER_KEY].'" /></div>';

		echo lang('filterbymodule').': <select name="modulefilter">' . "\n";
		echo '<option value="">'.lang('showall').'</option>';
		$modlist = array();
		if( is_array($events) )	{
			foreach( $events as $oneevent )	{
				if (!in_array($oneevent['originator'], $modlist)) $modlist[] = $oneevent['originator'];
			}
		}
		if (count($modlist) > 0) {
			foreach($modlist as $onemod) {
				echo '<option value="'.$onemod.'"';
				if ($onemod == $modulefilter) echo ' selected="selected"';
				echo '>'.$onemod.'</option>';
			}
		}
		echo "</select> <input type=\"submit\" value=\"".lang('submit')."\" /></form>\n\n";

		echo "<table class=\"pagetable\">\n";
		echo "<thead>\n";
		echo "  <tr>\n";
		echo "    <th title=\"".lang('title_event_originator')."\">".lang('originator')."</th>\n";
		echo "    <th title=\"".lang('title_event_name')."\">".lang('event')."</th>\n";
		echo "    <th title=\"".lang('title_event_handlers')."\">".lang('eventhandler')."</th>\n";
		echo "    <th title=\"".lang('title_event_description')."\" width='50%'>".lang('description')."</th>\n";
		echo "    <th class=\"pageicon\">&nbsp;</th>\n";
		echo "    <th class=\"pageicon\">&nbsp;</th>\n";
		echo "  </tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";

		if( is_array($events) )
		{
			$curclass = 'row1';
			foreach( $events as $oneevent )
			{
				if ($modulefilter == '' || $modulefilter == $oneevent['originator'])
				{
					echo "<tr class=\"".$curclass."\">\n";

					$desctext = '';
					if ($oneevent['originator'] == 'Core') {
						$desctext = Events::GetEventDescription($oneevent['event_name']);
						echo "    <td>".lang('core')."</td>\n";
					}
					else if ( ($objinstance = cms_utils::get_module($oneevent['originator'])) ) {
					  $desctext = $objinstance->GetEventDescription($oneevent['event_name']);
					  echo "    <td>".$objinstance->GetFriendlyName()."</td>\n";
					}
					echo "    <td>";
if ($access)
{
					echo "<a href=\"editevent.php".$urlext."&amp;action=edit&amp;module=".$oneevent['originator']."&amp;event=".$oneevent['event_name']."\" title=\"".lang('edit')."\">";
}
					echo $oneevent['event_name'];
if ($access)
{
					echo "</a>";
}
					echo "</td>\n";
					echo "    <td>";
					if ($oneevent['usage_count'] > 0)
						{
						echo "<a href=\"eventhandlers.php".$urlext."&amp;action=showeventhelp&amp;module=".$oneevent['originator']."&amp;event=".$oneevent['event_name']."\" title=\"".lang('help')."\">".
							$oneevent['usage_count']."</a>";
						}
					echo "</td>\n";
					echo "    <td>".$desctext."</td>\n";
					echo "    <td class=\"icons_wide\"><a href=\"eventhandlers.php".$urlext."&amp;action=showeventhelp&amp;module=".$oneevent['originator']."&amp;event=".$oneevent['event_name']."\">".$infoImg."</a></td>\n";
if ($access)
{
					echo "    <td class=\"icons_wide\"><a href=\"editevent.php".$urlext."&amp;action=edit&amp;module=".$oneevent['originator']."&amp;event=".$oneevent['event_name']."\">".$editImg."</a></td>\n";
}
					echo "  </tr>\n";
					($curclass=="row1"?$curclass="row2":$curclass="row1");
				}
			}
		}

		echo "</tbody>\n";
		echo "</table>\n";
	} // default action

} // switch


echo "</div>\n";
echo "</div>\n";

include_once("footer.php");

?>
