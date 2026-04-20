<?php
#CMS Made Simple class Events
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
#along with this program.  If not, read the license online at:
#https://www.gnu.org/licenses/#LicenseURLs
#
#$Id$

use CMSMS\internal\global_cachable;
use CMSMS\internal\global_cache;

/**
 * Class for handling and dispatching system and user defined events.
 *
 * @package CMS
 * @license GPL
 */
final class Events
{
	// event-handler types
	const HANDLERMOD = 1;
	const HANDLERUDT = 2;
	const HANDLERCALL = 3;

	/**
	 * @ignore
	 * Not a cache in the generally-understood sense, as it's
	 * re-populated each time it is used in this class
	 */
	private static $_handlercache;

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * Inform the system about a new event that can be generated.
	 *
	 * @param string $originator The name of the module (or 'Core') sending the event
	 * @param string $eventname The name of the event
	 */
	public static function CreateEvent($originator, $eventname)
	{
		$db = CmsApp::get_instance()->GetDb();
		$dup = $db->GetOne('SELECT EXISTS(SELECT 1 FROM '.CMS_DB_PREFIX.
		'events WHERE originator = ? AND event_name = ?)', array($originator, $eventname));
		if( $dup == 0 ) {
			$id = $db->GenID(CMS_DB_PREFIX."events_seq");
			$q = "INSERT INTO ".CMS_DB_PREFIX."events (originator,event_name,event_id) values (?,?,?)";
			$db->Execute($q, array($originator, $eventname, $id));
			global_cache::clear(__CLASS__);
		}
	}

	/**
	 * Remove a specified event.
	 * This function removes all handlers of the event, and removes
	 * all references to this event, from the database
	 *
	 * @param string $originator The name of the module (or 'Core') sending the event
	 * @param string $eventname The name of the event
	 */
	public static function RemoveEvent($originator, $eventname)
	{
		$db = CmsApp::get_instance()->GetDb();

		// get the id
		$q = 'SELECT event_id FROM '.CMS_DB_PREFIX.
		'events WHERE originator = ? AND event_name = ?';
		$id = $db->GetOne($q, array($originator, $eventname));
		if( !$id ) {
			return false; // query failed, event not found
		}

		// delete all handlers
		$q = 'DELETE FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ?';
		$db->Execute($q, array($id));

		// delete the event
		$q = 'DELETE FROM '.CMS_DB_PREFIX.'events WHERE event_id = ?';
		$db->Execute($q, array($id));

		global_cache::clear(__CLASS__);
	}

	/**
	 * Trigger an event.
	 * This function will call all registered event handlers for the event
	 *
	 * @param string $originator The name of the module (or 'Core') sending the event
	 * @param string $eventname The name of the event
	 * @param array $params Optional parameters associated with this event. Default empty
	 */
	public static function SendEvent($originator, $eventname, $params = array())
	{
		global $CMS_INSTALL_PAGE;
		if( isset($CMS_INSTALL_PAGE) ) return;

		$results = self::ListEventHandlers($originator, $eventname);

		if( $results ) {
			$params['_modulename'] = $originator; // might be 'Core'
			$params['_eventname'] = $eventname;
			foreach( $results as $row ) {
				switch( $row['handler_type'] ) {
				 case self::HANDLERUDT:
					debug_buffer('calling user tag ' . $row['handler'] . ' from event ' . $eventname);
					if( !isset($usertagops) ) {
						if( !isset($gCms) ) { $gCms = CmsApp::get_instance(); }
						$usertagops = $gCms->GetUserTagOperations();
					}
					$usertagops->CallUserTag($row['handler'], $params);
					break;
				 case self::HANDLERMOD:
					// don't call a module DoEvent function if the event
					// is originated by that same module
					if( $row['handler'] == $originator ) continue 2;
					if( !isset($modops) ) {
						if( !isset($gCms) ) { $gCms = CmsApp::get_instance(); }
						$modops = $gCms->GetModuleOperations();
					}
					$obj = $modops->get_module_instance($row['handler']);
					if( $obj ) {
						// call the module event handler.
						debug_buffer('calling module ' . $row['handler'] . ' from event ' . $eventname);
						$obj->DoEvent($originator, $eventname, $params);
					}
					break;
				 case self::HANDLERCALL:
					debug_buffer('calling \'' . $row['handler'] . '\' from event ' . $eventname);
					if( startswith($row['handler'], 'a:2:{') ) {
						$callable = unserialize($row['handler'], ['allowed_classes'=>[]]);
					}
					else {
						$callable = $row['handler'];
					}
					call_user_func($callable, $params);
					break;
				}
			}
		}
	}

