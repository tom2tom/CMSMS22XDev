<?php

/**
 * Smarty compiler exception class
 *
 * @package Smarty
 */
class SmartyCompilerException extends SmartyException
{
    /**
     * The constructor of the exception
     *
     * @param string         $message  The Exception message. Default ''
     * @param int            $code     The Exception code. Default 0
     * @param string|null    $filename The filename where the exception was thrown Default null
     * @param int|null       $line     The line number where the exception was thrown. Default null
     * @param Throwable|null $previous The previous exception used for the exception chaining. Default null
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?string $filename = null,
        ?int $line = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        // These are optional parameters, should be be overridden only when present!
        if ($filename) {
            $this->file = $filename;
        }
        if ($line) {
            $this->line = $line;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ' --> Smarty Compiler: ' . $this->message . ' <-- ';
    }

    /**
     * @param int $line
     */
    public function setLine($line)
    {
        $this->line = $line;
    }

    /**
     * The template source snippet relating to the error
     *
     * @type string|null
     */
    public $source = null;

    /**
     * The raw text of the error message
     *
     * @type string|null
     */
    public $desc = null;

    /**
     * The resource identifier or template name
     *
     * @type string|null
     */
    public $template = null;
}
