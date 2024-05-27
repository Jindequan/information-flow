<?php
namespace App\Core;

use App\Exceptions\ConsoleException;

class ErrorHandler {
    public static function handleException($exception) {
        if ($exception instanceof ConsoleException) {
            echo $exception->getFormattedMessage();
        } else {
            $redColorCode = "\033[31m"; 
            $resetColorCode = "\033[0m";

            $errorMessage = "[Exception] " . $exception->getMessage() .
                            " in " . $exception->getFile() .
                            " on line " . $exception->getLine();
            echo $redColorCode . $errorMessage . $resetColorCode . PHP_EOL;
        }
    }
}
