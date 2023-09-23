<?php
#CMS Made Simple class BookmarkOperations
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT withOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$

/**
 * Class for doing bookmark related functions. Many of the Bookmark object functions
 * are just wrappers around these.
 *
 * @package CMS
 * @license GPL
 */
class BookmarkOperations
{
	/**
	 * Prepares a url for saving by replacing security tags with a holder
	 * string so it can be replaced when retrieved and not break security.
	 * @deprecated since 2.2.19 admin-url placeholders contribute to
	 *  bypassing permissions, should not be allowed or used
	 *
	 * @param string $url The url to save
	 * @return string The fixed url
	 * @internal
	 */
	private function _prep_for_saving($url)
	{
		$root_url = preg_replace('#^http(s)?://#','', CMS_ROOT_URL);// TODO check this!
		$urlext = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
		if( startswith($url,$root_url) ) $url = str_replace($root_url,'[ROOT_URL]',$url);
		return str_replace($urlext,'[SECURITYTAG]',$url);
	}

	/**
	 * Prepares a url for displaying by replacing the holder for the security
	 * tag with the actual value.
	 * @deprecated since 2.2.19 admin-url placeholders contribute to
	 *  bypassing permissions, should not be allowed or used
	 *
	 * @param string $url The url to display
	 * @return string The fixed url
	 * @internal
	 */
	private function _prep_for_display($url)
	{
		$urlext = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

		$map = array('[SECURITYTAG]'=>$urlext,'[ROOT_URL]'=>CMS_ROOT_URL);
		foreach( $map as $from => $to ) {
			$url = str_replace($from,$to,$url);
		}

		return str_replace($from,$to,$url);
	}

	/**
	 * Gets a list of all bookmarks for a given user
	 *
	 * @param int $user_id The desired user id.
	 * @return array An array of Bookmark objects
	 */
	public function LoadBookmarks($user_id)
	{
		$gCms = \CmsApp::get_instance();
		$db = $gCms->GetDb();

		$result = array();
		$query = "SELECT bookmark_id, title, url FROM ".CMS_DB_PREFIX."admin_bookmarks WHERE user_id = ? ORDER BY title";
		$dbresult = $db->Execute($query, array($user_id));
		if ($dbresult) {
			while ($row = $dbresult->FetchRow()) {
				$onemark = new Bookmark();
				$onemark->bookmark_id = $row['bookmark_id'];
				$onemark->user_id = $user_id;
				$onemark->url = ($row['url']) ? $this->_prep_for_display($row['url']) : 'missing.url';
				$onemark->title = ($row['title']) ?: '&lt;Missing Title&gt;';
				$result[] = $onemark;
			}
			$dbresult->Close();
		}

		return $result;
	}

	/**
	 * Loads a bookmark by bookmark_id.
	 *
	 * @param int $id bookmark_id to load
	 * @return Bookmark
	 * @since 0.6.1
	 */
	function LoadBookmarkByID($id)
	{
		$db = \CmsApp::get_instance()->GetDb();

		$query = "SELECT user_id, title, url FROM ".CMS_DB_PREFIX."admin_bookmarks WHERE bookmark_id = ?";
		$dbresult = $db->Execute($query, array($id));

		if ($dbresult) {
			while ($row = $dbresult->FetchRow()) {
				$onemark = new Bookmark();
				$onemark->bookmark_id = (int)$id;
				$onemark->user_id = (int)$row['user_id'];
				$onemark->url = ($row['url']) ? $this->_prep_for_display($row['url']) : 'missing.url';
				$onemark->title = ($row['title']) ?: '&lt;Missing Title&gt;';
				$dbresult->Close();
				return $onemark;
			}
			$dbresult->Close();
		}

		return null; // no object
	}

	/**
	 * Saves a new bookmark to the database.
	 *
	 * @param Bookmark $bookmark Bookmark object to save
	 * @return int The new bookmark_id. If it fails, it returns -1.
	 */
	function InsertBookmark(Bookmark $bookmark)
	{
		$db = \CmsApp::get_instance()->GetDb();

		$bookmark->url = isset($bookmark->url) ? $this->_prep_for_saving($bookmark->url) : 'missing.url';
		if (!isset($bookmark->title)) $bookmark->title = '&lt;Missing Title&gt;';
		$new_bookmark_id = $db->GenID(CMS_DB_PREFIX."admin_bookmarks_seq");
		$query = "INSERT INTO ".CMS_DB_PREFIX."admin_bookmarks (bookmark_id, user_id, url, title) VALUES (?,?,?,?)";
		$dbresult = $db->Execute($query, array($new_bookmark_id, $bookmark->user_id, $bookmark->url, $bookmark->title));
		if ($dbresult) return $new_bookmark_id;

		return -1;
	}

	/**
	 * Updates an existing bookmark in the database.
	 *
	 * @param Bookmark $bookmark object to save
	 * @return bool
	 */
	function UpdateBookmark(Bookmark $bookmark)
	{
		$db = \CmsApp::get_instance()->GetDb();

		$bookmark->url = isset($bookmark->url) ? $this->_prep_for_saving($bookmark->url) : 'missing.url';
		if (!isset($bookmark->title)) $bookmark->title = '&lt;Missing Title&gt;';
		$query = "UPDATE ".CMS_DB_PREFIX."admin_bookmarks SET user_id = ?, title = ?, url = ? WHERE bookmark_id = ?";
		$dbresult = $db->Execute($query, array($bookmark->user_id, $bookmark->title, $bookmark->url, $bookmark->bookmark_id));
		if ($dbresult) return true;

		return false;
	}

	/**
	 * Deletes an existing bookmark from the database.
	 *
	 * @param int $id Id of the bookmark to delete
	 * @return bool
	 */
	function DeleteBookmarkByID($id)
	{
		$db = \CmsApp::get_instance()->GetDb();

		$query = "DELETE FROM ".CMS_DB_PREFIX."admin_bookmarks where bookmark_id = ?";
		$dbresult = $db->Execute($query, array($id));
		if ($dbresult) return true;
		return false;
	}
}

?>
