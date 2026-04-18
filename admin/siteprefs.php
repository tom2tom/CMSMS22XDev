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

use CMSMS\internal\global_cache;

$CMS_ADMIN_PAGE = 1;
//$CMS_TOP_MENU = 'admin';
//$CMS_ADMIN_TITLE = 'preferences';

require_once '../lib/include.php';
check_login();

$userid = get_userid(); // <- Checks also login

$access = check_permission($userid, 'Modify Site Preferences');
if( !$access ) {
  exit(lang('no_permission')); //TODO throw if can be caught
}

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
if( isset($_POST['cancel']) ) {
  redirect('index.php'.$urlext.'&section=siteadmin');
}
$pjobs = check_permission($userid,'Manage Jobs');

/*
 * Interpret octal permissions, and return a human-readable string.
 *
 * @internal
 * @param int The permissions to test.
 * @return array
 */
function siteprefs_interpret_permissions($perms)
{
  $owner = [];
  $group = [];
  $other = [];

  if( $perms & 0400 ) $owner[] = lang('read');
  if( $perms & 0200 ) $owner[] = lang('write');
  if( $perms & 0100 ) $owner[] = lang('execute');
  if( $perms & 0040 ) $group[] = lang('read');
  if( $perms & 0020 ) $group[] = lang('write');
  if( $perms & 0010 ) $group[] = lang('execute');
  if( $perms & 0004 ) $other[] = lang('read');
  if( $perms & 0002 ) $other[] = lang('write');
  if( $perms & 0001 ) $other[] = lang('execute');

  return [$owner,$group,$other];
}

function siteprefs_display_permissions($permsarr)
{
  if( count($permsarr) != 3 ) return lang('permissions_parse_error');
  $tmparr = [lang('owner'),lang('group'),lang('other')];

  $result = [];
  for( $i = 0; $i < 3; $i++ ) {
    $str = $tmparr[$i].': ';
    $str .= implode(',',$permsarr[$i]);
    $result[] = $str;
  }
  $str = implode('<br>&nbsp;&nbsp;',$result);
  return $str;
}

$gCms = cmsms();
$db = $gCms->GetDb();
$config = $gCms->GetConfig();
$devmode = !empty($config['developer_mode']);
$error = '';
$message = '';
$testresults = lang('untested');

// Get preferences
global_cache::clear('cms_siteprefs'); //use original data
if( $pjobs ) {
  $jobs_interval = cms_siteprefs::get('jobs_interval',15);
  $jobs_timeout = cms_siteprefs::get('jobs_timeout',30);
  $job_maxerrs = cms_siteprefs::get('job_maxerrs',5);
}
$adminlog_lifetime = cms_siteprefs::get('adminlog_lifetime',86400 * 31);
$allow_browser_cache = cms_siteprefs::get('allow_browser_cache',0);
$auto_clear_cache_age = cms_siteprefs::get('auto_clear_cache_age',0);
$backendwysiwyg = cms_siteprefs::get('backendwysiwyg');
$browser_cache_expiry = cms_siteprefs::get('browser_cache_expiry',60);
$checkversion = cms_siteprefs::get('checkversion',1);
$defaultdateformat = cms_siteprefs::get('defaultdateformat');
$enablesitedownmessage = cms_siteprefs::get('enablesitedownmessage',0);
$frontendlang = cms_siteprefs::get('frontendlang');
$frontendwysiwyg = cms_siteprefs::get('frontendwysiwyg');
$global_umask = cms_siteprefs::get('global_umask') ?: str_pad(decoct(umask()), 3, '0', STR_PAD_LEFT);
$lock_timeout = (int)cms_siteprefs::get('lock_timeout',60);
$logintheme = cms_siteprefs::get('logintheme','default');
$mail_is_set = cms_siteprefs::get('mail_is_set',0);
$tmp = cms_siteprefs::get('mailprefs');
$mailprefs = ($tmp) ? unserialize($tmp,['allowed_classes'=>[]]) : '';
if( !$mailprefs ) {
  $mailprefs = [
    'mailer'=>'mail',
    'host'=>'localhost',
    'port'=>25,
    'from'=>'root@localhost.localdomain',
    'fromuser'=>'CMS Administrator',
    'sendmail'=>'/usr/sbin/sendmail',
    'smtpauth'=>0,
    'smtpautotls'=>1,
    'username'=>'',
    'password'=>'',
    'secure'=>'',
    'timeout'=>60,
    'charset'=>'utf-8'
  ];
  $mail_is_set = 0;
}
$metadata = cms_siteprefs::get('metadata');
$notices_timeout = cms_siteprefs::get('notices_timeout',10);
$search_module = cms_siteprefs::get('searchmodule','Search');
$sitedownexcludeadmins = cms_siteprefs::get('sitedownexcludeadmins');
$sitedownexcludes = cms_siteprefs::get('sitedownexcludes');
$sitedownmessage = cms_siteprefs::get('sitedownmessage','<p>Site is currently down. Check back later.</p>');
$sitename = cms_html_entity_decode(cms_siteprefs::get('sitename','CMSMS Website'));
$SmartyAdmincacheLife = (int)cms_siteprefs::get('SmartyAdmincacheLife',30);
$SmartyFrontcacheLife = (int)cms_siteprefs::get('SmartyFrontcacheLife',60);
$thumbnail_height = cms_siteprefs::get('thumbnail_height',96);
$thumbnail_width = cms_siteprefs::get('thumbnail_width',96);
$use_smartycache = cms_siteprefs::get('use_smartycache',0);
$use_smartycompilecheck = cms_siteprefs::get('use_smartycompilecheck',1);
$use_wysiwyg = cms_siteprefs::get('sitedown_use_wysiwyg',1);
$xmlmodulerepository = cms_siteprefs::get('xmlmodulerepository');
if( $devmode ) {
  $ppath = cms_siteprefs::get('privatePath');
}

