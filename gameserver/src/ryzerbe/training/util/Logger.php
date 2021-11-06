<?php

namespace ryzerbe\training\util;

use pocketmine\utils\MainLogger;
use function debug_backtrace;

class Logger {
    public static function error(string $error): void {
        $debug = debug_backtrace();
        MainLogger::getLogger()->error($error." in function ".$debug[1]["function"]."#".$debug[0]["line"]);
    }
}