<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\listener\level;

use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\Listener;
use pocketmine\level\biome\Biome;
use ryzerbe\training\lobby\util\LevelSettings;

class ChunkLoadListener implements Listener{
    public function onChunkLoad(ChunkLoadEvent $event): void {
        if(LevelSettings::SNOW) {
            for($x = 0; $x < 16; ++$x){
                for($z = 0; $z < 16; ++$z){
                    $event->getChunk()->setBiomeId($x, $z, Biome::ICE_PLAINS);
                }
            }
        }
    }
}