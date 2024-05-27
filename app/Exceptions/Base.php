<?php 

namespace App\Exceptions;

use Exception;

abstract class Base extends Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        if ($code < 0) {
            $message = $previous->getMessage();
            $code = $previous->getCode();
        }
        parent::__construct($message, $code, $previous);
    }

    abstract public function getFormattedMessage();
}