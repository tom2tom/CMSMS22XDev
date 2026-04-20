<?php
# Class: CMSMS\Async\ConditionalJob
# (C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs

namespace CMSMS\Async;

/**
 * An abstract base class for a ConditionalJob.
 * A ConditionalJob extends a Job to enable self-management of its own recurrence.
 * Which makes it an effective mechanism to deal with context changes e.g.
 * site-preferences (or perhaps Events or Hooks?)
 *
 * @package CMS
 * @since 2.2.23F2
 */
abstract class ConditionalJob extends Job
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->props['testrecurs'] = null;
    }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __get($key)
    {
        switch( $key ) {
        case 'recurs':
            return $this->props['recurs']; // to be used for display-only
        case 'testrecurs':
            return $this->props['testrecurs']; // normally superseded by testRecur()
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
        case 'testrecurs':
            $this->props['testrecurs'] = $val;
            break;
        default:
            parent::__set($key,$val);
        }
    }

    /**
     * Report whether this job should be executed now.
     * Also make and save any relevant property-changes.
     * @abstract
     *
     * @param int $lastexec latest-execution timestamp of the job
     * @param int $time Optional this-execution timestamp Default 0
     * @return bool
     */
    public function testRecur($lastexec,$time=0)
    {
        // perform relevant tests here
        // make and save relevant changes here
        return true;
    }

    /**
     * Get the next start for this job.
     * Possibly 0 or later than current to indicate 'don't execute now'.
     * @abstract
     *
     * @param int $lastexec latest-execution timestamp of the job
     * @param int $time Optional this-execution timestamp Default 0
     * @return int timestamp
     */
    public function testStart($lastexec,$time=0)
    {
        return time();
    }
}
