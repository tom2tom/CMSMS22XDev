<?php
#CMS Made Simple admin console script
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

$CMS_ADMIN_PAGE = 1;

require_once("../lib/include.php");
check_login();

$userid = get_userid();
$access = check_permission($userid, "Modify Site Preferences");
if (!$access) {
  exit(lang('no_permission')); //TODO throw if can be caught
}

include_once "header.php";

require_once cms_join_path(dirname(__DIR__), 'lib', 'test.functions.php');

$gCms = cmsms();
$smarty = $gCms->GetSmarty(); //also in header.php
$smarty->caching = false;
$smarty->force_compile = true;
$smarty->assign('theme', $themeObject);

/*
 *
 * Database
 *
 */

$db = $gCms->GetDb();
$query = "SHOW TABLES LIKE ?";
$tablestmp = $db->GetArray($query,array(CMS_DB_PREFIX.'%'));
$tables = array();
$nonseqtables = array();
foreach ($tablestmp as $table) {
  foreach ($table as $tabeinfo => $tablename) {
    $tables[] = $tablename;
    if (!stripos($tablename, "_seq")) {
      $nonseqtables[] = $tablename;
    }
  }
}

$smarty->assign("tablecount", count($tables));
$smarty->assign("nonseqcount", count($nonseqtables));


function MakeCommaList($tables)
{
  $out = '';
  foreach ($tables as $table) {
    if ($out) $out .= ' ,';
    $out .= "`" . $table . "`";
  }
  return $out;
}

if (isset($_POST["optimizeall"])) {
  $query = "OPTIMIZE TABLE " . MakeCommaList($nonseqtables);
  $optimizearray = $db->GetArray($query);
  //print_r($optimizearray);
  $errorsfound = 0;
  $errordetails = "";
  foreach ($optimizearray as $check) {
    if (isset($check["Msg_text"]) && $check["Msg_text"] != "OK") {
      $errorsfound++;
      $errordetails .= "MySQL reports that table " . $check["Table"] . " does not checkout OK.<br>";
    }
  }

  // put mention into the admin log
  audit('', 'System maintenance', 'All db-tables optimized');
  $themeObject->ShowMessage(lang("sysmain_tablesoptimized"));
  $smarty->assign("active_database", "true");
}

if (isset($_POST["repairall"])) {
  $query = "REPAIR TABLE " . MakeCommaList($tables);
  $repairarray = $db->GetArray($query);
  $errorsfound = 0;
  $errordetails = "";
  foreach ($repairarray as $check) {
    if (isset($check["Msg_text"]) && $check["Msg_text"] != "OK") {
      $errorsfound++;
      $errordetails .= "MySQL reports that table " . $check["Table"] . " does not checkout OK.<br>";
    }
  }

  // put mention into the admin log
  audit('', 'System maintenance', 'All db-tables repaired');
  $themeObject->ShowMessage(lang("sysmain_tablesrepaired"));
  $smarty->assign("active_database", "true");
}

$urlext = '?' . CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY];
$smarty->assign("formurl", "systemmaintenance.php" . $urlext);

$query = "CHECK TABLE " . MakeCommaList($tables);
//echo $query;
$checkarray = $db->GetArray($query);
//print_r($checkarray);

$errortables = array();
foreach ($checkarray as $check) {
  if (isset($check["Msg_text"]) && $check["Msg_text"] != "OK") {
    $errortables[] = $check["Table"];
  }
}

$smarty->assign("errorcount", count($errortables));
if (count($errortables) > 0) {
  $smarty->assign("errortables", implode(",", $errortables));
}

/*
 *
 * Cache and content
 *
 */
$contentops = $gCms->GetContentOperations();

if (isset($_POST['updateurls'])) {
  cms_route_manager::rebuild_static_routes();
  audit('', 'System maintenance', 'Static routes rebuilt');
  $themeObject->ShowMessage(lang("routesrebuilt"));
  $smarty->assign("active_content", "true");
}

if (isset($_POST['clearcache'])) {
  $gCms->clear_cached_files(-1);
  audit('', 'System maintenance', 'Smarty page-content caches cleared');
//TODO also do $contentops->SetContentModified();
  $themeObject->ShowMessage(lang("cachecleared"));
  $smarty->assign("active_content", "true");
}

if (isset($_POST["updatehierarchy"])) {
  $contentops->SetAllHierarchyPositions();
  audit('', 'System maintenance', 'Page hierarchy positions updated');
  $themeObject->ShowMessage(lang("sysmain_hierarchyupdated"));
  $smarty->assign("active_content", "true");
}

//Setting up types
$contenttypes = $contentops->ListContentTypes(false, true);
//print_r($contenttypes);
$simpletypes = array();
foreach ($contenttypes as $typeid => $typename) {
  $simpletypes[] = $typeid;
}


