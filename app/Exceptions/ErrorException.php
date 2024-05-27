<?php 

namespace App\Exceptions;

use App\Exceptions\Base;

class ErrorException extends Base {
    public function __construct($message, $code = 0, Base $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getFormattedMessage() {
        $errorMessage = "[ErrorException] \n" . 
                        " " . $this->getMessage() .
                        " in " . $this->getFile() .
                        " on line " . $this->getLine();
        return $errorMessage . PHP_EOL;
    }
}