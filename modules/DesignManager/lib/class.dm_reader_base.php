<?php
#-------------------------------------------------------------------------
# Module DesignManager class dm_reader_base
# (c) 2015 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

abstract class dm_reader_base
{
    private $_filename;
    private $_suggested_name = '';
    private $_suggested_description = '';

    public function __construct($filename)
    {
        $this->_filename = (string)$filename;
    }

    public function set_suggested_name($name)
    {
        if( $name ) $this->_suggested_name = (string)$name;
    }

    public function set_suggested_description($description)
    {
        if( $description ) $this->_suggested_description = (string)$description;
    }

    protected function get_filename()
    {
        return $this->_filename;
    }

    protected function get_suggested_name()
    {
        return $this->_suggested_name;
    }

    protected function get_suggested_description()
    {
        return $this->_suggested_description;
    }

    abstract public function validate();

    /**
     * Retrieve information about the design
     *
     * @return a hash containing design name, description, generated, and cmsversion
     */
    abstract public function get_design_info();

    /**
     * Retrieve an array of hashes representing template information.
     * each hash will have a name,key,desc,data,type_originator,type_name fields.
     * All data should be base64 decoded.
     */
    abstract public function get_template_list();

    /**
     * Return information about stylesheets in the xml file
     * Returns an array of hashes.  Each hash should contain name, key, desc, data,
     * media type and media query values.  All data should be base64 decoded.
     */
    abstract public function get_stylesheet_list();

    /**
     * Actually do the importing..
     * Can throw.
     */
    abstract public function import();

    /**
     * Get the destination directory for this design's files.
     *
     * Directory should be created, and checked for writable...
     * Throw on failure.
     */
    abstract protected function get_destination_dir();

    /**
     * Get a new name for this design.
     *
     * Use the suggested name if possible, check for duplicate names
     * Throw on failure.
     */
    protected function get_new_name()
    {
        $name = $this->get_suggested_name();
        if( !$name ) {
            // no suggested name... get one from the design.
            $info = $this->get_design_info();
            $name = $info['name'];
        }
        if( !$name ) {
            // still no name... try to use the filename.
            $t = $this->get_filename();
            $x = strpos($t,'.');
            $name = substr($t,$x);
        }

        // adjust a duplicate name
        $list = CmsLayoutCollection::get_list();
        $orig_name = $name;
        if( $list && is_array($list) ) {
            for( $n = 1; $n < 101; $n++ ) {
                if( !in_array($name,$list) ) {
// TODO an existing files-place in $config['themes_path'] also counts as a duplicate
                    break;
                }
                $name = "$orig_name($n)";
            }
            if( $n == 101 ) {
                throw new RuntimeException('Could not determine a new name for design '.$orig_name);
            }
        }
        return $name;
    }

} // class