	/**
	 * Initiate the events-data cache
	 * @ignore
	 */
	public static function setup()
	{
		$obj = new global_cachable(__CLASS__,function() {
				$db = CmsApp::get_instance()->GetDb();
				$q = 'SELECT eh.event_id, eh.handler, eh.handler_type, e.originator, e.event_name, eh.handler_order, eh.handler_id, eh.removable
FROM '.CMS_DB_PREFIX.'event_handlers eh
INNER JOIN '.CMS_DB_PREFIX.'events e ON e.event_id = eh.event_id
ORDER BY event_id,handler_order';
				return $db->GetArray($q);
		});
		global_cache::add_cachable($obj);
	}

	/**
	 * Get the cached event-data for the specified event generated by the specified originator.
	 *
	 * @param string $originator The name of the module sending the event or 'Core'
	 * @param string $eventname The name of the event
	 * @param bool $display Since 2.2.23F3 Whether to format handler-values for public display. Default false
	 * @return array Empty or having members each of which is an array
	 *               See particulars in Events::setup()
     *               If the 'handler' property is truncated for display
     *               then a property 'truncated' (= true) is added
	 */
	public static function ListEventHandlers($originator, $eventname, $display = false)
	{
		$handlers = array();

		self::$_handlercache = global_cache::get(__CLASS__);
		if( self::$_handlercache && is_array(self::$_handlercache) ) {
			if( $display ) {
				// tailor 'handler' property value
				$config = cms_config::get_instance();
				$pdev = !empty($config['developer_mode']);
				$display_handler = function($val) use($pdev) {
					if( startswith($val,'a:2:{') ) {
						if( preg_match('/.+?"(.+?)".+?"(.+?)".+$/',$val,$matches) ) {
							$val = $matches[1] . ' : ' . $matches[2];
						}
					}
					if( $pdev ) {
						return $val;
					}
					else {
						$p = strrpos($val,'\\'); // ignore any namespace
						$p = ($p === false) ? 0 : $p + 1;
						return substr($val,$p,3);
					}
				};
			}
			foreach (self::$_handlercache as &$row) {
				if ($row['originator'] == $originator && $row['event_name'] == $eventname) {
					if( $display && $row['handler_type'] == self::HANDLERCALL ) {
						$tmp = $display_handler($row['handler']);
						$row['handler'] = $tmp;
						if( strlen($tmp) == 3 ) {
							$row['truncated'] = true;
						}
					}
					$handlers[] = $row;
				}
			}
			unset($row);
		}
		return $handlers;
	}

	/**
	 * Get the cached event-data for a particular event.
	 *
	 * @param int $handler_id numeric id of the event handler
	 * @return array maybe empty
	 */
	public static function GetEventHandler($handler_id)
	{
		self::$_handlercache = global_cache::get(__CLASS__);

		if( self::$_handlercache && is_array(self::$_handlercache) ) {
			foreach( self::$_handlercache as $row ) {
				if( $row['handler_id'] == $handler_id ) return $row;
			}
		}
		return [];
	}

	/**
	 * Get all usable events.
	 *
	 * @return array Events-data or empty
	 */
	public static function ListEvents()
	{
		$db = CmsApp::get_instance()->GetDb();
		$q = 'SELECT e.originator, e.event_name, e.event_id, COUNT(eh.event_id) AS usage_count FROM '.CMS_DB_PREFIX.
'events e LEFT JOIN '.CMS_DB_PREFIX.
'event_handlers eh ON e.event_id=eh.event_id GROUP BY e.originator, e.event_name, e.event_id ORDER BY originator,event_name';
		$dbr = $db->Execute($q);
		if( $dbr == false ) return [];

		$result = array();
		while( $row = $dbr->FetchRow() ) {
			if( $row['originator'] == 'Core' || cms_utils::module_available($row['originator']) ) {
				$result[] = $row;
			}
		}
		$dbr->Close();
		return $result;
	}