if (isset($_POST["addaliases"])) {
  $count = 0;
  $query = "SELECT content_id,content_name,type,menu_text,content_alias FROM " . CMS_DB_PREFIX . "content WHERE content_alias IS NULL OR content_alias=''";
  $allcontent = $db->GetArray($query);
  if ($allcontent) {
    $query2 = "UPDATE " . CMS_DB_PREFIX . "content SET content_alias=? WHERE content_id=?";
    foreach ($allcontent as $contentpiece) {
      foreach( [
        'content_name',
        'type',
        'menu_text',
        'content_alias'
      ] as $fld ) {
        if ($contentpiece[$fld] === null) $contentpiece[$fld] = '';
      }
      $content_id = (int)$contentpiece['content_id'];
      if (trim($contentpiece['content_alias']) == '' && $contentpiece['type'] != 'separator') {
        $alias = trim($contentpiece['menu_text']);
        if ($alias == '') {
          $alias = trim($contentpiece['content_name']);
        }
        $alias = munge_string_to_url($alias, true);
        if (!$alias) continue; //TODO throw
        if ($contentops->CheckAliasUsed($alias, $content_id)) {
          // Some other page uses it already, generate a suffixed variant
          $alias_num_add = 2;
          // If a '-2' variant of the alias is used, try '-3', etc.
          while ($contentops->CheckAliasUsed($alias . '-' . $alias_num_add)) {
            $alias_num_add++;
          }
          $alias .= '-' . $alias_num_add;
        }
        $dbresult = $db->Execute($query2, array($alias, $content_id));
        $count++;
      }
    }
    $contentops->SetAllHierarchyPositions(); // update hierarchy_path's
  }

  audit('', 'System maintenance', "Updated $count page(s) whose alias was missing");
  $themeObject->ShowMessage($count . " " . lang("sysmain_aliasesfixed"));
  $smarty->assign("active_content", "true");
}


if (isset($_POST["fixtypes"])) {
  $count = 0;
  $query = "SELECT content_id,type FROM " . CMS_DB_PREFIX . "content";
  $allcontent = $db->GetArray($query);
  if ($allcontent) {
    $query2 = "UPDATE " . CMS_DB_PREFIX . "content SET type='content' WHERE content_id=?";
    foreach ($allcontent as $contentpiece) {
      if (!$contentpiece['type'] ||
          !in_array($contentpiece['type'], $simpletypes)) {
        $dbresult = $db->Execute($query2, array($contentpiece['content_id']));
        $count++;
      }
    }
  }

  audit('', 'System maintenance', "Converted $count page(s) with invalid content type");
  $themeObject->ShowMessage($count . " " . lang("sysmain_typesfixed"));
  $smarty->assign("active_content", "true");
}


$pages = array();
$withoutalias = array();
$invalidtypes = array();
$query = "SELECT content_name,type,content_alias FROM " . CMS_DB_PREFIX . "content ORDER BY hierarchy_path";
$allcontent = $db->GetArray($query);
if ($allcontent) {
  foreach ($allcontent as $contentpiece) {
    foreach( [
      'content_name',
      'type',
      'content_alias',
    ] as $fld ) {
      if ($contentpiece[$fld] === null) $contentpiece[$fld] = '';
    }
    $pages[] = $contentpiece['content_name'];
    if (trim($contentpiece['content_alias']) == '' && $contentpiece['type'] != 'separator') {
      $withoutalias[] = $contentpiece;
    }
    if (!in_array($contentpiece['type'], $simpletypes)) {
      $invalidtypes[] = $contentpiece;
    }
  }
}

$smarty->assign("pagecount", count($pages));
$smarty->assign("pagesmissingalias", $withoutalias);
$smarty->assign("withoutaliascount", count($withoutalias));
$smarty->assign("pageswithinvalidtype", $invalidtypes);
$smarty->assign("invalidtypescount", count($invalidtypes));

/*
 *
 * Changelog
 *
 */
$ch_filename = cms_join_path(dirname(__DIR__), 'doc', 'CHANGELOG.txt');

if (is_readable($ch_filename)) {
    $changelog = @file($ch_filename);

    for ($i = 0, $n = count($changelog); $i < $n; $i++) {
      if (strncmp($changelog[$i], "Version", 7) == 0) {
        if ($i == 0) {
          $changelog[$i] = "<div class=\"version\"><h3>" . $changelog[$i] . "</h3>";
        } else {
          $changelog[$i] = "</div><div class=\"version\"><h3>" . $changelog[$i] . "</h3>";
        }
      }
    }

    $changelog = implode("<br>", $changelog);

    $smarty->assign("changelog", $changelog);
    $smarty->assign("changelogfilename", $ch_filename);

}

$smarty->assign('backurl', $themeObject->BackUrl());

$smarty->display('systemmaintenance.tpl');

include_once "footer.php";

?>
