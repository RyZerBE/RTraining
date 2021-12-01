<?php

namespace ryzerbe\training\gameserver\game\spectate;

use function array_search;

class SpectateQueue {
    /** @var SpectateRequest[]  */
    public static array $queue = [];

    public static function getQueue(): array{
        return self::$queue;
    }

    public static function addRequest(SpectateRequest $request){
        self::$queue[] = $request;
    }

    public static function removeRequest(SpectateRequest $request){
        unset(self::$queue[array_search($request, self::$queue)]);
    }
}