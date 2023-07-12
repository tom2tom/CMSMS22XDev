<?php
#Plugin handler: cms_admin_user
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

function smarty_function_cms_admin_user($params,$template)
{
  $smarty = $template->smarty;
  $out = '';

  if( cmsms()->test_state(CmsApp::STATE_ADMIN_PAGE) ) {
      $uid = (int)get_parameter_value($params,'uid');
      if( $uid > 0 ) {
          $user = UserOperations::get_instance()->LoadUserByID((int)$params['uid']);
          if( is_object($user) ) {
              $mode = trim(get_parameter_value($params,'mode','username'));
              switch( $mode ) {
              case 'username':
                  $out = $user->username;
                  break;
              case 'email':
                  $out = $user->email;
                  break;
              case 'firstname':
                  $out = $user->firstname;
                  break;
              case 'lastname':
                  $out = $user->lastname;
                  break;
              case 'fullname':
                  $out = "{$user->firstname} {$user->lastname}";
                  break;
              }
          }
      }
  }

  if( isset($params['assign']) ) {
    $smarty->assign($params['assign'],$out);
    return '';
  }
  return $out;
}

?>
