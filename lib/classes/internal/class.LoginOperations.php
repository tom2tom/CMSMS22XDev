<?php
#Class: CMSMS\internal\LoginOperations
#(c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

namespace CMSMS\internal;

use cms_config;
use cms_cookies;
use cms_siteprefs;
use cms_url;
use cms_userprefs;
use CmsAdminUtils;
use CmsApp;
use CmsLoginError;
use CMSMS\HookManager;
use LogicException;
use User;
use UserOperations;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use const CMS_VERSION;
use function audit;
use function redirect;

final class LoginOperations
{
    private static $_instance;
    private $_loginkey;
    private $_data;

    private function __construct()
    {
        $this->_loginkey = sha1(CMS_VERSION.$this->_get_salt());
    }

    public static function get_instance()
    {
        if( !self::$_instance ) self::$_instance = new self();
        return self::$_instance;
    }

    public function deauthenticate()
    {
        cms_cookies::erase($this->_loginkey);
//      cms_cookies::erase(CMS_USER_KEY); never used
        cms_cookies::erase(CMS_SECURE_PARAM_NAME);
        unset($_SESSION[$this->_loginkey],$_SESSION[CMS_USER_KEY]);
    }

    private function _get_salt()
    {
        // if we do not have a presaved salt.. we generate one
        $salt = cms_siteprefs::get(__CLASS__);
        if( !$salt ) {
            $salt = sha1(mt_rand().__FILE__.mt_rand().time());
            cms_siteprefs::set(__CLASS__,$salt);
        }
        return $salt;
    }

    private function _check_passhash($uid,$checksum)
    {
        if( !$checksum ) return FALSE;
        // we already validated that payload was not corrupt
        // now we validate that the user is valid.
        $userops = UserOperations::get_instance();
        $user = $userops->LoadUserByID((int) $uid);
        if( !$user ) return FALSE;
        if( !$user->active ) return FALSE;

        return password_verify($user->id.$user->password.basename(__FILE__),(string)$checksum);
    }

/* vanilla 2.2.23 added some replacement methods presumably to support staging for MFA:
    public function initialize_authentication(User $user, /*User * /$effective_user = null)
    public function finalize_authentication(User $user, /*User * /$effective_user = null)
    protected function _persist_authentication(User $user, /*User * /$effective_user = null)
*/
    public function preserve_user($user)
    {
        $key = $this->userkey();
        $data = cms_siteprefs::get('fuscint');
        if( $data ) {
            [$prime, $xorer] = explode('.', $data);
            $_SESSION[$key] = $this->encode($user->id, (int)$prime, (int)$xorer);
        } else {
            unset($_SESSION[$key]); // if any
            throw new CmsLoginError('Internal error : missing data for user id recovery');
        }
    }

    public function retrieve_user()
    {
        $key = $this->userkey();
        if( !empty($_SESSION[$key]) ) {
            $data = cms_siteprefs::get('fuscint');
            if( $data ) {
                [$prime, $xorer] = explode('.', $data);
                $uid = $this->decode((int)$_SESSION[$key], $inverse, $xorer);
                if( $uid > 0 ) {
                    $userops = UserOperations::get_instance();
                    return $userops->LoadUserByID($uid);
                }
            }
        }
        return null;
    }

    public function initialize_authentication(User $user)
    {
        $this->save_authentication($user);
        // send a post-pw-check event
        HookManager::do_hook('Core::LoginPassed', ['user'=>$user]);
        // if a LoginPassed handler doesn't return here, it must locally perform the following, to resume login
        // $user = $login_ops->retrieve_user(); if ($user) { $login_ops->finalize_authentication($user); }

        $this->finalize_authentication($user);
    }

    // Save session/cookie data
    // return string
    public function save_authentication(User $user, /*?User */$effective_user = null) // no object uncomment for PHP 7.1+ .. 8.4+
    {
        if( $user->id < 1 || empty($user->password) ) throw new LogicException('User information invalid for '.__METHOD__);

        $private_data = [
            'uid' => $user->id,
            'username' => $user->username,
            'eff_uid' => 0,
            'eff_username' => ''
        ];
        $private_data['hash'] = password_hash($user->id.$user->password.basename(__FILE__),PASSWORD_BCRYPT); //this is used like a password
        if( $effective_user && $effective_user->id > 0 && $effective_user->id != $user->id ) {
            $private_data['eff_uid'] = $effective_user->id;
            $private_data['eff_username'] = $effective_user->username;
        }
        $enc = base64_encode(json_encode($private_data));
        $hash = sha1($this->_get_salt() . $enc);
        $_SESSION[$this->_loginkey] = $hash.'::'.$enc;
        cms_cookies::set($this->_loginkey,$_SESSION[$this->_loginkey]);

        // CSRF stuff
        $key = substr(str_shuffle(sha1(__DIR__.$user->id.time().session_id())),-19);
        $_SESSION[CMS_USER_KEY] = $key;
        cms_cookies::set(CMS_SECURE_PARAM_NAME,$key);
        unset($this->_data);
        return $key;
    }

