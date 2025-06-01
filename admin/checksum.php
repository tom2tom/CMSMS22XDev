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
//$CMS_ADMIN_TITLE = 'system_verification';
$orig_memory = (function_exists('memory_get_usage')) ? memory_get_usage() : 0;

require_once '../lib/include.php'; //CMSMS functions N/A yet

check_login();
$userid = get_userid();
$access = check_permission($userid,'Modify Site Preferences');
if( !$access ) {
  exit(lang('no_permission')); //TODO throw if can be caught
}

require_once 'header.php';
require_once cms_join_path(dirname(__DIR__),'lib','test.functions.php');

//returns bool indicating success and if false, sets $report (string)
function check_checksum_data(&$report)
{
  if( (!isset($_FILES['cksumdat'])) || empty($_FILES['cksumdat']['name']) ) {
    $report = lang('error_nofileuploaded');
    return false;
  }
  elseif( $_FILES['cksumdat']['error'] > 0 ) {
    $report = lang('error_uploadproblem');
    return false;
  }
  elseif( $_FILES['cksumdat']['size'] == 0 ) {
    $report = lang('error_uploadproblem');
    return false;
  }

  $fh = fopen($_FILES['cksumdat']['tmp_name'],'rb');
  if( !$fh ) {
    $report = lang('error_uploadproblem');
    return false;
  }

  $fp1 = cms_join_path(CMS_ROOT_PATH,'lib','version.php');
  $fp2 = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'index.php';
  $salt = md5_file($fp1).md5_file($fp2);
  $filenotfound = [];
  $notreadable = 0;
  $md5failed = 0;
  $filesfailed = [];
  $filespassed = 0;
  $errorlines = 0;
  while( !feof($fh) ) {
      // get a line
      $line = fgets($fh,4096);

      // strip out comments
      $pos = strpos($line,'#');
      if( $pos !== false ) $line = substr($line,0,$pos);

      // trim the line
      $line = trim($line);

      // skip empty line
      if( empty($line) ) continue;

      // split it into fields
      if( strpos($line,'--::--') === false ) {
          $errorlines++;
          continue;
      }
      list($md5sum,$file) = explode('--::--',$line,2);

      if( !$md5sum || !$file ) {
          $errorlines++;
          continue;
      }

      $md5sum = trim($md5sum);
      $file = trim($file);

      $fn = cms_join_path(CMS_ROOT_PATH,$file);
      if( !file_exists( $fn ) ) {
          $filenotfound[] = $file;
          continue;
      }

      if( is_dir( $fn ) ) continue;

      if( !is_readable( $fn ) ) {
          $notreadable++;
          continue;
      }

      $md5 = md5($salt.md5_file($fn));
      if( !$md5 ) {
          $md5failed++;
          continue;
      }

      if( $md5sum != $md5 )  $filesfailed[] = $file;

      // it passed.
      $filespassed++;
  }
  fclose($fh);

  if( $filespassed == 0 || $filenotfound || $errorlines > 0 || $notreadable > 0 || $md5failed > 0 || $filesfailed ) {
    // build the error report
    $tmp2 = array();
    if( $filespassed == 0 ) $tmp2[] = lang('no_files_scanned');
    if( $errorlines > 0 ) $tmp2[] = lang('lines_in_error',$errorlines);
    if( $filenotfound ) $tmp2[] = sprintf("%d %s",count($filenotfound),lang('files_not_found'));
    if( $notreadable > 0 ) $tmp2[] = sprintf("%d %s",$notreadable,lang('files_not_readable'));
    if( $md5failed > 0 ) $tmp2[] = sprintf("%d %s",$md5failed,lang('files_checksum_failed'));

    $tmp = implode('<br>',$tmp2);
    if( $filenotfound ) {
      $tmp .= '<br>'.lang('files_not_found').':';
      $tmp .= '<br>'.implode('<br>',$filenotfound).'<br>';
    }
    if( $filesfailed ) {
      $tmp .= '<br>'.count($filesfailed).' '.lang('files_failed').':';
      $tmp .= '<br>'.implode('<br>',$filesfailed).'<br>';
    }

    $report = $tmp;
    return false;
  }

  return true;
}

//returns bool indicating success and if false, sets $report (string)
function generate_checksum_file(&$report)
{
  $config = cms_config::get_instance();
  $uptop = basename($config['uploads_path']);
  $tmp = get_recursive_file_list(CMS_ROOT_PATH,
    ["^$uptop\$",'^tmp$','^captchas$','index\.html?$',
     '^\.svn','^\.git','^CVS$','^\#.*\#$','~$','\.bak$']); //some of the exclusions are silly for production site
  if( !$tmp ) {
    $report = lang('error_retrieving_file_list');
    return false;
  }

  $output = '';
  $fp1 = cms_join_path(CMS_ROOT_PATH,'lib','version.php');
  $fp2 = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'index.php';
  $salt = md5_file($fp1).md5_file($fp2);

  foreach( $tmp as $file ) {
    if( is_dir($file) ) continue;
    $md5sum = md5($salt.md5_file($file));
    $file = str_replace(CMS_ROOT_PATH,'',$file);
    $output .= "{$md5sum}--::--{$file}\n";
  }

  $num = count(ob_list_handlers());
  for ($cnt = 0; $cnt < $num; $cnt++) { ob_end_clean(); }

  header('Pragma: public');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Cache-Control: private',false);
  header('Content-Description: File Transfer');
  header('Content-Type: text/plain');
  header('Content-Disposition: attachment; filename="checksum.dat"' );
  header('Content-Transfer-Encoding: binary');
  header('Content-Length: ' . strlen($output));
  echo $output;
  return true;
};

// Get ready
$gCms = CmsApp::get_instance();
$smarty = $gCms->GetSmarty();
$smarty->caching = false;
$smarty->force_compile = true;
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

// Handle output
if( isset($_POST['action']) ) {
  @set_time_limit(9999); // this might not work on some hosts
  $res = true;
  $report = '';
  switch($_POST['action']) {
  case 'upload':
    $res = check_checksum_data($report);
    if( $res ) {
      $themeObject->ShowMessage(lang('checksum_passed'));
    }
    break;
  case 'download':
    $res = generate_checksum_file($report);
    if( $res ) {
      redirect('checksum.php'.$urlext.'&exported=1'); //come back here to show completion message DOESN'T WORK
      return; //USEFUL?
    }
    break;
  }
  if( !$res ) $smarty->assign('error',$report);
}
elseif( !empty($_GET['exported']) ) {
  $themeObject->ShowMessage(lang('msg_completed'));
}

// Display the output
$smarty->assign('urlext',$urlext)
 ->assign('cms_secure_param_name',CMS_SECURE_PARAM_NAME)
 ->assign('cms_user_key',$_SESSION[CMS_USER_KEY])
 ->display('checksum.tpl');

require_once 'footer.php';

?>
