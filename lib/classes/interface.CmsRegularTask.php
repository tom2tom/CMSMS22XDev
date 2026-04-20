<?php
#Interface: CmsRegularTask
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

/**
 * An interface to define how Tasks should work
 * @deprecated since 2.2.23F2 instead use Jobs and Job-subclasses
 * @package CMS
 * @license GPL
 * @since 1.8
 */
interface CmsRegularTask
{
 /**
  * For effective timestamp recording, the object needs to have an 
  * id property to record the $id of the 'wrapper' RegularJob
  * @since 2.2.23F2
  */
//public int $id; // PHP 8.4+


  /**
   * Get the name of the Task
   *
   * @return string
   */
  public function get_name();


  /**
   * Get the description of the Task
   *
   * @return string
   */
  public function get_description();


  /**
   * Test whether the Task should be executed
   *
   * @param mixed $time The timestamp representing task execution time
   *   to be used during the test. Assume the current time if falsy.
   * @return bool TRUE if the task should be executed, FALSE otherwise.
   */
  public function test($time = '');


  /**
   * Execute the Task
   *
   * @param mixed $time The timestamp representing when the task is being executed.
   *   Assume the current time if falsy.
   * @return bool TRUE on success, FALSE otherwise.
   */
  public function execute($time = '');


  /**
   * Do things consequent on successful execution of the Task.
   * This method is called after execute() returns TRUE.
   *
   * @param mixed $time The timestamp representing when the task was executed.
   *   Assume the current time if falsy.
   */
  public function on_success($time = '');


  /**
   * Do things consequent on failure of the Task.
   * This method is called after execute() returns FALSE.
   *
   * @param int $time The timestamp representing when the task was executed.
   *   Assume the current time if empty.
   */
  public function on_failure($time = '');

} // interface

?>
