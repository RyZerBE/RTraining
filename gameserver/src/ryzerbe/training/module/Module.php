<?php

namespace ryzerbe\training\module;

use pocketmine\event\Listener;
use ReflectionClass;

abstract class Module implements Listener {
    private string $shortname;

    public function getShortname(): string{
        return $this->shortname;
    }

    public function __construct(){
        $reflection = new ReflectionClass(self::class);
        $this->shortname = $reflection->getShortName();
    }

    public function onLoad(): void {}
}