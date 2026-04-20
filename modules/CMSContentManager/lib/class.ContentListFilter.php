<?php
/*
CMSMS CMSContentManager module class: ContentListFilter
(c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CMSContentManager.module.php applies to this file too.
*/

namespace CMSContentManager;

use LogicException;

/**
 * A simple class defining a content filter.
 * @final
 * @internal
 * @ignore
 * @package CMS
 */
final class ContentListFilter
{
	const EXPR_OWNER = 'OWNER_UID';
	const EXPR_EDITOR = 'EDITOR_UID';
	const EXPR_TEMPLATE = 'TEMPLATE_ID';
	const EXPR_DESIGN = 'DESIGN_ID';

	private $_type; // one of the EXPR_* consts
	private $_expr; // value of $_type, numeric string

	#[\ReturnTypeWillChange]
	public function __get($key)
	{
		switch( $key ) {
		case 'type':
		case 'expr':
			$key = '_'.$key;
			return $this->$key;

		default:
			throw new LogicException("$key is not a gettable member of ".__CLASS__);
		}
	}

	#[\ReturnTypeWillChange]
	public function __set($key,$val)
	{
		switch( $key ) {
		case 'type':
			switch( $val ) {
			case self::EXPR_OWNER:
			case self::EXPR_EDITOR:
			case self::EXPR_TEMPLATE:
			case self::EXPR_DESIGN:
				$this->_type = $val;
				break;
			default:
				throw new LogicException("$val is an invalid type for ".__CLASS__);
			}
			break;

		case 'expr':
			$this->_expr = trim($val);  // OR (int)
			break;

		default:
			throw new LogicException("$key is not a settable member of ".__CLASS__);
		}
	}
}
