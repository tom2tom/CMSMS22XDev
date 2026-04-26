<?php

namespace CMSMS\Database\mysqli;

class Connection extends \CMSMS\Database\Connection
{
    private $_mysql;
    private $_in_transaction = 0;
    private $_in_smart_transaction = 0;
    private $_transaction_status = TRUE;
    private $_transaction_failed = FALSE;

    public function DbType() { return 'mysqli'; }

    public function Connect()
    {
        if( !class_exists('\mysqli') ) throw new \LogicException("Configuration error... mysqli functions are not available");

        mysqli_report(MYSQLI_REPORT_STRICT);
        $port = ($this->_connectionSpec->port === null) ? null : (int)$this->_connectionSpec->port; // null (the mysqli default) means use ini value
        $old = error_reporting(0); // rely on exceptions for error processing
        try {
            $this->_mysql = new \mysqli($this->_connectionSpec->host,
                                        $this->_connectionSpec->username,
                                        $this->_connectionSpec->password,
                                        $this->_connectionSpec->dbname,
                                        $port);
            error_reporting($old);
            // prevent sensitive-information display
            $this->_connectionSpec->password = 'restricted';
            if( $this->_mysql->connect_error ) {
                $this->_mysql = null;
                $this->OnError(self::ERROR_CONNECT,mysqli_connect_errno(),mysqli_connect_error());
                return FALSE;
            }
            // prevent sensitive-information display during any crash
            $this->_connectionSpec->username = 'restricted';
            $this->_connectionSpec->dbname = 'restricted';
            return TRUE;
        }
        catch( \Exception $e ) {
            error_reporting($old);
            $this->_connectionSpec->password = 'restricted';
            $this->_mysql = null;
            $this->OnError(self::ERROR_CONNECT,mysqli_connect_errno(),mysqli_connect_error());
            return FALSE;
        }
    }

    public function NewDataDictionary()
    {
        $obj = new DataDictionary($this);
        return $obj;
    }

    public function Disconnect()
    {
        if( $this->_mysql ) {
            $this->_mysql->Close();
            $this->_mysql = null;
        }
    }

    public function get_inner_mysql()
    {
        return $this->_mysql;
    }

    public function IsConnected()
    {
        return is_object($this->_mysql);
    }

    public function ErrorMsg()
    {
        if( $this->_mysql ) return $this->_mysql->error;
        return mysqli_connect_error();
    }

    public function ErrorNo()
    {
        if( $this->_mysql ) return $this->_mysql->errno;
        return mysqli_connect_errno();
    }

    public function Affected_Rows()
    {
        return $this->_mysql->affected_rows;
    }

    public function Insert_ID()
    {
        $res =  $this->_mysql->insert_id;
        return $res;
    }

    public function qstr($str)
    {
        // note... this could be a two way tcp/ip or socket communication
        return "'".$this->_mysql->escape_string($str)."'";
    }

    public function Concat()
    {
        $arr = func_get_args();
        $list = implode(', ', $arr);

        if (strlen($list) > 0) return "CONCAT($list)";
        return '';
    }

    public function IfNull( $field, $ifNull )
    {
        return " IFNULL($field, $ifNull)";
    }

    /*
     * This is an extension of the legacy ADODB API
     * @param string $sql semi-colon-separated database commands
     * @return the last-retrieved recordset
     */
    protected function do_multisql($sql)
    {
        // no error checking for this stuff
        $ret = null;
        if( $this->_mysql->multi_query($sql) ) {
            do {
                if( ($res = $this->_mysql->store_result()) ) {
                    if( $ret ) { $ret->close(); }
                    $ret = $res;
                }
            } while( $this->_mysql->more_results() && $this->_mysql->next_result() );
        }
        return $ret; // TODO array of all results
    }

