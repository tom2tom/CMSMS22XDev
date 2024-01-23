<?php

use FilePicker\PathAssistant;
use FilePicker\TemporaryProfileStorage;
use FilePicker\UploadHandler;

if( !isset($gCms) ) exit;
try {
    if( strtolower($_SERVER['REQUEST_METHOD']) != 'post' ) {
        throw new RuntimeException('Invalid request method');
    }

    $sig = cleanValue(get_parameter_value($_POST,'sig'));
    $cmd = cleanValue(get_parameter_value($_POST,'cmd'));
    $val = strip_tags(get_parameter_value($_POST,'val'));
    $cwd = strip_tags(get_parameter_value($_POST,'cwd'));

    // get the profile
    $profile = null; // no object
    if( $sig ) $profile = TemporaryProfileStorage::get($sig);
    if( !$profile ) $profile = $this->get_default_profile();

    // check the cwd make sure it is okay
    $topdir = $profile->top;
    if( !$topdir ) $topdir = $config['uploads_path'];
    $assistant = new PathAssistant($config,$topdir);

    $fullpath = $assistant->to_absolute($cwd);
    if( ! $assistant->is_relative($fullpath) ) throw new RuntimeException('Invalid cwd '.$cwd);
    if( !isset($this->macos) ) {
        $tmp = php_uname('s');
        $this->winos = stripos($tmp,'windo') !== false;// running on some flavour of Windows
        $this->macos = !$this->winos && stripos($tmp,'darwin') !== false;// running on some flavour of MacOS
    }
    switch( $cmd ) {
    case 'mkdir':
        if( !$profile->can_mkdir ) throw new LogicException('Internal error: mkdir command executed, but profile prohibits that operation');
        // TODO hidden-attribute check on Windows
        if( ($val[0] == '.' && !$this->winos) || ($val[0] == '_' && $this->macos) || ($val[0] == '~' && $this->winos) ) throw new RuntimeException($this->Lang('error_ajax_invalidfilename'));
        if( !is_writable($fullpath) ) throw new RuntimeException($this->Lang('error_ajax_writepermission'));
        $destpath = $fullpath.DIRECTORY_SEPARATOR.$val;
        if( is_dir($destpath) || is_file($destpath) ) throw new RuntimeException($this->Lang('error_ajax_fileexists'));
        if( !@mkdir($destpath) ) throw new RuntimeException($this->Lang('error_ajax_mkdir ',$cwd.DIRECTORY_SEPARATOR.$val)); // TODO $destpath ?
        break;

    case 'del':
        if( !$profile->can_delete ) throw new LogicException('Internal error: del command executed, but profile prohibits that operation');
        $val = basename($val);
        if( ($val[0] == '.' && !$this->winos) || ($val[0] == '_' && $this->macos) || ($val[0] == '~' && $this->winos) ) throw new RuntimeException($this->Lang('error_ajax_invalidfilename'));
        //if( !is_writable($fullpath) ) throw new \RuntimeException($this->Lang('error_ajax_writepermission'));
        $destpath = $fullpath.DIRECTORY_SEPARATOR.$val;
        if( !is_writable($destpath) ) throw new RuntimeException($this->Lang('error_ajax_writepermission').' '.$destpath);
        if( is_dir($destpath) ) {
            // check if the directory is empty
            if( count(scandir($destpath)) > 2 ) throw new RuntimeException($this->Lang('error_ajax_dirnotempty'));
            if( @rmdir($destpath) ) { audit('','FilePicker','Removed directory '.$destpath); }
        } else {
            if( $this->is_image( $destpath ) ) {
                $thumbnail = $fullpath.DIRECTORY_SEPARATOR.'thumb_'.$val;
                if( is_file($thumbnail) ) {
                    @unlink($thumbnail);
                }
            }
            if( @unlink($destpath) ) { audit('','FilePicker','Removed file '.$destpath); }
        }
        break;

    case 'upload':
        if( !$profile->can_upload ) throw new LogicException('Internal error: upload command executed, but profile prohibits that operation');
        // todo: checks for upload functionality
        $upload_handler = new UploadHandler($this,$profile,$fullpath);

        header('Pragma: no-cache');
        header('Cache-Control: private, no-cache');
        header('Content-Disposition: inline; filename="files.json"');
        header('X-Content-Type-Options: nosniff');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
        header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

        switch ($_SERVER['REQUEST_METHOD']) {
        case 'OPTIONS':
            break;
        case 'HEAD':
        case 'GET':
            $upload_handler->get();
            break;
        case 'POST':
            $upload_handler->post();
            break;
        case 'DELETE':
            $upload_handler->delete();
            break;
        default:
            header('HTTP/1.1 405 Method Not Allowed');
        }
        exit; // exit from here.

    default:
        throw new RuntimeException('Invalid cmd '.$cmd);
    }
}
catch( Exception $e ) {
    // throw a 500 error
    debug_to_log('Exception: '.$e->GetMessage());
    debug_to_log($e->GetTraceAsString());
    header("HTTP/1.1 500 ".$e->GetMessage());
}
exit;
