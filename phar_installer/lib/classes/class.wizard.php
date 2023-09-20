<?php

namespace __appbase;

use DirectoryIterator;
use Exception;
use RegexIterator;

class wizard
{
    private static $_instance;
    private $_classdir;
    private $_initialized = false;
    private $_name = '';
    private $_namespace;
    private $_stepobj = null;
    private $_steps = [];
    private $_stepkeys;

    const STATUS_OK    = 'OK';
    const STATUS_ERROR = 'ERROR';
    const STATUS_BACK  = 'BACK';
    const STATUS_NEXT  = 'NEXT';
    const SECURE_PARAM_NAME = 'i_k_';

    private function __construct($classdir,$namespace)
    {
        if( !is_dir($classdir) ) throw new Exception('Could not find wizard steps in '.$classdir);
        $this->_classdir = $classdir;
        $this->_name = basename($classdir);
        $this->_namespace = $namespace;
        $tmp = md5(realpath(getcwd()).session_id());
        $n = ord($tmp[0]);
        while( $n < 97 ) { //'a'
           $n += 6;
        }
        $tmp[0] = chr(min($n,102)); //'f'
        $this->_stepkeys = substr($tmp,0,12);
    }

    final public static function get_instance($classdir = '', $namespace = '')
    {
        if( !self::$_instance ) self::$_instance = new self($classdir,$namespace);
        return self::$_instance;
    }

    private function _init()
    {
        if( $this->_initialized ) return;
        $this->_initialized = true;

        // find all classes in the wizard directory.
        $di = new DirectoryIterator($this->_classdir);
        $ri = new RegexIterator($di,'/^class\.wizard.*\.php$/');
        $files = array();
        foreach( $ri as $one ) {
            $files[] = $one->getFilename();
        }
        $nsteps = count($files);
        if( !$nsteps ) throw new Exception('Could not find wizard steps in '.$classdir);
        sort($files);

        $_data = array();
        $stepnow = $this->cur_step();
        for( $i = 0; $i < count($files); $i++ ) {
            $idx = $i+1;
            $filename = $files[$i];
            $classname = substr($filename,6,strlen($filename)-10);
            $rec = array('fn'=>$filename,'class'=>'','name'=>'','description'=>'','active'=>'');
            $fullclass = $classname;

            if( $this->_namespace ) $fullclass = $this->_namespace.'\\'.$classname;
            $rec['classname'] = $classname;
            $rec['class'] = $fullclass;
            $rec['active'] = ($idx == $stepnow)?1:0;
            $_data[$idx] = $rec;
        }
        $this->_steps = $_data;

        $sess = session::get();
        if( empty($sess[$this->_stepkeys]) ) {
            $sess[$this->_stepkeys] = array_fill(1, $nsteps, '');
        }
    }

    final public function get_nav()
    {
        $this->_init();
        return $this->_steps;
    }

    final public function get_step_var($stepnum)
    {
        $stepnum = (int)$stepnum;
        $sess = session::get();
        if( !empty($sess[$this->_stepkeys][$stepnum]) ) {
            return $sess[$this->_stepkeys][$stepnum];
        }
        return '';
    }

    final public function set_step_var($stepnum,$str)
    {
        $sess = session::get();
        $all = $sess[$this->_stepkeys]; // work around lack of 2-D storage
        $all[(int)$stepnum] = $str;
        $sess[$this->_stepkeys] = $all;
    }

    final public function cur_step()
    {
        if( isset($_GET[self::SECURE_PARAM_NAME]) ) {
            $str = $_GET[self::SECURE_PARAM_NAME];
            $sess = session::get();
            $all = $sess[$this->_stepkeys];
            if( ($stepnum = array_search($str,$all,true)) !== false ) {
                for( $i = $stepnum+1, $n = count($all); $i < $n; $i++ ) {
                    $all[$i] = '';
                }
                $sess[$this->_stepkeys] = $all;
                return $stepnum;
            }
        }
        return 1;
    }

    final public function finished()
    {
        $this->_init();
        return $this->cur_step() > $this->num_steps();
    }

    final public function num_steps()
    {
        $this->_init();
        return count($this->_steps);
    }

    final public function get_step()
    {
        $this->_init();
        if( is_object($this->_stepobj) ) return $this->_stepobj;

        $rec = $this->_steps[$this->cur_step()];
        if( isset($rec['class']) && class_exists($rec['class']) ) {
            $obj = new $rec['class']();
            if( is_object($obj) ) {
                $this->_stepobj = $obj;
                return $obj;
            }
        }
        $this->_stepobj = null;
        return null;
    }

    public function get_data($key,$dflt = null)
    {
        $sess = session::get();
        if( !isset($sess[$key]) ) return $dflt;
        return $sess[$key];
    }

    public function set_data($key,$value)
    {
        $sess = session::get();
        $sess[$key] = $value;
    }

    public function clear_data($key)
    {
        $sess = session::get();
        if( isset($sess[$key]) ) unset($sess[$key]);
    }

    public function process()
    {
        $this->_init();
        $res = $this->get_step()->run();
        return $res;
    }

    final public function step_url($idx)
    {
        $this->_init();

        // get the url to the specified step index
        $idx = (int)$idx;
        if( $idx < 1 || $idx > $this->num_steps() ) return '';

        $request = request::get();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?',$url);

        $str = $this->get_step_var($idx);
        if( !$str ) {
            $str = base_convert(bin2hex(random_bytes(7)),16,36);
            $this->set_step_var($idx,$str);
        }
        $parts = array();
        $parts[self::SECURE_PARAM_NAME] = $str;
        //TODO any relevant $parts from $urlmain[1]

        $tmp = array();
        foreach( $parts as $k => $v ) {
            $tmp[] = $k.'='.$v;
        }
        $url = rtrim($urlmain[0],' /').'?'.implode('&',$tmp);
        return $url;
    }

    final public function next_url()
    {
        $this->_init();

        $idx = $this->cur_step() + 1;
        if( $idx > $this->num_steps() ) return '';

        $request = request::get();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?',$url);

        $str = $this->get_step_var($idx);
        if( !$str ) {
            $str = base_convert(bin2hex(random_bytes(7)),16,36);
            $this->set_step_var($idx,$str);
        }
        $parts = array();
        $parts[self::SECURE_PARAM_NAME] = $str;
        //TODO any relevant $parts from $urlmain[1]

        $tmp = array();
        foreach( $parts as $k => $v ) {
            $tmp[] = $k.'='.$v;
        }
        $url = rtrim($urlmain[0],' /').'?'.implode('&',$tmp);
        return $url;
    }

    final public function prev_url()
    {
        $this->_init();

        $idx = $this->cur_step() - 1;
        if( $idx < 1 ) return '';

        $request = request::get();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?',$url);

        $str = $this->get_step_var($idx);
        if( !$str ) {
            $str = base_convert(bin2hex(random_bytes(7)),16,36);
            $this->set_step_var($idx,$str);
        }

        $parts = array();
        $parts[self::SECURE_PARAM_NAME] = $str;
        //TODO any relevant $parts from $urlmain[1]

        $tmp = array();
        foreach( $parts as $k => $v ) {
            $tmp[] = $k.'='.$v;
        }
        $url = rtrim($urlmain[0],' /').'?'.implode('&',$tmp);
        return $url;
    }

} // end of class
?>