    public function do_sql($sql)
    {
        // execute all queries, but only need the resultset from the last one.
        $this->sql = $sql;
        $time_start = microtime(TRUE);
        $res = $this->_mysql->query($sql);
        $this->query_time_total += microtime(TRUE) - $time_start;
        if( $res ) {
            $this->add_debug_query($sql);
            return new ResultSet($this->_mysql, $res, $sql);
        }
        $this->FailTrans();
        $this->OnError(self::ERROR_EXECUTE,$this->_mysql->errno,$this->_mysql->error);
        return null; // no object
    }

    public function Prepare($sql)
    {
        return new Statement($this,$sql);
    }

    public function BeginTrans()
    {
        if( $this->_in_smart_transaction ) return TRUE; // allow nesting in this case.
        $this->_in_transaction++;
        $this->_transaction_failed = FALSE;
        $this->Execute('BEGIN');
        return TRUE;
    }

    public function StartTrans()
    {
        if( $this->_in_smart_transaction ) {
            $this->_in_smart_transaction++;
            return; //TODO return value?
        }

        if( $this->_in_transaction ) {
            $this->OnError(self::ERROR_TRANSACTION, -1, 'Bad Transaction: StartTrans called within BeginTrans');
            return; //TODO return value? FALSE
        }
        $this->_transaction_status = TRUE;
        $this->_in_smart_transaction++;
        $this->BeginTrans();
         //TODO return value?
    }

    public function RollbackTrans()
    {
        if( !$this->_in_transaction ) {
            $this->OnError(self::ERROR_TRANSACTION, -1, 'BeginTrans has not been called');
            return FALSE;
        }

        $this->_in_transaction--;
        $this->Execute('ROLLBACK');
        return TRUE;
    }

    function CommitTrans($ok=true)
    {
        if (!$ok) return $this->RollbackTrans();

        if( !$this->_in_transaction ) {
            $this->OnError(self::ERROR_TRANSACTION, -1, 'BeginTrans has not been called');
            return FALSE;
        }

        $this->_in_transaction--;
        $this->Execute('COMMIT');
        return TRUE;
    }

    public function CompleteTrans($autoComplete = true)
    {
        if( $this->_in_smart_transaction > 0 ) {
            $this->_in_smart_transaction--;
            return TRUE;
        }

        if( $this->_transaction_status && $autoComplete ) {
            if( !$this->CommitTrans() ) {
                $this->_transaction_status = FALSE;
            }
        } else {
            $this->RollbackTrans();
        }
        $this->_in_smart_transaction = 0;
        return $this->_transaction_status;
    }

    public function FailTrans()
    {
        $this->_transaction_status = FALSE;
    }

    function HasFailedTrans()
    {
        if( $this->_in_smart_transaction > 0 ) return $this->_transaction_status == FALSE;
        return FALSE;
    }

    public function GenID($seqname)
    {
        // this should be as race-resistant as reasonably possible
        if( $this->_mysql->multi_query("LOCK TABLE `$seqname` WRITE;UPDATE `$seqname` SET id = (@seq_value := id) + 1;SELECT @seq_value;UNLOCK TABLE") ) {
            for( $i = 0; $i < 4; ++$i ) {
                if( $i == 2 ) {
                    $res = $this->_mysql->store_result();
                    if( $res ) {
                        $row = $res->fetch_row();
                        $res->close();
                    }
                }
                $this->_mysql->next_result();
            }
            if( isset($row) ) { return (int)$row[0] + 1; }
        }
        if( $this->_mysql->errno ) $this->OnError(self::ERROR_EXECUTE,$this->_mysql->errno,$this->_mysql->error);
        return 0;
    }

    public function CreateSequence($seqname,$startID=0)
    {
        $startID = (int)$startID;
        $dict = $this->NewDataDictionary();
        $dict->ExecuteSQLArray([
        "DROP TABLE IF EXISTS `$seqname`",
        "CREATE TABLE `$seqname` (id integer NOT NULL) ENGINE MyISAM CHARSET ascii COLLATE ascii_bin MAX_ROWS 1",
        "INSERT INTO `$seqname` (id) VALUES ($startID)"
        ]);
        return TRUE;
    }

    public function DropSequence($seqname)
    {
        return $this->Execute("DROP TABLE `$seqname`");
    }
} // end of class
