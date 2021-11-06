<?php

namespace ryzerbe\training\gameserver\minigame\type\aimtrainer\generator;

use pocketmine\block\BlockIds;
use pocketmine\block\StoneBricks;
use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class AimTrainerGenerator extends Generator {
    public function __construct(array $settings = []){}

    public function generateChunk(int $chunkX, int $chunkZ): void{
        $chunk = $this->level->getChunk($chunkX, $chunkZ);
        if(($chunkX + 1) % 2 === 0) {
            for($z = 0; $z <= 15; $z++) {
                for($y = 0; $y <= Level::Y_MAX; $y++) {
                    $chunk->setBlock(8, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                    $chunk->setBlock(9, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                }
            }
        }
        if($chunkZ === 0 && $chunkX % 2 === 0){
            $chunk->setBlock(8, 50, 8, BlockIds::STONE_BRICK, StoneBricks::CHISELED);
        }
    }

    public function populateChunk(int $chunkX, int $chunkZ): void{}

    public function getSettings(): array{
        return [];
    }

    public function getName(): string{
        return "aimtrainer";
    }

    public function getSpawn(): Vector3{
        return new Vector3(0, 50, 0);
    }
}