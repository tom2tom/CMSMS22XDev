<?php
#FileManager module action
#(c) 2006-8 Morten Poulsen <morten@poulsen.org>
#(c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

if( !function_exists('cmsms') ) exit;
if( !($this->CheckPermission('Modify Files') || $this->AdvancedAccessAllowed()) ) exit;

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;filemanager.tpl", null, $modname, $smarty);

if( !empty($params['newsort']) ) {
  $sortby = $params['newsort'];
}
elseif( !empty($_SESSION['FMnewsortby']) ) {
  $sortby = $_SESSION['FMnewsortby'];
}
else {
  $sortby = 'namedesc'; //hence start with nameasc
}
//TODO support sorting also on mime, date (here and backend)
foreach( ['name','size'] as $prefix ) { //,'type','date'
  $titlelink = $this->Lang('file'.$prefix);
  $sortparm = $prefix.'asc';
  if( $sortby == $sortparm ) {
    $sortparm = $newsort = $prefix.'desc';
    $titlelink .= ' &#9652;'; //up triangle (in some fonts)
  }
  elseif( $sortby == $prefix.'desc' ) {
    $newsort = $sortparm;
    $titlelink .= ' &#9662;'; //down triangle
  }
  else {
    $titlelink .= ' &#9671;'; //diamond
  }
  //sort-list links don't want most or all supplied $params
  //TODO any of them need pass thru via link urls?
  $titlelink = $this->CreateLink($id, 'defaultadmin', $returnid, $titlelink, ['newsort' => $sortparm]);
  $tpl->assign('file'.$prefix.'text', $titlelink);
}

$devmode = !empty($config['developer_mode']);
$baseurl = $this->GetModuleURLPath();
$iconsize = $this->GetPreference('iconsize',24);
$iconsize = str_replace('px','',$iconsize); //adjust deprecated format
$emsize = $iconsize / 16;
$countdirs = 0;
$countfiles = 0;
$countfilesize = 0;
$files = [];
$path = filemanager_utils::get_cwd();
$filelist = filemanager_utils::get_file_list($path);

for( $i = 0, $n = count($filelist); $i < $n; $i++ ) {
  $itmname = $filelist[$i]['name'];

  $onerow = new stdClass();
  $onerow->name = $itmname;
  $onerow->urlname = $this->encodefilename($itmname);
  if( isset($filelist[$i]['url']) ) {
    $onerow->url = $filelist[$i]['url'];
  }
  $onerow->mime = $filelist[$i]['mime'];
  if( isset($params[$onerow->urlname]) ) {
    $onerow->checked = true;
  }
  $onerow->editor = ''; //never changed here

  if( $filelist[$i]['dir'] ) {
    $urlname = 'dir_' . $onerow->urlname;
    $value = (isset($params[$urlname])) ? 'true' : '';
    $onerow->checkbox = $this->CreateInputCheckBox($id, $urlname , 'true', $value);

    $parms = ['newdir' => $itmname, 'path' => $path, 'sortby' => $sortby];
    $onerow->iconlink = $this->CreateLink($id, 'changedir',  '', $this->GetFileIcon($filelist[$i]['ext'], $filelist[$i]['dir']), $parms);
    $url = $this->create_url($id, 'changedir', '', $parms);

    if( $itmname != '..' ) {
      $countdirs++;
      $onerow->type = ['dir'];
      $onerow->txtlink = "<a class=\"dirlink\" href=\"$url\" title=\"{$this->Lang('title_changedir')}\">$itmname</a>";
      $onerow->filedate = $filelist[$i]['date']; //template uses {$file->filedate|cms_date_format}
    }
    else {
      // for accessing the parent directory
      $onerow->type = []; // need something
      $onerow->noCheckbox = 1;
      $img_tag = '<img src="'.$baseurl.'/icons/themes/default/actions/dir_up.png" style="height:'.$emsize.'em;vertical-align:middle;border:0" title="'.$this->Lang('title_changeupdir').'">';
      $onerow->iconlink = $this->CreateLink($id, 'changedir', '', $img_tag, $parms);
      $onerow->txtlink = "<a class=\"dirlink\" href=\"$url\" style=\"vertical-align:super\" title=\"{$this->Lang('title_changeupdir')}\">$itmname</a>";
      $onerow->fileaction = '&nbsp;';
      $onerow->filepermissions = '&nbsp;';
      $onerow->filedate = '';
    }
    $onerow->filesize = '&nbsp;';
  }
  else { // not a dir
    $urlname = 'file_' . $onerow->urlname;
    $value = (isset($params[$urlname])) ? 'true' : '';
    $onerow->checkbox = $this->CreateInputCheckBox($id, $urlname, 'true', $value);

    $countfiles++;
    $countfilesize += $filelist[$i]['size'];
//  $url = $this->create_url($id, 'view', '', array('file' => $this->encodefilename($itmname)));
    if( isset($onerow->url) && ($devmode || !$filelist[$i]['exec']) ) {
      $onerow->iconlink = '<a href="' . $onerow->url . '" target="_blank">' . $this->GetFileIcon($filelist[$i]['ext']) . '</a>';
//    $onerow->txtlink = "<a href='" . $filelist[$i]['url'] . "' target='_blank' title=\"".$this->Lang('title_view_newwindow')."\">" . $itmname . "</a>";
      $onerow->txtlink = '<a class="filelink" href="' . $onerow->url . '" target="_blank" title="' . $this->Lang('title_view_newwindow') . '">' . $itmname . '</a>';
    }
    else {
      $onerow->iconlink = $this->GetFileIcon($filelist[$i]['ext']);
      $onerow->txtlink = $itmname;
      $onerow->noCheckbox = true; //prevent any button-initiated operation
    }
    $filesize = filemanager_utils::format_filesize($filelist[$i]['size']);
    $onerow->filesize = $filesize['size'];
    $onerow->filesizeunit = $filesize['unit'];

    $onerow->thumbnail = '';
    $onerow->filedate = $filelist[$i]['date'];

    $onerow->type = ['file'];
    if( strpos($onerow->mime,'text') !== FALSE ) {
      $onerow->type[] = 'text';
    }
    elseif( $filelist[$i]['image'] ) {
      $onerow->type[] = 'image';
      $params['imagesrc'] = $path.'/'.$itmname; //TODO all url-format seps? strtr($path, '\\','/')
      if( $this->GetPreference('showthumbnails', 0) ) {
        $onerow->thumbnail = $this->GetThumbnailLink($filelist[$i], $path);
      }
    }
    elseif( $filelist[$i]['archive'] ) {
      $onerow->type[] = 'archive';
    }
  }

  $onerow->fileinfo = trim($filelist[$i]['fileinfo']);
  if( $itmname != '..' ) {
    $onerow->fileowner = $filelist[$i]['fileowner'];
    $onerow->filepermissions = $filelist[$i]['permissions'];
  }
  $files[] = $onerow;
}

