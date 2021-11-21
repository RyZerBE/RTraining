<?php

namespace ryzerbe\training\gameserver\util;

use pocketmine\utils\MainLogger;
use function debug_backtrace;

class Logger {
    public static function error(string $error): void {
        $debug = debug_backtrace();
        MainLogger::getLogger()->error($error." in method ".$debug[1]["function"]."#".$debug[0]["line"]);
    }
}