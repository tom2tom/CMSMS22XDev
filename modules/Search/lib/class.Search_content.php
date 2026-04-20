<?php
# Search module class
# (c) 2025 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# A single-method class, it's essentially for autoloading the method.

# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: https://www.gnu.org/licenses/gpl-2.0.html

final class Search_content
{
	/**
	 * Poll the database content-tables for pages having any property,
	 * or specified property(ies), whose value matches a wanted value
	 * @since 2.2.22F2
	 *
	 * @param mixed $needle what to search for
	 * @param mixed $field content-table(s) property name(s) array | string,
	 *  single name or comma-separated series. Default '' hence all properties.
	 * @param bool $strict whether to precisely match $needle. Default false.
	 *  If false, value types need not be the same, and string-values
	 *  need not have the same case, and need not be the entire content
	 *  of a field i.e. *$needle* will match
	 *  Sorry, no fuzzy matching (yet?)
	 *
	 * @return array with member(s) each like: page-numeric-id=>row, or empty.
	 *  Each such row will be an array of matches like field1=>val1[,field2=>val2....]
	 */
	public static function Find($needle, $field = '', $strict = false)
	{
		if( is_string($needle) ) {
			$target = addcslashes($needle, "'");
		}
		elseif( is_bool($needle) ) {
			$target = ($needle) ? 1 : 0;
		}
		else {
			$target = $needle; // int | float | null
		}

		if( !$strict ) {
			$tc = !(is_bool($needle) || is_null($needle));
			$matchfunc = 'stripos';
//			if( is_string($needle) ) {
//$matchfunc = 'stripos'; TODO something to perform caseless multibyte | extended-byte comparisons
//			}
		}

		// distinguish content-table fields from content_props fields to prevent invalid queries
		$db = CmsApp::get_instance()->GetDb();
		$pref = CMS_DB_PREFIX;
		$dbr = $db->GetArray("SHOW COLUMNS FROM `{$pref}content`");
		$cores = array_column($dbr, 'Field');

		if( $field && $field != '*' ) {
			if( !is_array($field) ) {
				$field = explode(',', $field);
			}
			$tmp = array_map('trim', $field);
			$tmp = array_intersect($tmp, $cores);
			if( $tmp ) {
				if( ($k = array_search('content_id', $tmp)) !== false ) {
					unset($tmp[$k]);
				}
				$lookin = 'content_id,'.implode(',', $tmp);
			}
			else {
				$lookin = ''; // signal no search
			}
		}
		else {
			$lookin = '*'; // search everywhere
		}

		if( $lookin ) {
			$where = '';
			$parms = [];

			//NOTE all core-table string fields are _ci collated, so default caseless selection

			if( $strict ) {
				if( $lookin != '*' ) { //explicit field(s) to check
					$fields = [];
					foreach( explode(',', $lookin) as $onefield ) {
						if( in_array($onefield, $cores) ) {
							$fields[] = "$onefield=?";
						}
					}
					$where = ' WHERE BINARY ('. implode(' OR ',  $fields) .')';
					$parms = array_fill(0, count($fields), $target);
				}
			}
			elseif( $lookin != '*' ) {
				$fields = [];
				foreach( explode(',', $lookin) as $onefield ) {
					if( in_array($onefield, $cores) ) {
						$fields[] = "$onefield LIKE ?"; // '%?%' would fail due to single-quoting of the embedded substitute-string
					}
				}
				$where = ' WHERE ('. implode(' OR ', $fields) .')';
				$parms = array_fill(0, count($fields), "%$target%");
			}

			$pref = CMS_DB_PREFIX;
			$query = "SELECT $lookin FROM {$pref}content";
			if( $where ) { $query .= $where; }
			$dbr = $db->GetAssoc($query,$parms); //might be invalid query per $lookin
			if( $dbr ) {
				foreach( $dbr as $id => &$row ) {
					if( is_array($row) ) {
						$matched = false;
						foreach( $row as $k => $v ) {
							if( $strict ) {
								if( $needle === $v ) { $matched = true; } else { $row[$k] = null; }
							}
							else {
								if( $needle == $v ) {
									$matched = true;
									continue;
								}
								if( $tc && $matchfunc((string)$v,$needle) !== false ) { $matched = true; continue; }
								$row[$k] = null;
							}
							if( $row[$k] == null ) { unset($row[$k]); }
						}
						if( !$matched ) {
							$row = null;
						}
					}
				}
				unset($row);
				$dbr = array_filter($dbr);
			}
			else {
				$dbr = []; // no match or possible error
			}
		}
		else {
			$dbr = []; // no core-table check
		}

		// now interrogate extended properties
		if( $field && $field != '*' ) {
			if( $field == 'content' ) { // allowed alias
				$field = 'content_en';
			}
			if( !is_array($field) ) {
				$field = explode(',', $field);
			}
			$tmp = array_map('trim', $field);
			$tmp = array_diff($tmp, $cores);
			$lookin = implode(',', $tmp);
			if( !$lookin ) {
				ksort($dbr, SORT_NUMERIC);
				return $dbr;
			}
		}
		else {
			$lookin = '*';
		}

		$query = "SELECT content_id,prop_name,content FROM {$pref}content_props";
		if( $lookin != '*' ) {
			$lookin = str_replace(',', "','", $lookin);
			$query .= " WHERE prop_name IN('$lookin')";
		}
		// props-table content field is _ci collated, so default caseless selection
		if( $strict ) {
			$query .= ' AND BINARY content = ?';
			$parms = [$target];
		}
		else {
			$query .= ' AND content LIKE ?';
			$parms = ["%$target%"];
		}
		$dbr2 = $db->GetAssoc($query,$parms);
		if( $dbr2 ) {
			foreach( $dbr2 as $id => &$row ) {
				if( is_array($row) ) {
					$matched = false;
					foreach( $row as $k => $v ) {
						if( $strict ) {
							if( $needle === $v ) { $matched = true; } else { $row[$k] = null; }
						}
						else {
							if( $needle == $v ) { $matched = true; continue; }
							if( $tc && $matchfunc((string)$v,$needle) !== false ) {
								$matched = true; continue; // later, change $k to prop_name value
							}
							if( $k != 'prop_name' ) { $row[$k] = null; }
						}
						if( $row[$k] == null ) { unset($row[$k]); }
					}
					if( !$matched ) {
						$row = null;
					}
				}
			}
			unset($row);
			$dbr2 = array_filter($dbr2);
			if( $dbr2 ) {
				foreach( $dbr2 as $id => $row ) {
					if( isset($row['prop_name'])) {
						$k = $row['prop_name'];
						$v = $row['content'];
						unset($row['prop_name'],$row['content']);
						$row[$k] = $v;
					}
					if( isset($dbr[$id]) ) {
						$dbr[$id] += $row;
					} else {
						$dbr[$id] = $row;
					}
				}
			}
		}
		ksort($dbr, SORT_NUMERIC);
		return $dbr;
	}
}
