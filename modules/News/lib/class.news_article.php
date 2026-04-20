<?php
#CMSMS News module class: news_article
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

class news_article
{
/* UNUSED
    private static $_keys = array(
    'author',
    'author_id',
    'authorname',
    'canonical',
    'category',
    'category_id',
    'content',
    'create_date',
    'customfieldsbyname',
    'enddate',
    'extra',
    'fields',
    'fieldsbyname',
    'file_location',
    'icon',
    'id',
    'modified_date',
    'news_url',
    'params',
    'postdate',
    'returnid',
    'startdate',
    'status',
    'summary',
    'title',
    'useexp'
    );
*/
    private $_rawdata = array();
    private $_meta = array();
    private $_inparams = array();
    private $_inid = 'm1_';

    private function _getdata($key)
    {
        if( isset($this->_rawdata[$key]) ) return $this->_rawdata[$key];
        return null; // aka unset
    }


    private function _getauthorinfo($author_id,$authorname = FALSE)
    {
        if( !isset($this->_meta['author']) ) {
            $mod = cms_utils::get_module('News');
            $this->_meta['author'] = $mod->Lang('anonymous');
            $this->_meta['authorname'] = $mod->Lang('unknown');
            if( $author_id > 0 ) {
                $userops = cmsms()->GetUserOperations();
                $theuser = $userops->LoadUserById($author_id);
                if( is_object($theuser) ) {
                    $this->_meta['author'] = $theuser->username;
                    $this->_meta['authorname'] = $theuser->firstname.' '.$theuser->lastname; // is there some locale way we can do this?
                }
            }
            elseif( $author_id < 0 ) {
                $this->_meta['author'] = $mod->Lang('unknown');
                $feu = cms_utils::get_module('MAMS');
                if( !$feu ) {
                    $feu = cms_utils::get_module('FrontEndUsers');
                }
                if( $feu ) {
                    $uinfo = $feu->GetUserInfo(-(int)$author_id);
                    if( $uinfo && $uinfo[0] ) $this->_meta['author'] = $uinfo[1]['username'];
                }
            }
        }
        if( $authorname ) return (isset($this->_meta['authorname'])) ? $this->_meta['authorname'] : null;
        return $this->_meta['author'];
    }


    private function _get_returnid()
    {
        if( !isset($this->_meta['returnid']) ) {
            $mod = cms_utils::get_module('News');
            $tmp = $mod->GetPreference('detail_returnid',-1);
            if( $tmp <= 0 ) $tmp = ContentOperations::get_instance()->GetDefaultContent();
            $this->_meta['returnid'] = $tmp;
        }
        return $this->_meta['returnid'];
    }


    private function _get_canonical()
    {
        if( !isset($this->_meta['canonical']) ) {
            $tmp = $this->news_url;
            if( $tmp == '' ) {
                $aliased_title = munge_string_to_url($this->title);
                $tmp = 'news/'.$this->id.'/'.$this->returnid."/$aliased_title";
            }
            $mod = cms_utils::get_module('News');
            $canonical = $mod->create_url($this->_inid,'detail',$this->returnid,$this->params,FALSE,FALSE,$tmp);
            $this->_meta['canonical'] = $canonical;
        }
        return $this->_meta['canonical'];
    }


    private function _get_params()
    {
        $params = $this->_inparams;
        $params['articleid'] = $this->id;
        return $params;
    }


    public function set_linkdata($id,$params,$returnid = '')
    {
        if( $id ) $this->_inid = $id;
        if( is_array($params) ) $this->_inparams = $params;
        if( isset($this->_inparams['returnid']) ) {
            $this->_meta['returnid'] = $params['returnid'];
        }
        elseif( $returnid ) {
            $this->_meta['returnid'] = $returnid;
        }
    }


    public function set_field(news_field $field)
    {
        if( !isset($this->_rawdata['fieldsbyname']) ) $this->_rawdata['fieldsbyname'] = array();
        $name = $field->name;
        $this->_rawdata['fieldsbyname'][$name] = $field;
    }


    public function unset_field($name)
    {
        if( isset($this->_rawdata['fieldsbyname']) ) {
            if( isset($this->_rawdata['fieldsbyname'][$name]) ) unset($this->_rawdata['fieldsbyname'][$name]);
            if( count($this->_rawdata['fieldsbyname']) == 0 ) unset($this->_rawdata['fieldsbyname']);
        }
    }


