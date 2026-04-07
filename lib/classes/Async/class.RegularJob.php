<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: CMSMS\Async\RegularJob
# (c) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program. If not, read the license online at:
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
#END_LICENSE

namespace CMSMS\Async;

use CmsRegularTask;
use LogicException;

/**
 * A RegularJob wraps a CmsRegularTask pseudocron Task in an
 * asynchronous background Job.
 *
 * @package CMS
 * @since 2.2.23F2
 * @property CmsRegularTask $_task The Task to be adapted
 */
class RegularJob extends Job
{
    /**
     * @val CmsRegularTask
     * @ignore
     */
    private $_task;

    /**
     * Constructor.
     *
     * @param CmsRegularTask $task
     */
    public function __construct(CmsRegularTask $task)
    {
        if( !$task ) {
            throw new LogicException('No task provided to RegularJob');
        }
        parent::__construct();
        $this->_task = $task;
        $this->name = $task->get_name();
        $this->props['recurs'] = $task->get_frequency();
     }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __get($key)
    {
        switch( $key ) {
        case 'task':
            return $this->_task;
        case 'description':
            return !empty($this->props['description']) ?
            $this->props['description'] :
            $this->_task->get_description();
        case 'displayrecr':
        case 'until':
            return $this->props[$key];
        default:
            return parent::__get($key);
        }
    }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __set($key,$val)
    {
        switch( $key ) {
        case 'task':
            if( !$val instanceof CmsRegularTask ) throw new LogicException('Invalid value for '.$key.' in a '.__CLASS__);
            if( $this->_task ) {
                if( $this->props['id'] > 0 ) {
                    $this->delete(); // clear recorded data, start again
                }
            }
            $this->_task = $val;
            $this->name = $val->get_name();
            $this->props['recurs'] = $val->get_frequency();
            break;
        case 'displayrecr':
        case 'until':
            $this->props[$key] = $val;
            break;
        default:
            parent::__set($key,$val);
        }
    }

    /**
     * Get the expected inter-execution interval for this task.
     * @since 2.2.23F2
     * @abstract
     *
     * @return string a RECUR_* constant (possibly to be substituted
     *  when appropriate by the 'displayrecr' property e.g.
     * 'Every full moon' or 'Not on your life!')
     *  or empty as another indicator of no-recurrence
     */
    public function get_frequency()
    {
        if( method_exists($this->_task,'get_frequency') ) {
            return $this->_task->get_frequency();
        }
        return $this->recurs;
    }

    /**
     * Get the 'non-standard' variant of the expected inter-execution
     * interval for this task.
     * @since 2.2.23F2
     *
     * @return string
     */
    public function get_custom_frequency()
    {
        if( method_exists($this->_task,'get_custom_frequency') ) {
            return $this->_task->get_custom_frequency(time());
        }
        if( $this->displayrecr ) {
            return $this->displayrecr;
        }
        $val = $this->get_frequency;
        if( !$val || $val == Job::RECUR_NONE || $val == Job::RECUR_NONE_CUSTOM ) {
            return '';
        }
        return 'Custom';
    }

    /**
     * @ignore
     * @param int $now Optional timestamp Default 0
     * return bool
     */
    public function execute($now = 0)
    {
        if( property_exists($this->_task,'id') ) {
            $this->_task->id = $this->id; // use the correct job id for cached properties
        }
        if( $now == 0 ) { $now = time(); }
        if( !$this->_task->test($now) ) {
            return false;
        }
        $this->force_start = $now; // update even if execution fails
        $this->record_exec_time($now);
        if( $this->_task->execute($now) ) {
            $this->_task->on_success($now);
            return true;
        } else {
            $this->errors++;
            $this->_task->on_failure($now);
            return false;
        }
    }
}
