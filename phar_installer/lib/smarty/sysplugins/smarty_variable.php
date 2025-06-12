<?php

/**
 * class for the Smarty variable object
 * This class defines the Smarty variable object
 *
 * @package    Smarty
 * @subpackage Template
 */
#[\AllowDynamicProperties]
class Smarty_Variable
{
    /**
     * the variable value
     *
     * @var mixed
     */
    public $value = null;

    /**
     * whether the value of this variable will NOT be cached
     *
     * @var bool Default false
     */
    public $nocache = false;

    //various 'dynamic' properties might also be used

    /**
     * create Smarty variable object
     *
     * @param mixed   $value   the value to assign
     * @param boolean $nocache if true any output of this variable will be not cached
     */
    public function __construct($value = null, $nocache = false)
    {
        $this->value = $value;
        $this->nocache = $nocache;
    }

    /**
     * <<magic>> String conversion
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}