// Active tab
$tab = (isset($_POST['active_tab'])) ? trim(cleanValue($_POST['active_tab'])) : '';

// Submit
if( isset($_POST['testmail']) ) { // not 'testemail'
  if( !$mail_is_set ) {
    $error .= '<li>'.lang('error_mailnotset_notest').'</li>';
  }
  elseif( $_POST['mailtest_testaddress'] == '' ) {
    $error .= '<li>'.lang('error_mailtest_noaddress').'</li>';
  }
  else {
    $addr = cleanValue($_POST['mailtest_testaddress']);
    $addr = cms_html_entity_decode($addr);
    if( !is_email($addr) ) {
      $error .= '<li>'.lang('error_mailtest_notemail').'</li>';
    }
    else {
      // we got an email, and we have settings.
      try {
        $mailer = new cms_mailer();
        $mailer->AddAddress($addr);
        $mailer->IsHTML(true);
        $mailer->SetBody(lang('mail_testbody','siteprefs'));
        $mailer->SetSubject(lang('mail_testsubject','siteprefs'));
        $mailer->Send();
        if( $mailer->IsError() ) {
          $error .= '<li>'.$mailer->GetErrorInfo().'</li>';
        }
        else {
          $message .= lang('testmsg_success');
        }
      }
      catch( Exception $e ) {
        $error .= '<li>'.$e->GetMessage().'</li>';
      }
    }
  }
}

if( isset($_POST['testumask']) ) {
  $testdir = TMP_CACHE_LOCATION;
  $testfile = $testdir.DIRECTORY_SEPARATOR.'dummy.tst';
  if( !is_writable($testdir) ) {
    $testresults = lang('errordirectorynotwritable');
  }
  else {
    @umask(octdec($global_umask));

    $fh = @fopen($testfile,"w");
    if( !$fh ) {
      $testresults = lang('errorcantcreatefile').' ('.$testfile.')';
    }
    else {
      @fclose($fh);
      $filestat = stat($testfile);
      if( $filestat == false ) $testresults = lang('errorcantcreatefile');

      if( function_exists("posix_getpwuid") ) {
        //function posix_getpwuid not available on WAMP systems
        $userinfo = @posix_getpwuid($filestat[4]);
        $username = isset($userinfo['name']) ? $userinfo['name'] : lang('unknown');
        $permsstr = siteprefs_display_permissions(siteprefs_interpret_permissions($filestat[2]));
        $testresults = sprintf("%s: %s<br>%s:<br>&nbsp;&nbsp;%s",lang('owner'),$username,lang('permissions'),$permsstr);
      }
      else {
        $testresults = sprintf("%s: %s<br>%s:<br>&nbsp;&nbsp;%s",lang('owner'),"N/A",lang('permissions'),"N/A");
      }
      @unlink($testfile);
    }
  }
}