	/**
	 * Record a handler of an event.
	 * @since 2.2.23F2
	 *
	 * @param string $originator The name of the module (or 'Core') sending the event
	 * @param string $eventname The name of the event
	 * @param mixed $handler module name | User Defined Tag name | callable string or array
	 * @param int $type Optional type-indicator for $handler
	 *  Events::HANDLERMOD(=1) for module name This is the default.
	 *  Events::HANDLERUDT(=2) for UDT name
	 *  Events::HANDLERCALL(=3) for callable
	 * @param bool $removable Optional flag whether this event can be removed via the admin UI.
	 *  Default true but always false for a HANDLERCALL.
	 * @return bool indicating success
	 */
	public static function AddEventTypedHandler($originator, $eventname, $handler, $type = 1, $removable = true)
	{
		$db = CmsApp::get_instance()->GetDb();

		// find the event
		$q = 'SELECT event_id FROM '.CMS_DB_PREFIX.
		'events WHERE originator = ? AND event_name = ?';
		$id = $db->GetOne($q, array($originator, $eventname));
		if( !$id ) { return false; } // event not found

		// check this is not a duplication
		$q = 'SELECT handler_id FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ? AND handler = ?';
		switch( (int)$type ) {
			case self::HANDLERMOD:
			case self::HANDLERUDT:
				$params = [$id, $handler];
				break;
			case self::HANDLERCALL:
				if( is_array($handler) ) {
					$params = [$id, serialize($handler)];
				} else {
					$params = [$id, $handler];
				}
				$removable = false; // no removal by admin users
				break;
			default:
				return false;
		}

		$dbr = $db->GetOne($q, $params);
		if( $dbr ) { return false; } // handler exists already TODO useful feedback to caller

		// get a new handler-order
		$q = 'SELECT MAX(handler_order) FROM '.CMS_DB_PREFIX.
		'event_handlers WHERE event_id = ?';
		$dbr = $db->GetOne($q, [$id]);
		$q = 'INSERT INTO '.CMS_DB_PREFIX.
		'event_handlers (handler_id,event_id,handler,handler_type,handler_order,removable) VALUES (?,?,?,?,?,?)';
		$handler_id = $db->GenID(CMS_DB_PREFIX.'event_handlers_seq');
		$handler = $params[1]; // possibly serialized
		$order = ($dbr) ? $dbr + 1 : 1;
		$dbr = $db->Execute($q, [$handler_id,$id,$handler,$type,$order,($removable?1:0)]);
		if( $dbr ) {
			global_cache::clear(__CLASS__);
			return true;
		}
		return false;
	}

	/**
	 * Record a handler of an event.
	 * @deprecated since 2.2.23F32 instead use Events::AddEventTypedHandler()
	 * Either $tag_handler or $module_handler (but not both) must be specified.
	 *
	 * @param string $originator The name of the module sending the event, or 'Core'
	 * @param string $eventname The name of the event
	 * @param string $tag_handler Optional name of a User Defined Tag to handle the event. Default ''.
	 * @param string $module_handler Optional name of a module to handle the event. Default ''.
	 * @param bool $removable Optional flag whether this event can be removed. Default true.
	 * @return bool indicating success.
	 */
	public static function AddEventHandler($originator, $eventname, $tag_handler = '', $module_handler = '', $removable = true)
	{
		if( $tag_handler && $module_handler ) return false;
		if( !($tag_handler || $module_handler) ) return false;
		$handler = ($module_handler) ?: $tag_handler;
		$type = ($module_handler) ? self::HANDLERMOD : self::HANDLERUDT;
		return self::AddEventTypedHandler($originator, $eventname, $handler, $type, $removable);
	}

	/**
	 * @ignore
	 */
	protected static function InternalRemoveHandler(array $handler)
	{
		$db = CmsApp::get_instance()->GetDb();
		$id = $handler['event_id'];

		// update any subsequent handlers
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order - 1 WHERE event_id = ? AND handler_order > ?';
		$db->Execute($sql, [$id, $handler['handler_order']]);

		// now delete this record
		$sql = 'DELETE FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ? AND handler_id = ?';
		$db->Execute($sql, [$id, $handler['handler_id']]);

		global_cache::clear(__CLASS__);
	}

	/**
	 * Remove a specific event handler
	 *
	 * @param int $handler_id
	 */
	public static function RemoveEventHandlerById($handler_id)
	{
		$handler = self::GetEventHandler($handler_id);
		if( $handler ) self::InternalRemoveHandler($handler);
	}

	/**
	 * Remove a recorded handler of an event.
	 * @since 2.2.23F2
	 *
	 * @param string $originator The name of the module (or 'Core') sending the event
	 * @param string $eventname The name of the event
	 * @param mixed $handler module name | User Defined Tag name | callable string or array
	 * @param int $type Optional type-indicator for $handler
	 *  Events::HANDLERMOD(=1) for module name Default
	 *  Events::HANDLERUDT(=2) for UDT name
	 *  Events::HANDLERCALL(=3) for callable
     * @param bool $force Optional flag whether to remove a handler marked as not-removable. Default false.
	 * @return bool indicating success
	 */
	public static function RemoveEventTypedHandler($originator, $eventname, $handler, $type = 1, $force = false)
	{
		$db = CmsApp::get_instance()->GetDb();

		// find the event
		$q = 'SELECT event_id FROM '.CMS_DB_PREFIX.
		'events WHERE originator = ? AND event_name = ?';
		$id = (int)$db->GetOne($q, array($originator, $eventname));
		if( $id < 1 ) { return false; } // event not found TODO useful feedback to caller

		switch( (int)$type ) {
			case self::HANDLERMOD:
			case self::HANDLERUDT:
				$params = [$id, $handler];
				break;
			case self::HANDLERCALL:
				if( is_array($handler) ) {
					$params = [$id, serialize($handler)];
				} else {
					$params = [$id, $handler];
				}
				break;
			default:
				return false;
		}

		$q = 'SELECT handler_id,event_id,handler_order,removable FROM '.CMS_DB_PREFIX.
		'event_handlers WHERE event_id = ? AND handler = ? AND handler_type = '.(int)$type;
		$row = $db->GetRow($q, $params);
		if( !$row || !is_array($row) ) { return false; }
		// check it is removable
		if( !($force || $row['removable']) ) { return false; } // TODO useful feedback to caller
		self::InternalRemoveHandler($row);
		return true;
	}

