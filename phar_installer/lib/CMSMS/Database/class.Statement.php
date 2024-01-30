<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: CMSMS\Database\Statement
# (c) 2015 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# A class to represent a prepared SQL statement
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE

/**
 * This file defines the abstract database statement class.
 *
 * @package CMS
 */

namespace CMSMS\Database;

/**
 * A class defining a prepared database statement.
 *
 * @package CMS
 * @author Robert Campbell
 * @since 2.2
 * @property-read Connection $db The database connection
 * @property-read string $sql The SQL query.
 */
abstract class Statement
{
    /**
     * @ignore
     */
    private $_conn;

    /**
     * @ignore
     */
    private $_sql;

    /**
     * Constructor
     *
     * @param Connection $conn The database connection
     * @param string $sql The SQL query
     */
    public function __construct(Connection $conn,$sql = '')
    {
        $this->_conn = $conn;
        $this->_sql = $sql;
    }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __get($key)
    {
        switch( $key ) {
        case 'db':
        case 'conn':
            return $this->_conn;

        case 'sql':
            return $this->_sql;
        }
        return null; // no value for unrecognised property
    }

    /**
     * Bind data to the sql statements
     *
     * @param array $data An array of arrays of data representing the numerous rows of the input data.
     */
    public function Bind(array $data)
    {
        if( !$data ) {
            $this->_conn->OnError($this->_conn::ERROR_EXECUTE,-1,'Data passed to '.__METHOD__.' must be an associative array');
            exit;
        }
        $first = $data[0];
        if( !$first || !is_array($first) ) {
            $this->_conn->OnError($this->_conn::ERROR_EXECUTE,-1,'Data passed to '.__METHOD__.' must be an associative array');
            exit;
        }
        $keys = array_keys($first);
        if( is_numeric($keys[0]) && $keys[0] === 0 ) {
            $this->_conn->OnError($this->_conn::ERROR_EXECUTE,-1,'Data passed to '.__METHOD__.' must be an associative array');
            exit;
        }

        $this->set_bound_data($data);
    }

    /**
     * Set bound data
     *
     * @see bind
     * @param array $data An array of arrays of data representing the numerous rows of the input data.
     */
    abstract protected function set_bound_data($data);

    /**
     * Test if we are at the end of the resultset.
     *
     * @return bool
     */
    abstract public function EOF();

    /**
     * Move to the first record of the resultset.
     */
    abstract public function MoveFirst();

    /**
     * Move to the next record of the resultset.
     */
    abstract public function MoveNext();

    /**
     * Retrive data fields.
     *
     * @param string $col The column name.  If not specified, all columns will be returned.
     * @return mixed
     */
    abstract public function Fields($col = '');

    /**
     * Execute the query
     */
    abstract public function Execute();
}