if( isset($_POST['editsiteprefs']) ) {
  if( $access ) {
    switch( $tab ) {
    case 'general':
      // tab 1
      // @todo: should validate input or fully trust users allowed to change these values
      if( isset($_POST['sitename']) ) {
        $sitename = cleanValue($_POST['sitename']);
        cms_siteprefs::set('sitename', $sitename);
      }
      if( isset($_POST['frontendlang']) ) {
        $frontendlang = cleanValue($_POST['frontendlang']);
        cms_siteprefs::set('frontendlang', $frontendlang);
      }
      if( isset($_POST['frontendwysiwyg']) ) {
        $frontendwysiwyg = cleanValue($_POST['frontendwysiwyg']);
        cms_siteprefs::set('frontendwysiwyg', $frontendwysiwyg);
      }
      if( !empty($_POST['metadata']) ) {
        $matches = [];
        $merr = [];
        $val = addcslashes(trim($_POST['metadata']), '~+*?[]^$(){}\\|');
        $arr = preg_split('~<meta\s+~i', $val);
        foreach( $arr as &$val ) {
          if( $val ) {
            if( preg_match('~^(\s*[a-zA-Z]{2,}[\w\-.]*\s*=\s*(["\'])[^<>]+\2\s*)+/{0,1}>(.*)$~s', $val, $matches) ) {
              if( $matches[3] ) {
                $val = str_replace($matches[3], '', $val);
                if( preg_match('~\S~', $matches[3]) ) {
                  $merr[] = 'unqouted data';
                }
              }
              $val = rtrim($val, "/>\r\n\t ") . '>'; //html5 format
              $o = 0;
              while (preg_match('~(.*?)([a-zA-Z]{2,}[\w\-.]*)\s*=\s*("(\\\\.|[^"])*"|\'(\\\\.|[^\'])*\')(\s+|\s*\/{0,1}>\s*)~', $val, $matches, PREG_OFFSET_CAPTURE, $o)) {
                if( ($s = strpbrk($matches[3][0], '`$')) ) {
                  $val = '';
                  $merr[] = 'prohibited '. $s[0] . ' in data';
                  break;
                }
                if( $matches[1][0] ) {
                  $s = str_repeat(' ', strlen($matches[1][0]));
                  $val = str_replace($matches[1][0], $s, $val);
                  $merr[] = 'unqouted data';
                }
                //filter per meta name TODO deal with all oWASP examples
                switch ($matches[2][0]) {
                  case 'content':
                    $s = trim($matches[3][0], '"\'');
                    if( $s == 'no-referrer' ) {
                      if( ($p = stripos($val, 'referrer')) !== false && $p < $matches[3][1] ) {
                        $val = '';
                        $merr[] = 'no-referrer';
                        break;
                      }
                    }
                    if( $s == 'upgrade-insecure-requests' ) {
                      if( ($p = stripos($val, 'Content-Security-Policy')) !== false && $p < $matches[3][1] ) {
                        $val = '';
                        $merr[] = 'insecure CSP override';
                        break;
                      }
                    }
                    if( stripos($s, 'url') !== false ) {
                      if( ($p = stripos($val, 'refresh')) !== false && $p < $matches[3][1] ) {
                        $val = '';
                        $merr[] = 'refresh URL';
                        break;
                      }
                    }
                    break;
                  default:
                    break;
                }
                if( $val !== '' ) {
                  $o = $matches[6][1] + strlen($matches[6][0]);
                }
                else {
                  continue 2;
                }
              }
              $val = '<meta ' . stripcslashes(trim($val));
            }
            else {
              $val = '';
              $merr[] = 'invalid format';
            }
          }
        }
        unset($val);
        cms_siteprefs::set('metadata', implode("\n", array_filter($arr)));
        if( $merr ) {
          $error .= '<li>'.lang('error_badfield', 'metadata').'</li>';
          $val = implode(',', $merr);
          audit('', 'Site metadata', 'Ignored some/all having '.$val);
        }
      }
      else {
        cms_siteprefs::set('metadata', '');
      }
      if( isset($_POST['notices_timeout']) ) {
        $notices_timeout = (int)$_POST['notices_timeout'];
        if( $notices_timeout < 0 ) { $notices_timeout = 0; }
        elseif( $notices_timeout > 30 ) { $notices_timeout = 30; }
        cms_siteprefs::set('notices_timeout', $notices_timeout);
      }
      if( isset($_POST['logintheme']) ) {
        $logintheme = cleanValue($_POST['logintheme']);
        cms_siteprefs::set('logintheme', $logintheme);
      }
      if( isset($_POST['backendwysiwyg']) ) {
        $backendwysiwyg = cleanValue($_POST['backendwysiwyg']);
        cms_siteprefs::set('backendwysiwyg', $backendwysiwyg);
      }
      if( isset($_POST['defaultdateformat']) ) {
        $defaultdateformat = str_replace('&#37;','%',cleanValue($_POST['defaultdateformat'])); // have to undo some cleaning.
        cms_siteprefs::set('defaultdateformat', $defaultdateformat);
      }
      if( isset($_POST['thumbnail_width']) ) {
        $thumbnail_width = (int)$_POST['thumbnail_width'];
        cms_siteprefs::set('thumbnail_width',$thumbnail_width);
      }
      if( isset($_POST['thumbnail_height']) ) {
        $thumbnail_height = (int)$_POST['thumbnail_height'];
        cms_siteprefs::set('thumbnail_height',$thumbnail_height);
      }
      if( isset($_POST['search_module']) ) {
        $search_module = trim(cleanValue($_POST['search_module']));
        cms_siteprefs::set('searchmodule',$search_module);
      }
      break;

/* exported to ContentManager settings UI
    case 'editcontent':
      break;
*/
    case 'sitedown':
      if( isset($_POST['sitedownexcludes']) ) {
        $sitedownexcludes = trim($_POST['sitedownexcludes']);
        cms_siteprefs::set('sitedownexcludes',$sitedownexcludes);
      }
      if( isset($_POST['sitedownexcludeadmins']) ) {
        $sitedownexcludeadmins = (int)$_POST['sitedownexcludeadmins'];
        cms_siteprefs::set('sitedownexcludeadmins',$sitedownexcludeadmins);
      }
      $tmp = false;
      if( isset($_POST['sitedownmessage']) ) {
        $tmp = trim(strip_tags($_POST['sitedownmessage']));
        if( $tmp ) {
          $sitedownmessage = $tmp;
          cms_siteprefs::set('sitedownmessage',$sitedownmessage);
        }
        else { $error .= lang('error_sitedownmessage'); }
      }
      $prevsitedown = $enablesitedownmessage;
      if( $tmp ) {
        if( isset($_POST['enablesitedownmessage']) ) {
          $enablesitedownmessage = (int)$_POST['enablesitedownmessage'];
          cms_siteprefs::set('enablesitedownmessage',$enablesitedownmessage);
        }
      }
      else {
        $enablesitedownmessage = false;
      }
      if( !$prevsitedown && $enablesitedownmessage ) {
        audit('','Global settings','Sitedown enabled');
      }
      elseif( $prevsitedown && !$enablesitedownmessage ) {
        audit('','Global settings','Sitedown disabled');
      }
      if( isset($_POST['use_wysiwyg']) ) {
        $use_wysiwyg = (int)$_POST['use_wysiwyg'];
        cms_siteprefs::set('sitedown_use_wysiwyg',$use_wysiwyg);
      }
      break;

    case 'mail':
      // gather mailprefs Values of disabled elements are provided (courtesy of jQ)
      $mclean = [];
      $prefix = 'mailprefs_';
      $lp = strlen($prefix);
      foreach( $_POST as $key => $val ) {
        if( !startswith($key,$prefix) ) continue;
        $key = substr($key,$lp);
        switch ($key) {
          case 'from':
            //TODO scrub malicious/XSS, invalid content c.f. execSpecialize()'s etc
            //TODO PHP's FILTER_SANITIZE_EMAIL is incomplete (per RFC5321)
            $mclean[$key] = filter_var(trim($val),FILTER_SANITIZE_EMAIL);
            break;
          case 'fromuser':
          case 'username':
            //TODO scrub malicious/XSS
            $mclean[$key] = trim($val,'<> ');
            break;
          case 'password':
            //TODO scrub malicious/XSS
            $mclean[$key] = $val;
            break;
          default:
            if( is_numeric($val) ) {
              // 'port' 'smtpauth' 'smtpautotls' 'timeout'
              $mclean[$key] = (int)$val;
            }
            else {
              // 'mailer' 'host' 'sendmail' 'secure' 'charset'
              $mclean[$key] = trim(cleanValue($val)); //OR custom filterer c.f. include.php
            }
        }
      }

      // validate
      if( $mclean['from'] == '' ) {
        $error .= '<li>'.lang('error_fromrequired').'</li>';
      }
      elseif( $mclean['from'] != trim($_POST[$prefix.'from']) ) {
        $error .= '<li>'.lang('error_frominvalid').'</li>';
      }
      elseif( !is_email($mclean['from']) ) {
        $error .= '<li>'.lang('error_frominvalid').'</li>';
      }
      if( $mclean['mailer'] == 'smtp' ) {
        if( $mclean['host'] == '' ) {
          $error .= '<li>'.lang('error_hostrequired').'</li>';
        }
        if( $mclean['port'] == '' ) $mclean['port'] = 25; // convenience
        if( $mclean['port'] < 1 || $mclean['port'] > 10240 ) {
          $error .= '<li>'.lang('error_portinvalid').'</li>';
        }
        if( $mclean['timeout'] == '' ) $mclean['timeout'] = 180;
        if( $mclean['timeout'] < 1 || $mclean['timeout'] > 3600 ) {
          $error .= '<li>'.lang('error_timeoutinvalid').'</li>';
        }
        if( $mclean['smtpauth'] ) {
          if( $mclean['username'] == '' ) $error .= '<li>'.lang('error_usernamerequired').'</li>';
          if( $mclean['password'] == '' ) $error .= '<li>'.lang('error_passwordrequired').'</li>';
        }
      }

      $mailprefs = $mclean + $mailprefs;
      if( !$error ) {
        cms_siteprefs::set('mailprefs',serialize($mailprefs));
        cms_siteprefs::set('mail_is_set',1);
      }
      break;

    case 'setup':
      if( isset($_POST['lock_timeout']) ) {
        $lock_timeout = (int)$_POST['lock_timeout'];
        cms_siteprefs::set('lock_timeout',$lock_timeout);
      }
      if( isset($_POST['xmlmodulerepository']) ) {
        $xmlmodulerepository = cleanValue($_POST['xmlmodulerepository']);
        cms_siteprefs::set('xmlmodulerepository',$xmlmodulerepository);
      }
      if( isset($_POST['checkversion']) ) {
        $checkversion = (int) $_POST['checkversion'];
        cms_siteprefs::set('checkversion',$checkversion);
      }
      if( isset($_POST['global_umask']) ) {
        $global_umask = cleanValue($_POST['global_umask']);
        cms_siteprefs::set('global_umask',$global_umask);
      }
      if( isset($_POST['allow_browser_cache']) ) {
        $allow_browser_cache = (int)$_POST['allow_browser_cache'];
        cms_siteprefs::set('allow_browser_cache',$allow_browser_cache);
      }
      if( isset($_POST['browser_cache_expiry']) ) {
        $browser_cache_expiry = (int)$_POST['browser_cache_expiry'];
        cms_siteprefs::set('browser_cache_expiry',$browser_cache_expiry);
      }
      if( isset($_POST['auto_clear_cache_age']) ) {
        $auto_clear_cache_age = (int)$_POST['auto_clear_cache_age'];
        cms_siteprefs::set('auto_clear_cache_age',$auto_clear_cache_age);
      }
      if( isset($_POST['adminlog_lifetime']) ) {
        $adminlog_lifetime = (int)$_POST['adminlog_lifetime'];
        cms_siteprefs::set('adminlog_lifetime',$adminlog_lifetime);
      }
      if( $pjobs && isset($_POST['jobs_interval']) ) {
         $jobs_interval = max(3,min(60,(int)$_POST['jobs_interval']));
         cms_siteprefs::set('jobs_interval',$jobs_interval);
         $jobs_timeout = max(10,min(600,(int)$_POST['jobs_timeout']));
         cms_siteprefs::set('jobs_timeout',$jobs_timeout);
         $job_maxerrs = max(0,min(20,(int)$_POST['job_maxerrs']));
         cms_siteprefs::set('job_maxerrs',$job_maxerrs);
      }
      if( $devmode && isset($_POST['privatePath']) ) {
        $opath = $ppath;
        $ofull = private_place('',$config);
        $ppath = trim($_POST['privatePath'],' ,\\/');
        $ppath = strtr($ppath,[' '=>'','/'=>',','\\'=>',']);
        cms_siteprefs::set('privatePath',$ppath);
        $pfull = private_place('',$config);
        if( !$pfull ) {
          audit('','Global settings','Ignored invalid path: '.$ppath);
          cms_siteprefs::set('privatePath',$opath);
          //$error .= "<li>".lang('TODO')."</li>";
        }
        elseif( $pfull != $ofull ) {
          //TODO check stuff, do stuff e.g. rename, move content
          audit('','Global settings','Private path changed from: '.$opath);
          audit('','Global settings','Private path changed to: '.$ppath);
          $fp = cms_join_path(CMS_ROOT_PATH,'lib','classes','dbPath');
          chmod($fp,0666);
          file_put_contents($fp,$ppath);
          usleep(40000);
          chmod($fp,0444);
          //TODO reconcile access by installer upgrade or refresh ?
        }
      }
      break;

    case 'smarty':
      $use_smartycache = (isset($_POST['use_smartycache'])) ? (int)$_POST['use_smartycache'] : 0;
      cms_siteprefs::set('use_smartycache',$use_smartycache);
      $SmartyFrontcacheLife = max(0, min(180, (int)$_POST['SmartyFrontcacheLife']));
      cms_siteprefs::set('SmartyFrontcacheLife',$SmartyFrontcacheLife);
      $SmartyAdmincacheLife = max(0, min(180, (int)$_POST['SmartyAdmincacheLife']));
      cms_siteprefs::set('SmartyAdmincacheLife',$SmartyAdmincacheLife);
      $use_smartycompilecheck = (isset($_POST['use_smartycompilecheck'])) ? (int)$_POST['use_smartycompilecheck'] : 0;
      cms_siteprefs::set('use_smartycompilecheck',$use_smartycompilecheck);
      $gCms->clear_cached_files();
      break;
    }

    // put mention into the admin log
    if( !$error ) {
      audit('', 'Global settings', 'Edited');
      if( !isset($message) ) $message .= lang('siteprefsupdated');
    }
  }
  else {
    $error .= "<li>".lang('noaccessto', 'Modify Site Permissions')."</li>";
  }
}

