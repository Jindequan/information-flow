<?php 

namespace App\Exceptions;

use App\Exceptions\Base;

class ConsoleException extends Base {
    public function __construct($message, $code = 0, Base $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getFormattedMessage() {
        $redColorCode = "\033[31m";
        $resetColorCode = "\033[0m";

        $errorMessage = "[ConsoleException] \n" . 
                        " " . $this->getMessage() .
                        " in " . $this->getFile() .
                        " on line " . $this->getLine();
        return $redColorCode . $errorMessage . $resetColorCode . PHP_EOL;
    }
}