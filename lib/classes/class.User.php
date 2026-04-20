<?php
#CMS Made Simple class User
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

/**
 * Generic admin user class.  This can be used for any logged in user or user related function.
 *
 * @package CMS
 * @since 0.6.1
 * @license GPL
 */
class User
{

	/**
	 * @var int $id User id
	 */
	public $id;

	/**
	 * @var string Username
	 */
	public $username;

	/**
	 * @var string $password Password Hash
	 */
	public $password;

	/**
	 * @var string $firstname User's First Name
	 */
	public $firstname;

	/**
	 * @var string $lastname Last Name
	 */
	public $lastname;

	/**
	 * @var string $email User's Email Address
	 */
	public $email;

	/**
	 * @var bool $active Active Flag
	 */
	public $active;

	/**
	 * @var bool $adminaccess Flag whether the user can log in to admin panel
	 */
	public $adminaccess;

	/**
	 * Generic constructor.  Runs the SetInitialValues method.
	 */
	function __construct()
	{
		$this->SetInitialValues();
	}

	/**
	 * Sets object to some sane initial values
	 *
	 * @since 0.6.1
	 */
	function SetInitialValues()
	{
		$this->id = -1;
		$this->username = '';
		$this->password = '';
		$this->firstname = '';
		$this->lastname = '';
		$this->email = '';
		$this->active = false;
		$this->adminaccess = false;
	}

	/**
	 * Hash and cache the password for this User
	 *
	 * @since 0.6.1
	 * @param string $password The plaintext password.
	 */
	function SetPassword($password)
	{
		$this->password = password_hash($password, PASSWORD_BCRYPT); //PASSWORD_ARGON2I or PASSWORD_ARGON2ID might be relevant in future
	}

	/**
	 * Save this User to the database.  If no user_id property is set, then a new record
	 * is created.  If user_id is set, then the record is updated.
	 *
	 * @return bool indicating success.
	 * @since 0.6.1
	 */
	function Save()
	{
		$result = false;

		$userops = UserOperations::get_instance();
		if ($this->id > -1) {
			$result = $userops->UpdateUser($this);
		}
		else {
			$newid = $userops->InsertUser($this);
			if ($newid > -1) {
				$this->id = $newid;
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Delete the record for this User from the database and reset
	 * all object properties to their initial values.
	 *
	 * @return bool indicating success.
	 * @since 0.6.1
	 */
	function Delete()
	{
		$result = false;
		if ($this->id > -1) {
			$userops = UserOperations::get_instance();
			$result = $userops->DeleteUserByID($this->id);
			if ($result) $this->SetInitialValues();
		}
		return $result;
	}
}

?>