// Build page

require_once 'header.php';

if( $error ) $themeObject->ShowErrors($error);
if( $message ) $themeObject->ShowMessage($message);

// Make sure cache folder is writable
if( !is_writable(TMP_CACHE_LOCATION) ||
    !is_writable(TMP_TEMPLATES_C_LOCATION) ) {
  $themeObject->ShowErrors(lang('cachenotwritable'));
}

$tpl = $smarty->createTemplate('admin_tpl:siteprefs.tpl',null,null,$smarty,false);

$modules = ModuleOperations::get_instance()->get_modules_with_capability('search');
if( $modules && is_array($modules) ) {
  $tmp = [];
  $tmp['-1'] = lang('none');
  for( $i = 0, $iMax = count($modules); $i < $iMax; $i++ ) {
    $tmp[$modules[$i]] = $modules[$i];
  }
  $tpl->assign('search_modules',$tmp);
}

$maileritems = [
  'mail'=>'mail',
  'sendmail'=>'sendmail',
  'smtp'=>'smtp'
];
$tpl->assign('maileritems',$maileritems);
$opts = [];
$opts[''] = lang('none');
$opts['ssl'] = 'SSL';
$opts['tls'] = 'TLS';
$tpl->assign('secure_opts',$opts);
$tpl->assign('mailprefs',$mailprefs);
$tpl->assign('mail_is_set',$mail_is_set);

