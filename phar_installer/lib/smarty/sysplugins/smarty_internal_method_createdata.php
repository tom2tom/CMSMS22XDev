<?php

/**
 * Smarty Method CreateData
 *
 * Smarty::createData() method
 *
 * @package    Smarty
 * @subpackage PluginsInternal
 * @author     Uwe Tews
 */
class Smarty_Internal_Method_CreateData
{
    /**
     * Valid for Smarty and template object
     *
     * @var int
     */
    public $objMap = 3;

    /**
     * creates a data object
     *
     * @api  Smarty::createData()
     * @link https://www.smarty.net/docs/en/api.create.data.tpl
     *
     * @param mixed Smarty_Internal_TemplateBase|Smarty_Internal_Template|Smarty          $obj
     * @param mixed Smarty_Internal_Template|Smarty_Internal_Data|Smarty_Data|Smarty|null $parent next higher level of Smarty
     *                                                                                     variables Default null
     * @param string|null                                                                 $name   optional data block name Default null
     *
     * @return \Smarty_Data data object
     */
    public function createData(Smarty_Internal_TemplateBase $obj, ?Smarty_Internal_Data $parent = null, ?string $name = null)
    {
        /* @var Smarty $smarty */
        $smarty = $obj->_getSmartyObj();
        $dataObj = new Smarty_Data($parent, $smarty, $name);
        if ($smarty->debugging) {
            Smarty_Internal_Debug::register_data($dataObj);
        }
        return $dataObj;
    }
}
