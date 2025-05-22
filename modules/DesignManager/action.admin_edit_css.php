<?php
#-------------------------------------------------------------------------
# Module DesignManager action
# (c) 2012 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
#-------------------------------------------------------------------------

if( !isset($gCms) ) exit ;
if( !$this->CheckPermission('Manage Stylesheets') ) return;

$this->SetCurrentTab('stylesheets');

if( isset($params['cancel']) ) {
    $this->SetMessage($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

$apply = isset($params['apply']);
$extraparms = [];
$message = $this->Lang('msg_stylesheet_saved');
$response = 'success';
$userid = get_userid();
$ssid = (int)get_parameter_value($params,'css');
try {
    if( $ssid > 0 ) {
        $css_ob = CmsLayoutStylesheet::load($ssid); // might throw
        $extraparms['css'] = $ssid;
    }
    else {
        $css_ob = new CmsLayoutStylesheet();
    }

    try {
        if( isset($params['submit']) || $apply && $response !== 'error' ) {
            if( isset($params['name']) ) $css_ob->set_name($params['name']);
            if( isset($params['description']) ) $css_ob->set_description($params['description']);
            if( isset($params['content']) ) $css_ob->set_content($params['content']);
            $mtyp = [];
            if( isset($params['media_type']) ) $mtyp = $params['media_type'];
            $css_ob->set_media_types($mtyp);
            if( isset($params['media_query']) ) $css_ob->set_media_query($params['media_query']);
            if( $this->CheckPermission('Manage Designs') ) {
                $design_list = [];
                if( isset($params['design_list']) ) $design_list = $params['design_list'];
                $css_ob->set_designs($design_list);
            }
            $css_ob->save();

            if( !$apply ) {
                $this->SetMessage($message);
                $this->RedirectToAdminTab();
            }
        }
        elseif( isset($params['export']) ) {
            $outfile = $css_ob->get_content_filename();
            $dn = dirname($outfile);
            if( !is_dir($dn) || !is_writable($dn) ) {
                throw new RuntimeException($this->Lang('error_assets_writeperm'));
            }
            if( is_file($outfile) && !is_writable($outfile) ) {
                throw new RuntimeException($this->Lang('error_assets_writeperm'));
            }
            file_put_contents($outfile,$css_ob->get_content());
        }
        elseif( isset($params['import']) ) {
            $infile = $css_ob->get_content_filename();
            if( !is_file($infile) || !is_readable($infile) || !is_writable($infile) ) {
                throw new RuntimeException($this->Lang('error_assets_readwriteperm'));
            }
            $data = file_get_contents($infile);
            unlink($infile);
            $css_ob->set_content($data);
            $css_ob->save();
        }
    } catch( Exception $e ) {
        $message = $e->GetMessage();
        $response = 'error';
    }

    $ssid = $css_ob->get_id();
    if( $ssid > 0 && !$apply && dm_utils::locking_enabled() ) {
        try {
            $lock_id = CmsLockOperations::is_locked('stylesheet', $ssid);
            if( $lock_id > 0 ) {
                $lock = CmsLock::load('stylesheet', $ssid);
                if( $lock['uid'] == $userid || $lock->expired() ) {
                    // remove it, ready to start again
                    CmsLockOperations::unlock($lock_id, 'stylesheet', $ssid);
                }
                else {
                    // it's owned by somebody else
                    throw new CmsLockException('CMSEX_L010');
                }
            }
        } catch( CmsException $e ) {
            $response = 'error';
            $message = $e->GetMessage();
            if( !$apply ) {
                $this->SetError($message);
                $this->RedirectToAdminTab();
            }
        }
    }

    // handle the response message
    if( $apply ) {
        $this->GetJSONResponse($response, $message); //TODO any additional data for frontend
    }
    elseif( $response == 'error' ) {
        $this->ShowErrors($message);
    }

    //
    // prepare to display
    //
    $modname = $this->GetName();
    $tpl = $smarty->CreateTemplate("module_file_tpl:$modname;admin_edit_css.tpl", null, $modname, $smarty);

    $designs = CmsLayoutCollection::get_all();
    if( $designs ) {
        $tmp = [];
        foreach( $designs as $one ) {
            $tmp[$one->get_id()] = $one->get_name();
        }
        $tpl->assign('design_list', $tmp);
    }

    if( $ssid > 0 ) {
        CmsAdminThemeBase::GetThemeObject()->SetSubTitle($this->Lang('edit_stylesheet').': '.$css_ob->get_name()." ($ssid)");
    }
    else {
        CmsAdminThemeBase::GetThemeObject()->SetSubTitle($this->Lang('new_stylesheet'));
    }
    $extraparms['__activetab'] = 'stylesheeets';

    $tpl->assign('has_designs_right', $this->CheckPermission('Manage Designs'));
    $tpl->assign('extraparms', $extraparms);
    $tpl->assign('css', $css_ob);
    $tpl->assign('css_id', $ssid);
    $tpl->assign('userid', $userid);
    $tpl->assign('lock_timeout', $this->GetPreference('lock_timeout'));
    $tpl->assign('lock_refresh', $this->GetPreference('lock_refresh'));

    $tpl->display();
} catch( CmsException $e ) {
    $this->SetError($e->GetMessage());
    $this->RedirectToAdminTab();
}