$tpl->assign('languages',get_language_list());
$tpl->assign('tab',$tab);

// need a list of wysiwyg modules.
$tmp = module_meta::get_instance()->module_list_by_capability('wysiwyg');
$tmp2 = [-1 => lang('none')];
for( $i = 0, $iMax = count($tmp); $i < $iMax; $i++ ) {
  $tmp2[$tmp[$i]] = $tmp[$i];
}
$tpl->assign('wysiwyg',$tmp2);

if( ($dir = opendir(__DIR__ . '/themes')) ) {
  $themes = [];
  while( ($file = readdir($dir)) !== false ) {
    if( $file[0] != '.' && @is_dir("themes/$file") && @is_readable("themes/$file/{$file}Theme.php") ) {
      $themes[$file] = $file;
    }
  }
  if( count($themes) > 1 ) {
    $tpl->assign('themes',$themes);
    $tpl->assign('logintheme',cms_siteprefs::get('logintheme','default'));
  }
}

$tpl->assign('pjobs',$pjobs);
if( $pjobs ) {
  $tpl->assign('jobs_interval',$jobs_interval);
  $tpl->assign('jobs_timeout',$jobs_timeout);
  $tpl->assign('job_maxerrs',$job_maxerrs);
}
// see also $smarty-assigned var $secureparam
$tpl->assign('securename',CMS_SECURE_PARAM_NAME)
 ->assign('secureval',$_SESSION[CMS_USER_KEY])
 ->assign('sitename',$sitename)
 ->assign('site_ipaddr',cms_utils::get_real_ip())
 ->assign('global_umask',$global_umask)
 ->assign('testresults',$testresults)
 ->assign('frontendlang',$frontendlang)
 ->assign('frontendwysiwyg',$frontendwysiwyg)
 ->assign('backendwysiwyg',$backendwysiwyg)
 ->assign('metadata',$metadata)
 ->assign('notices_timeout',($notices_timeout>0)?(int)$notices_timeout:'')
 ->assign('enablesitedownmessage',$enablesitedownmessage)
 ->assign('use_wysiwyg',$use_wysiwyg)
 ->assign('textarea_sitedownmessage',create_textarea($use_wysiwyg,$sitedownmessage,'sitedownmessage','pagesmalltextarea'))
 ->assign('checkversion',$checkversion)
 ->assign('defaultdateformat',$defaultdateformat)
 ->assign('lock_timeout',$lock_timeout)
 ->assign('sitedownexcludes',$sitedownexcludes)
 ->assign('sitedownexcludeadmins',$sitedownexcludeadmins)
 ->assign('thumbnail_width',$thumbnail_width)
 ->assign('thumbnail_height',$thumbnail_height)
 ->assign('allow_browser_cache',$allow_browser_cache)
 ->assign('browser_cache_expiry',$browser_cache_expiry)
 ->assign('auto_clear_cache_age',$auto_clear_cache_age)
 ->assign('adminlog_lifetime',$adminlog_lifetime)
 ->assign('search_module',$search_module)
 ->assign('SmartyAdmincacheLife',$SmartyAdmincacheLife)
 ->assign('SmartyFrontcacheLife',$SmartyFrontcacheLife)
 ->assign('use_smartycache',$use_smartycache)
 ->assign('use_smartycompilecheck',$use_smartycompilecheck);
if( $devmode ) {
  $tpl->assign('privatePath',$ppath);
}

$tmp = [
  86400=>lang('adminlog_1day'),
  86400*7=>lang('adminlog_1week'),
  86400*14=>lang('adminlog_2weeks'),
  86400*31=>lang('adminlog_1month'),
  86400*91=>lang('adminlog_3months'),
  86400*182=>lang('adminlog_6months'),
  -1=>lang('adminlog_manual')
];
$tpl->assign('adminlog_options',$tmp);
$tpl->assign('smarty_cacheoptions',['always'=>lang('always'),'never'=>lang('never'),'moduledecides'=>lang('moduledecides')]);
$tpl->assign('smarty_cacheoptions2',['always'=>lang('always'),'never'=>lang('never')]);
$tpl->assign('yesno',[0=>lang('no'),1=>lang('yes')]);
$tpl->assign('titlemenu',[0=>lang('menutext'),1=>lang('title')]);
$tpl->assign('backurl',$themeObject->backUrl());
$tpl->assign('formurl',basename(__FILE__).$urlext);

$tpl->display();

require_once 'footer.php';