if( !empty($params['viewfile']) ) {
  foreach( $files as $file ) {
    if( $file->urlname == $params['viewfile'] ) {
      $fn = filemanager_utils::join_path(filemanager_utils::get_full_cwd(),$file->name);
      if( in_array('text',$file->type) ) {
        if( file_exists($fn) ) {
          $data = @file_get_contents($fn);
          if( $data ) {
            $data = cms_htmlentities($data); //TODO robust sanitisation
            $data = nl2br($data);
          }
          echo $data;
          exit;
        }
        //error report then exit ?
      }
      elseif( in_array('image', $file->type) ) {
        $data = '<img src="'.$file->url.'" alt="'.$file->name.'">';
        echo $data;
        exit;
      }
    }
  }
}

// build display

$tpl->assign('path', $path);
$tpl->assign('files', $files);
$tpl->assign('itemcount', count($files));
if( $countfiles > 0 || $countdirs > 0 ) {
  $totalsize = filemanager_utils::format_filesize($countfilesize);
/*
  $counts = $totalsize['size'] . ' ' . $totalsize['unit'] . ' ' . $this->Lang('in') . ' ' . $countfiles . ' ';
  if( $countfiles == 1 ) { $counts .= $this->Lang('file'); }
  else { $counts .= $this->Lang('files'); }
  $counts .= ' ' . $this->Lang('and') . ' ' . $countdirs . ' ';
  if( $countdirs == 1 ) { $counts .= $this->Lang('subdir'); }
  else { $counts .= $this->Lang('subdirs'); }
*/
  //display summary like [A] [kb] in [B] [file|s] and [C] [subdirector|ies]
  $key1 = ($countfiles == 1) ? 'file' : 'files';
  $key2 = ($countdirs == 1) ? 'subdir' : 'subdirs';
  $counts = $this->Lang('summary', $totalsize['size'], $totalsize['unit'], $countfiles, $this->Lang($key1), $countdirs, $this->Lang($key2));
}
else {
  $counts = $this->Lang('emptydirectory');
}
$tpl->assign('countstext', $counts);
$tpl->assign('filedatetext', $this->Lang('filedate')); //TODO make this or its column clickable for sorting
$tpl->assign('fileinfotext', $this->Lang('fileinfo'));
$tpl->assign('fileownertext', $this->Lang('fileowner'));
$tpl->assign('filepermstext', $this->Lang('fileperms'));
$tpl->assign('filesizetext', $this->Lang('filesize')); //TODO make this or its column clickable for sorting
$tpl->assign('filetypetext', $this->Lang('mimetype')); //TODO make this or its column clickable for sorting
$tpl->assign('actionstext', $this->Lang('actions'));
//$tpl->assign('confirm_unpack', $this->Lang('confirm_unpack'));
if( empty($params['noform']) ) {
  $tpl->assign('vformstart', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', ['newsort' => $newsort, 'path' => $path]));
  $tpl->assign('formend', $this->CreateFormEnd());
  $url = $this->Create_url($id, 'admin_fileview', '', ['noform' => 1]);
  $tpl->assign('refresh_url', str_replace('&amp;', '&', $url)); //.'&showtemplate=false'
  $url = $this->create_url($id, 'admin_fileview', '', ['viewfile' => 1]); //was 'ajax' ?
  $tpl->assign('viewfile_url', str_replace('&amp;', '&', $url));
  $tpl->display();
}
else { //doing an ajax refresh
  $tpl->assign('noform', 1); // generate only the files-table
  $n = count(ob_list_handlers());
  for( $i = 0; $i < $n; $i++ ) { ob_end_clean(); }
  $tpl->display();
  exit;
}
