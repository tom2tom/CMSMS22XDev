<?php
/*
CMSMS FilePicker module class: ProfileDAO
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file FilePicker.module.php applies to this file.
*/

namespace FilePicker;

use CmsInvalidDataException;
use FilePicker; // module-class in global space
use FilePicker\Profile;
use LogicException;
use RuntimeException;
use const CMS_DB_PREFIX;

/**
 * A class of storage, retrieval and administrative methods for FilePicker objects
 *
 * @package CMS
 * @license GPL
 * @author Fernando Morgado
*/
class ProfileDAO
{
    const DFLT_PREF = 'ProfileDAO_defaultProfileId';
    private $_mod;
    private $_db;

    /**
     * Constructor
     *
     * @param FilePicker $mod
     */
    public function __construct(FilePicker $mod)
    {
        $this->_mod = $mod;
        $this->_db = $mod->GetDb();
    }

    /**
     *
     * @return string
     */
    public static function table_name()
    {
        return CMS_DB_PREFIX.'mod_filepicker_profiles';
    }

    /**
     *
     * @param array $row
     * @return mixed Profile | null
     */
    protected function profile_from_row(array $row)
    {
        $data = unserialize($row['data']);
        if( $data ) {
            $data['name'] = $row['name'];
            $data['id'] = $row['id'];
            if( !isset($data['data']) ) $data['data'] = '';
            $data['created'] = $row['created'];
            $data['modified'] = $row['modified'];
            return new Profile($data);
        }
        return null;
    }

    /**
     *
     * @return int
     */
    public function getDefaultProfileId()
    {
        return (int) $this->_mod->GetPreference(self::DFLT_PREF);
    }

    /**
     *
     */
    public function clearDefault()
    {
        $this->_mod->RemovePreference(self::DFLT_PREF);
    }

    /**
     *
     * @param Profile $profile
     * @throws LogicException
     */
    public function setDefault(Profile $profile)
    {
        if( $profile->id < 1 ) throw new LogicException('Cannot set a profile as default if it is not yet saved');
        $this->_mod->SetPreference(self::DFLT_PREF,$profile->id);
    }

    /**
     *
     * @return mixed Profile | null
     */
    public function loadDefault()
    {
        $dflt_id = $this->getDefaultProfileId();
        if( $dflt_id < 1 ) return null; // no object
        return $this->loadById($dflt_id);
    }

    /**
     *
     * @param int $id
     * @return mixed Profile | null
     * @throws CmsInvalidDataException
     */
    public function loadById($id)
    {
        $id = (int) $id;
        if( $id < 1 ) throw new CmsInvalidDataException('Invalid id passed to '.__METHOD__);
        $sql = 'SELECT * FROM '.self::table_name().' WHERE id = ?';
        $row = $this->_db->GetRow($sql,[ $id ]);
        if( is_array($row) && count($row) ) return $this->profile_from_row($row);
        return null; // no object
    }

    /**
     *
     * @param string $name
     * @return mixed Profile | null
     * @throws CmsInvalidDataException
     */
    public function loadByName($name)
    {
        $name = trim((string)$name);
        if( !$name ) throw new CmsInvalidDataException('Invalid name passed to '.__METHOD__);
        $sql = 'SELECT * FROM '.self::table_name().' WHERE name = ?';
        $row = $this->_db->GetRow($sql,[ $name ]);
        if( is_array($row) && count($row) ) return $this->profile_from_row($row);
        return null; // no object
    }

    /**
     *
     * @param Profile $profile
     * @return $profile with its id property reset to 0 and its created, modified to now
     * @throws CmsInvalidDataException
     */
    public function delete(Profile $profile)
    {
        if( $profile->id < 1 ) throw new CmsInvalidDataException('Invalid profile passed to '.__METHOD__);

        $sql = 'DELETE FROM '.self::table_name().' WHERE id = ?';
        $this->_db->Execute($sql, [$profile->id]); // no success check ?

        $profile->withNewId(0);
        return $profile;
    }

    /**
     *
     * @param Profile $profile
     * @return Profile
     * @throws CmsInvalidDataException
     * @throws RuntimeException
     */
    protected function _insert(Profile $profile)
    {
        $tbl = self::table_name();
        $tmp = (int)$this->_db->GetOne("SELECT id FROM $tbl WHERE name=?", [$profile->name] );
        if( $tmp > 0 ) throw new CmsInvalidDataException('err_profilename_exists');

        $now = time();
        $props = $profile->getRawData();
        $props['created'] = $props['modified'] = $now;
        $flat = serialize($props);
        // the recorded data will initially have wrong numeric id
        $sql = "INSERT INTO $tbl (name,data,created,modified) VALUES (?,?,$now,$now)";
        $dbr = $this->_db->Execute($sql, [$profile->name, $flat]);
        if( $dbr ) {
            $new_id = $this->_db->Insert_ID();
            $profile->withNewId($new_id);
            // conform the recorded id
            $props['id'] = $new_id;
            // and the timestamps
            $props['created'] = $props['modifed'] = $profile->created;
            $flat = serialize($props);
            $this->_db->Execute("UPDATE $tbl SET data=? WHERE id=$new_id", [$flat]);
            return $profile;
        }
        else {
            throw new RuntimeException('Problem inserting profile record');
        }
    }

    /**
     *
     * @param Profile $profile
     * @return Profile
     * @throws CmsInvalidDataException
     * @throws RuntimeException
     */
    protected function _update(Profile $profile)
    {
        $tbl = self::table_name();
        $sql = "SELECT id FROM $tbl WHERE name=? AND id!=?";
        $tmp = (int)$this->_db->GetOne($sql, [$profile->name, $profile->id]);
        if( $tmp > 0 ) throw new CmsInvalidDataException('err_profilename_exists');
        $now = time();
        $sql = "UPDATE $tbl SET name=?, data=?, modified=$now WHERE id=?";
        $dbr = $this->_db->Execute($sql, [$profile->name, serialize($profile->getRawData()), $profile->id]);
        if( !$dbr ) throw new RuntimeException('Problem updating profile record');

        return $profile->markModified();
    }

    /**
     *
     * @param Profile $profile
     * @return Profile
     */
    public function save(Profile $profile)
    {
        if( $profile->id < 1 ) {
            return $this->_insert($profile);
        } else {
            return $this->_update($profile);
        }
    }

    /**
     *
     * @return array of Profile object(s), maybe empty
     */
    public function loadAll()
    {
        $sql = 'SELECT * FROM '.self::table_name().' ORDER BY name';
        $list = $this->_db->GetArray($sql);
        if( !$list ) return [];

        $out = [];
        foreach( $list as $row ) {
            $obj =  $this->profile_from_row($row);
            if( $obj ) { $out[] = $obj; }
        }
        return $out;
    }
} // class