    public function finalize_authentication(User $user)
    {
        // put mention into the admin log
        audit($user->id, 'Admin user', 'Logged in');

        // send a post-login event
        HookManager::do_hook('Core::LoginPost', ['user'=>$user]);

        $key = $this->userkey();
        unset($_SESSION[$key]);

        // redirect outa here somewhere
        if( isset($_SESSION['login_redirect_to']) ) {
            // we previously attempted an URL but didn't have the user key in the request.
            $url_ob = new cms_url($_SESSION['login_redirect_to']);
            unset($_SESSION['login_redirect_to']);
            $url_ob->erase_queryvar('_s_');
            $url_ob->erase_queryvar('sp_');
            $url_ob->set_queryvar(CMS_SECURE_PARAM_NAME,$_SESSION[CMS_USER_KEY]);
            $url = (string) $url_ob;
            redirect($url);
        }
        else {
            // find the user's homepage, if any, and redirect there.
            $homepage = cms_userprefs::get_for_user($user->id,'homepage');
            if( !$homepage ) {
                $config = cms_config::get_instance();
                $homepage = $config['admin_url'];
            }
/*          elseif( 0 ) {
                url should be rel. to $config['admin_url']
                //TODO somewhere, efficiently check page ok (FR#12400)
                //e.g. after each system upgrade and after each module-uninstall or -deactivate
                $config = cms_config::get_instance();
                $homepage = $config['admin_url'];
            }
*/
            $homepage = CmsAdminUtils::get_session_url($homepage); // involves deprecated conversion of 'placeholders'. instead use verbatim
            $homepage = html_entity_decode($homepage);
            redirect($homepage);
        }
    }

    private function userkey()
    {
        return hash('md4', CMS_VERSION . $this->_get_salt() . __FILE__);
    }

    private function encode($value, $prime, $xorer)
    {
        $mask = (1 << 16) - 1; // handle ints up to 65535
        return (($value * $prime) & $mask) ^ $xorer;
    }

    private function decode($value, $prime, $xorer)
    {
        $mask = (1 << 16) - 1; // handle ints up to 65535
        return (($value ^ $xorer) / $prime) & $mask;
    }

    private function _get_data()
    {
        if( !empty($this->_data) ) {
            return $this->_data;
        }
        // using session, and-or cookie data see if we are authenticated
        if( isset($_SESSION[$this->_loginkey]) ) {
            $private_data = $_SESSION[$this->_loginkey];
        }
        else {
            $private_data = [];
            if( isset($_COOKIE[$this->_loginkey]) ) {
                $private_data = $_SESSION[$this->_loginkey] = $_COOKIE[$this->_loginkey];
            }
        }

        if( !$private_data ) return [];
        $parts = explode('::',$private_data,2);
        if( count($parts) != 2 ) return [];

        if( $parts[0] != sha1($this->_get_salt() . $parts[1]) ) return []; // payload corrupted.
        $private_data = json_decode(base64_decode($parts[1]),TRUE);

        if( !is_array($private_data) ) return [];
        if( empty($private_data['uid']) ) return [];
        if( empty($private_data['username']) ) return [];
        if( empty($private_data['hash']) ) return [];

        // authenticate the passhash (requires a database query)
        if( !(CmsApp::get_instance()->is_frontend_request() || // should never happen, here
              $this->_check_passhash($private_data['uid'],$private_data['hash'])) ) {
            return [];
        }

        // if we get here, the user is authenticated.
        // set the session key from the cookie if it exists.
        if( !isset($_SESSION[CMS_USER_KEY]) ) {
            if( cms_cookies::exists(CMS_SECURE_PARAM_NAME) ) {
                $_SESSION[CMS_USER_KEY] = cms_cookies::get(CMS_SECURE_PARAM_NAME);
            }
        }

        $this->_data = $private_data;
        return $this->_data;
    }

    public function validate_requestkey()
    {
        // check that the session includes the user key.
        if( !isset($_SESSION[CMS_USER_KEY]) ) throw new LogicException('Internal error: User key not found in session.');
        // check GET and POST vars in case $_REQUEST also contains cookie values.
        $v = '<no$!tgonna!$happen>';
        if( isset($_GET[CMS_SECURE_PARAM_NAME]) ) $v = $_GET[CMS_SECURE_PARAM_NAME];
        if( isset($_POST[CMS_SECURE_PARAM_NAME]) ) $v = $_POST[CMS_SECURE_PARAM_NAME];
        // check the key in the request against the key in the session.
        if( $v != $_SESSION[CMS_USER_KEY] ) {
            $config = cms_config::get_instance();
            if( !isset($config['stupidly_ignore_xss_vulnerability']) ) return FALSE;
        }
        return TRUE;
    }

    public function get_loggedin_uid()
    {
        $data = $this->_get_data();
        if( !$data ) return 0;
        return (int) $data['uid'];
    }

    public function get_loggedin_username()
    {
        $data = $this->_get_data();
        if( !$data ) return '';
        return trim($data['username']);
    }

    public function get_loggedin_user()
    {
        $uid = $this->get_loggedin_uid();
        if( $uid < 1 ) return null;
        $user = UserOperations::get_instance()->LoadUserByID($uid);
        return $user;
    }

    public function get_effective_uid()
    {
        $data = $this->_get_data();
        if( !$data ) return 0;
        if( isset($data['eff_uid']) && $data['eff_uid'] > 0 ) return $data['eff_uid'];
        return $data['uid'];
    }

    public function get_effective_username()
    {
        $data = $this->_get_data();
        if( !$data ) return '';
        if( isset($data['eff_username']) && $data['eff_username'] ) return trim($data['eff_username']);
        return trim($data['username']);
    }

    public function set_effective_user(/*?User */$e_user = null) // no object uncomment for PHP 7.1+ .. 8.4+
    {
        $li_user = $this->get_loggedin_user();
        if( $e_user && $e_user->id == $li_user->id ) return '';

        $new_key = $this->save_authentication($li_user,$e_user);
        return $new_key;
    }
}
