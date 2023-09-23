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

$CMS_ADMIN_PAGE=1;

require_once("../lib/include.php");
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

if (isset($_POST["cancel"])) {
  redirect("listbookmarks.php".$urlext);
}

$error = "";
$title = "";
if (isset($_POST["title"])) $title = trim(cleanValue($_POST["title"]));

$url = "";
if (isset($_POST["url"])) $url = trim(cleanValue($_POST["url"]));
if ($url) {
  $url = html_entity_decode($url);
  $url = urldecode($url);
  //this validation should be in a standalone function, for use by both
  //add- and edit-bookmark scripts
  $url = str_replace('[ROOT_URL]', CMS_ROOT_URL, $url);
  $extsub = substr($urlext, 1);
  if (strpos($url, '[SECURITYTAG]') !== false) { // deprecated
    $url = str_replace('[SECURITYTAG]', $extsub, $url);
  }

  // mimic FILTER_SANITIZE_URL, allowing valid UTF-8 and extended-ASCII chars
  if (preg_match('/[^\x21-\x7e\p{L}\p{N}\p{Po}\x82-\x84\x88\x8a\x8c\x8e\x91-\x94\x96-\x98\x9a\x9c\x9e\x9f\xa8\xad\xb4\xb7\xb8\xc0-\xf6\xf8-\xff]/u', $url)) {
    unset($_POST['editbookmark']);
    $error .= "<li>".lang('illegalcharacters', lang('url'))."</li>";
  }
  else {
    $validurl = function($checkurl, $blockhosts) {
      $parts = parse_url($checkurl);
      if ($parts) {
        if (empty($parts['scheme'])) return false;
        $val = strtolower($parts['scheme']);
        // lots of valid schemes https://en.wikipedia.org/wiki/List_of_URI_schemes
        if (in_array($val, [
        'attachment',
        'blob',
        'chrome',
        'cid',
        'data',
        'dns',
        'example',
        'file',
        'filesystem',
        'ftp',
        'query',
        'sftp',
        'tel',
        'tftp',
        'view-source',
        ])) return false;
        // some typo checks
        similar_text($val, 'https', $p1);
        $near = ($p1 > 60 && $p1 < 100);
        if ($near) {
          similar_text($val, 'http', $p2);
          $near = ($p2 > 60 && $p2 < 100);
        }
        if ($near) return false;

        if (empty($parts['host'])
         || in_array($parts['host'], $blockhosts)) return false;
//TODO other sanity checks, malevolence checks
//e.g. refer to https://owasp.org/www-community/attacks/Forced_browsing
//www.example.com/function.jsp?fwd=admin.jsp
//www.example.com/example.php?url=http://malicious.example.com
        return true;
      }
      return false;
    };

    //$sitehost = parse_url(CMS_ROOT_URL, PHP_URL_HOST);
    //treated as ok for frontend urls (MAMS aside?)
    //TODO blacklisted hosts?
    $reported = false;
    if (!$validurl($url, [])) {
      unset($_POST['editbookmark']);
      $error .= "<li>".lang('error_badfield', lang('url'))."</li>";
      $reported = true;
    }

    $url = str_replace($extsub, '[SECURITYTAG]', $url); // if any
    $config = cms_config::get_instance();
    if (startswith($url, $config['admin_url'])) {
      //TODO somewhere apply a permission-check akin to admin menu generation
      if (strpos($url, '[SECURITYTAG]') === false) {
        unset($_POST['editbookmark']);
        if (!$reported) { // don't repeat same error
          $error .= "<li>".lang('error_badfield', lang('url'))."</li>";
        }
      }
    }
    elseif (strpos($url, '[SECURITYTAG]') !== false) {
      unset($_POST['editbookmark']);
      if (!$reported) {
        $error .= "<li>".lang('error_badfield', lang('url'))."</li>";
      }
    }
  }
}

$bookmark_id = -1;
if (isset($_POST["bookmark_id"])) $bookmark_id = (int)$_POST["bookmark_id"];
else if (isset($_GET["bookmark_id"])) $bookmark_id = (int)$_GET["bookmark_id"];

$userid = get_userid();

if (isset($_POST["editbookmark"])) {
  $validinfo = true;
  if ($title == "") {
    $validinfo = false;
    $error .= "<li>".lang('nofieldgiven', array(lang('title')))."</li>";
  }
  if ($url == "") {
    $validinfo = false;
    $error .= "<li>".lang('nofieldgiven', array(lang('url')))."</li>";
  }

  if ($validinfo) {
    $markobj = new Bookmark();
    $markobj->bookmark_id = $bookmark_id;
    $markobj->title = $title;
    $markobj->url = $url;
    $markobj->user_id = $userid;

    $result = $markobj->save();

    if ($result) {
      redirect("listbookmarks.php".$urlext);
    }
    else {
      $error .= "<li>".lang('errorupdatingbookmark')."</li>";
    }
  }
}
else if ($bookmark_id != -1) {
  $db = cmsms()->GetDb();
  $query = "SELECT * from ".CMS_DB_PREFIX."admin_bookmarks WHERE bookmark_id = ?";
  $result = $db->Execute($query, array($bookmark_id));
  if ($result) {
    $row = $result->FetchRow();
    foreach (['title','url'] as $fld) {
      if ($row[$fld] === null) $row[$fld] = '';
    }
    $url = $row['url'];
    $title = $row['title'];
    $result->Close();
  }
}

if (strlen($title) > 0) $CMS_ADMIN_SUBTITLE = $title;
$urlhelp = cms_admin_utils::get_help_tag(['key2'=>'help_bookmark_url', 'title'=>lang('url')]);

include_once("header.php");

if ($error != "") echo '<div class="pageerrorcontainer"><p class="pageerror">'.$error.'</p></div>';
?>

<div class="pagecontainer">
    <?php echo $themeObject->ShowHeader('editbookmark') ?>
    <form method="post" action="editbookmark.php">
        <div>
          <input type="hidden" name="<?php echo CMS_SECURE_PARAM_NAME ?>" value="<?php echo $_SESSION[CMS_USER_KEY] ?>">
          <input type="hidden" name="editbookmark" value="true">
          <input type="hidden" name="bookmark_id" value="<?php echo $bookmark_id ?>">
          <input type="hidden" name="userid" value="<?php echo $userid ?>">
        </div>
        <div class="pageoverflow">
            <p class="pagetext"><?php echo lang('title') ?>:</p>
            <p class="pageinput"><input type="text" name="title" maxlength="255" value="<?php echo $title ?>"></p>
        </div>
        <div class="pageoverflow">
            <p class="pagetext"><?php echo lang('url').':&nbsp;'.$urlhelp ?></p>
            <p class="pageinput"><input type="text" name="url" size="80" maxlength="255" value="<?php echo $url ?>"></p>
        </div>
        <br>
        <div class="pageoverflow">
            <p class="pageinput">
                <input type="submit" value="<?php echo lang('submit') ?>" class="pagebutton">
                <input type="submit" name="cancel" value="<?php echo lang('cancel') ?>" class="pagebutton">
            </p>
        </div>
    </form>
</div>
<?php

include_once("footer.php");

?>
