<?php

/**
 * A file to describe an empty recordset
 *
 * @ignore
 */
namespace CMSMS\Database;

/**
 * A final class to describe a special (empty) recordset.
 *
 * @ignore
 */
final class EmptyResultset extends Resultset
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
    public function Close() {} //TODO leakage
    /**
     * @ignore
     */
    public function RecordCount() { return 0; }

    /**
     * @ignore
     */
    public function Fields($field = '') { return ($field) ? null : []; }
} // end of class
