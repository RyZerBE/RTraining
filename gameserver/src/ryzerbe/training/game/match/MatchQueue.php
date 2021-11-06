<?php

namespace ryzerbe\training\game\match;

use function array_search;

class MatchQueue {
    /** @var MatchRequest[]  */
    public static array $queue = [];

    /**
     * @return MatchRequest[]
     */
    public static function getRequests(): array{
        return self::$queue;
    }

    public static function addQueue(MatchRequest $request){
        self::$queue[] = $request;
    }

    public static function removeQueue(MatchRequest $request){
        unset(self::$queue[array_search($request, self::$queue)]);
    }
}