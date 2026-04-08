<?php
/*
CMSMS FilePicker module class: ProfilesCache
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file FilePicker.module.php applies to this file.
*/

namespace FilePicker;

/**
 * Singleton class to manage temporary caching of Profile-objects.
 * Serialized objects are stored in $_SESSION.
 * @since 1.1
 */
class ProfilesCache
{
    /**
     * @ignore
     */
    private $space;
    private $db;
    private $counter;
    private static $instance = null;

    /**
     * Constructor
     */
    protected function __construct()
    {
        $this->space = 'p'.hash('crc32',__FILE__);
        $this->db = \CmsApp::get_instance()->GetDb();
        $this->counter = \CMS_DB_PREFIX.'mod_filepicker_profiles_seq'; // aka ProfileDAO::tablename()."_seq"
    }

    /**
     * Get the singleton instance of this class
     *
     * @return self
     */
    public static function get_instance()
    {
        if (!self::$instance) { self::$instance = new self(); }
        return self::$instance;
    }

    /**
     * Cache the supplied profile
     * The returned string is used e.g. as a property in the CMSFileBrowser
     * js class and submitted to action.ajax_cmd.php where it is used to
     * retrieve the cached object
     *
     * @param Profile $profile
     * @return string cached-item identifier
     */
    public function set(Profile $profile)
    {
        $key = uniqid('q'.$this->db->GenId($this->counter));
        $_SESSION[$this->space][$key] = serialize($profile);
        return $key;
    }

    /**
     * Retrieve a cached profile
     *
     * @param string $key
     * @return mixed Profile | null
     */
    public function get($key)
    {
        if (isset($_SESSION[$this->space][$key])) {
            $profile = @unserialize($_SESSION[$this->space][$key],
                ['allowed_classes'=>['FilePicker\Profile']]);
            return ($profile) ?: null;
        }
        return null;
    }

    /**
     * Remove a cached profile
     *
     * @param string $key
     */
    public function clear($key)
    {
        if (isset($_SESSION[$this->space][$key])) {
            unset($_SESSION[$this->space][$key]);
        }
    }

    /**
     * Clear the cache
     */
    public function reset()
    {
        if (isset($_SESSION[$this->space])) {
            unset($_SESSION[$this->space]);
        }
    }
}
