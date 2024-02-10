<?php
#CMS Made Simple class ErrorPage
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$

/**
 * Main class for CMS Made Simple ErrorPage content type
 *
 * @package CMS
 * @version $Revision$
 * @license GPL
 */
class ErrorPage extends Content
{
    public $error_types = [];
    public $doAliasCheck; //unused property
    public $doAutoAliasIfEnabled; //unused property
    public $mType; //property normally runtime generated for read & not written
    public $mPreview; //unused property

    public function __construct()
    {
        parent::__construct();

        global $CMS_ADMIN_PAGE;
        if( isset($CMS_ADMIN_PAGE) ) {
            $this->error_types = array('404'=>lang('404description'),
                                       '403'=>lang('403description'));
        }
        $this->doAliasCheck = false;
        $this->doAutoAliasIfEnabled = false;
        $this->mType = strtolower(get_class($this)); //WHAAATT?
    }

    public function HandlesAlias()
    {
        return true;
    }

    public function FriendlyName()
    {
        return lang('contenttype_errorpage');
    }

    public function SetProperties()
    {
        parent::SetProperties();
        $this->RemoveProperty('parent',-1);
        $this->RemoveProperty('showinmenu',false);
        $this->RemoveProperty('menutext','');
        $this->RemoveProperty('target','');
        $this->RemoveProperty('extra1','');
        $this->RemoveProperty('extra2','');
        $this->RemoveProperty('extra3','');
        $this->RemoveProperty('image','');
        $this->RemoveProperty('thumbnail','');
        $this->RemoveProperty('accesskey','');
        $this->RemoveProperty('titleattribute','');
        $this->RemoveProperty('active',true);
        $this->RemoveProperty('default',false);
        $this->RemoveProperty('cachable',false);
        $this->RemoveProperty('secure',false);
//      $this->RemoveProperty('searchable',false);
        $this->RemoveProperty('page_url','');
        $this->RemoveProperty('alias',''); //change priority (to last position)
        $this->AddProperty('alias',20,parent::TAB_OPTIONS,true);

        //Turn on preview TODO relevance? unused property ?
        $this->mPreview = true;
    }

    public function HasUsableLink()
    {
        return false;
    }

    public function WantsChildren()
    {
        return false;
    }

    public function IsDefaultPossible()
    {
        return false;
    }

    public function IsSystemPage()
    {
        return true;
    }

    public function FillParams($params,$editing = false)
    {
        parent::FillParams($params,$editing);
        $this->mParentId = -1;
        $this->mShowInMenu = false;
        $this->mCachable = false;
        $this->mActive = true;
        //TODO others e.g. $mDefaultContent ?
    }

    public function display_single_element($one,$adding)
    {
        switch($one) {
        case 'alias':
            $dropdownopts = '';
            //$dropdownopts = '<option value="">'.lang('none').'</option>';
            foreach ($this->error_types as $code=>$name)
            {
                $dropdownopts .= '<option value="error' . $code . '"';
                if ('error'.$code == $this->mAlias)
                {
                    $dropdownopts .= ' selected="selected" ';
                }
                $dropdownopts .= ">{$name} ({$code})</option>";
            }
            return array(lang('error_type').':', '<select name="alias">'.$dropdownopts.'</select>');
            break;

        default:
            return parent::display_single_element($one,$adding);
        }
    }

    public function ValidateData()
    {
        // $this->SetPropertyValue('searchable',0);
        // force not searchable.

        $errors = parent::ValidateData();

        //Do our own alias check
        if ($this->mAlias == '')
        {
            $errors[] = lang('nofieldgiven', array(lang('error_type')));
        }
        else if (in_array($this->mAlias, $this->error_types))
        {
            $errors[] = lang('nofieldgiven', array(lang('error_type')));
        }
        else if ($this->mAlias != $this->mOldAlias)
        {
            $gCms = cmsms();
            $contentops = $gCms->GetContentOperations();
            $error = $contentops->CheckAliasError($this->mAlias, $this->mId);
            if ($error)
            {
                if ($error == lang('aliasalreadyused'))
                {
                    $errors[] = lang('errorpagealreadyinuse');
                }
                else
                {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }
}

# vim:ts=4 sw=4 noet
?>
