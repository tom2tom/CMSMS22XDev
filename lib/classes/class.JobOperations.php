<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: CMSMS\JobOperations
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
#END_LICENSE

namespace CMSMS;

/**
 * A class of static methods for managing asynchronous jobs.
 *
 * This is a simple proxy for methods in the CmsJobManager module.
 *
 * @package CMS
 * @author Robert Campbell
 * @since 2.2
 */
final class JobOperations
{
    /**
     * @ignore
     */
    const MANAGER_MODULE = 'CmsJobManager';

    /**
     * Trigger asynchronous processing.
     *
     * @internal
     * @return void
     */
    public function trigger_async_processing()
    {
        $mod = $this->get_mod();
        if( $mod ) $mod->trigger_async_processing();
    }

    /**
     * Given an integer job id, load the job.
     *
     * @param int $job_id
     * @return Job
     */
    public function load_job( $job_id )
    {
        $mod = $this->get_mod();
        if( $mod ) return $mod->load_job_by_id( $job_id );
        return null; // no object
    }

    /**
     * Save a job to the queue.
     *
     * @param Job $job
     * @return int The id of the job.
     */
    public function save_job( Job $job )
    {
        $mod = $this->get_mod();
        if( $mod ) return $mod->save_job($job);
        return 0; // no job
    }

    /**
     * Remove a job from the queue
     *
     * Note: After calling this method, the job object itself is invalid and cannot be saved.
     *
     * @param Job $job
     * @return void
     */
    public function delete_job( Job $job )
    {
        $mod = $this->get_mod();
        if( $mod ) $mod->delete_job($job);
    }

    /**
     * Remove all of the jobs originating from a specific module
     *
     * @param string $module_name
     * @return void
     */
    public function delete_jobs_by_module( $module_name )
    {
        $mod = $this->get_mod();
        if( $mod ) $mod->delete_job($module_name);
    }
} // end of class