	/**
	 * Force-remove a handler of a specific event.
	 * @deprecated since 2.2.23F32 instead use Events::RemoveEventTypedHandler()
	 * Either $tag_handler or $module_handler (but not both) must be specified.
	 *
	 * @param string $originator The name of the module sending the event or 'Core'
	 * @param string $eventname The name of the event
	 * @param string $tag_handler Optional name of a User Defined Tag. Default ''
	 * @param string $module_handler Optional name of a module. Default ''.
	 * @return bool indicating success
	 */
	public static function RemoveEventHandler($originator, $eventname, $tag_handler = '', $module_handler = '')
	{
		if( $tag_handler && $module_handler ) return false;
		if( !($tag_handler || $module_handler) ) return false;
		$handler = ($module_handler) ?: $tag_handler;
		$type = ($module_handler) ? self::HANDLERMOD : self::HANDLERUDT;
		return self::RemoveEventTypedHandler($originator, $eventname, $handler, $type, true);
	}

	/**
	 * Clear all handlers of the given event.
	 *
	 * @param string $originator The name of the module sending the event or 'Core'
	 * @param string $eventname The name of the event
	 * @return bool indicating success
	 */
	public static function RemoveAllEventHandlers($originator, $eventname)
	{
		$db = CmsApp::get_instance()->GetDb();

		// find the id
		$q = 'SELECT event_id FROM '.CMS_DB_PREFIX.
		'events WHERE originator = ? AND event_name = ?';
		$dbresult = $db->Execute($q, array($originator, $eventname));
		if( $dbresult == false || $dbresult->RecordCount() == 0 ) {
			// query failed, event not found
			return false;
		}
		$row = $dbresult->FetchRow();
		$id = $row['event_id'];

		// and delete the handlers
		$q = 'DELETE FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ?';
		$dbresult = $db->Execute($q, array($id));
		global_cache::clear(__CLASS__);
		if( $dbresult == false ) return true;
		return false;
	}

	/**
	 * Move an event handler up in its event.
	 *
	 * @param int $handler_id
	 */
	public static function OrderHandlerUp($handler_id)
	{
		$handler = self::GetEventHandler($handler_id);
		if( !$handler ) return;

		if( $handler['handler_order'] < 2 ) return;

		$db = CmsApp::get_instance()->GetDb();
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order + 1 WHERE event_id = ? AND handler_order = ?';
		$db->Execute($sql, [$handler['event_id'], $handler['handler_order'] - 1]);
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order - 1 WHERE event_id = ? AND handler_id = ?';
		$db->Execute($sql, [$handler['event_id'], $handler['handler_id']]);
		global_cache::clear(__CLASS__);
	}

	/**
	 * Move an event handler down in its event.
	 *
	 * @param int $handler_id
	 */
	public static function OrderHandlerDown($handler_id)
	{
		$handler = self::GetEventHandler($handler_id);
		if( !$handler ) return;

		$db = CmsApp::get_instance()->GetDb();
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order - 1 WHERE event_id = ? AND handler_order = ?';
		$db->Execute($sql, [$handler['event_id'], $handler['handler_order'] + 1]);
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order + 1 WHERE event_id = ? AND handler_id = ?';
		$db->Execute($sql, [$handler['event_id'], $handler['handler_id']]);
		global_cache::clear(__CLASS__);
	}

	/**
	 * Get the help message for a core event.
	 *
	 * @param string $eventname The name of the event
	 * @return string The translated help for the event.
	 */
	static function GetEventHelp($eventname)
	{
		return lang_by_realm('events','help_'.strtolower((string)$eventname));
	}

	/**
	 * Get the description of a core event.
	 *
	 * @param string $eventname The name of the event
	 * @return string The translated description of the event.
	 */
	public static function GetEventDescription($eventname)
	{
		return lang_by_realm('events','desc_'.strtolower((string)$eventname));
	}
} // class

?>
