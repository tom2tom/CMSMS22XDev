<?php

namespace CMSMS\Database\mysqli;

/**
 * A final class to describe a special (empty) recordset.
 *
 * @ignore
 */
final class EmptyResultSet extends ResultSet
{
    /**
     * @ignore
     */
    public function MoveFirst() { return FALSE; }
    /**
     * @ignore
     */
    public function MoveNext() { return FALSE; }
    /**
     * @ignore
     */
    public function GetArray() { return []; }
    /**
     * @ignore
     */
    public function GetRows() { return []; }
    /**
     * @ignore
     */
    public function GetAll() { return []; }
    /**
     * @ignore
     */
    public function GetAssoc() { return []; }
    /**
     * @ignore
     */
    public function EOF() { return TRUE; }
    /**
     * @ignore
     */
    public function RecordCount() { return 0; }
    /**
     * @ignore
     */
    public function Fields($field = '') { return ($field) ? null : []; }
    /**
     * @ignore
     */
    protected function fetch_row() {}
} // end of class