    #[\ReturnTypeWillChange]
    public function __get($key)
    {
        switch( $key ) {
        case 'id':
        case 'author_id':
        case 'title':
        case 'content':
        case 'summary':
        case 'extra':
        case 'icon':
        case 'news_url':
        case 'postdate':       // db datetime
        case 'startdate':      // db datetime
        case 'enddate':        // db datetime
        case 'create_date':    // db datetime
        case 'modified_date':  // db datetime
        case 'category_id':
        case 'status':
            return $this->_getdata($key);

        case 'url':
            return $this->_getdata('news_url');

        case 'image_url':
        case 'image':
            return $this->_getdata('icon'); //might be relative url

        case 'file_location':
            $config = \cms_config::get_instance();
            $url = $config['uploads_url'].'/news/id'.$this->id;
            return $url;

        case 'author':
            // metadata
            return $this->_getauthorinfo($this->author_id);

        case 'authorname':
            // metadata
            return $this->_getauthorinfo($this->author_id,TRUE);

        case 'category_name':
        case 'category':
            // metadata TODO $this->meta['category_name'] content?
            return news_ops::get_category_name_from_id($this->category_id);

        case 'useexp':
            if( isset($this->_meta['useexp']) ) return (bool)$this->_meta['useexp'];
            return false;

        case 'canonical':
            // metadata
            return $this->_get_canonical();

        case 'fields':
        case 'customfieldsbyname': // deprecated
        case 'fieldsbyname': // deprecated
            if( isset($this->_rawdata['fieldsbyname']) ) return $this->_rawdata['fieldsbyname'];
            return [];

        case 'returnid':
            // metadata
            return $this->_get_returnid();

        case 'params':
            // metadata
            return $this->_get_params();

        default:
            // check if there is a field with this alias
            if( isset($this->_rawdata['fieldsbyname']) && is_array($this->_rawdata['fieldsbyname']) ) {
                foreach( $this->_rawdata['fieldsbyname'] as $fname => &$obj ) {
                    if( !is_object($obj) ) continue;
                    if( $key == $obj->alias ) return $obj->value;
                }
                unset($obj);
            }
            //throw new Exception('Requesting invalid data from News article object '.$key);
        }
        return null; // no value after switch-break
    }


    #[\ReturnTypeWillChange]
    public function __isset($key)
    {
        switch( $key )
        {
        case 'id':
        case 'author_id':
        case 'title':
        case 'content':
        case 'summary':
        case 'extra':
        case 'icon':
        case 'news_url':
        case 'category_id':
        case 'postdate':
        case 'startdate':
        case 'enddate':
        case 'fieldsbyname':
        case 'status':
            return isset($this->_rawdata[$key]);

        case 'url':
            return isset($this->_rawdata['news_url']);

        case 'image_url':
        case 'image':
            return isset($this->_rawdata['icon']);

        case 'customfieldsbyname': // deprecated
        case 'fields': // deprecated
            return isset($this->_rawdata['fieldsbyname']);

        case 'author':
        case 'authorname':
        case 'category':
        case 'canonical':
        case 'returnid':
        case 'params':
        case 'useexp':
            return TRUE;

        case 'create_date':
        case 'modified_date':
            if( $this->id != '' ) return TRUE; // shortcut-check ok?
            break;

        case 'news_date':
            return isset($this->_rawdata['postdate']);

        case 'category_name':
        case 'category':
            return isset($this->_meta['category_name']);

        default:
            throw new Exception('Requesting invalid data from News article object '.$key);
        }

        return FALSE;
    }


    #[\ReturnTypeWillChange]
    public function __set($key,$value)
    {
        switch( $key ) {
        case 'id':
        case 'author_id':
        case 'title':
        case 'content':
        case 'summary':
        case 'extra':
        case 'category_id':
            $this->_rawdata[$key] = $value;
            break;

        case 'status':
            $value = strtolower($value);
            if( $value != 'published' ) $value = 'draft';
            $this->_rawdata[$key] = $value;
            break;

        case 'useexp':
            // this is a different case as this doesn't get stored in the database
            $this->_meta['useexp'] = $value;
            break;

        case 'icon':
        case 'image_url':
        case 'image':
            $this->_rawdata['icon'] = $value; // might be relative url TODO sanitize e.g. cms_utils::validate_url($value,'image')
            break;

        case 'news_url':
        case 'url':
            $this->_rawdata['news_url'] = cms_utils::cleanUrlPath($value);
            break;

        case 'news_date':
            $key = 'postdate';
            //no break here
        case 'create_date':   // db datetime
        case 'modified_date': // db datetime
        case 'postdate':      // db datetime
        case 'startdate':     // db datetime
        case 'enddate':       // db datetime
            if( is_int($value) ) {
                $db = cmsms()->GetDb();
                $value = trim($db->DBTimeStamp($value),"'");
            }
            $this->_rawdata[$key] = $value;
            break;

        case 'category_name':
        case 'category':
            $this->_meta['category_name'] = $value;
            break;

        default:
            throw new Exception('Modifying invalid data in News article object '.$key);

        }
    }
}

?>
