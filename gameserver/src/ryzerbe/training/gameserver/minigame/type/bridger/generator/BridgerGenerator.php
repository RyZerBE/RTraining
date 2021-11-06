<?php

namespace ryzerbe\training\gameserver\minigame\type\bridger\generator;

use pocketmine\block\BlockIds;
use pocketmine\block\StoneBricks;
use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use ryzerbe\training\gameserver\minigame\type\bridger\BridgerMinigame;
use function mt_rand;

class BridgerGenerator extends Generator {
    public function __construct(array $settings = []){}

    public function generateChunk(int $chunkX, int $chunkZ): void{
        $chunk = $this->level->getChunk($chunkX, $chunkZ);
        if(($chunkX + 5) % 10 === 0) {
            for($z = 0; $z <= 15; $z++) {
                for($y = 0; $y <= Level::Y_MAX; $y++) {
                    $chunk->setBlock(8, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                    $chunk->setBlock(9, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                }
            }
        }
        if($chunkZ === 0 && $chunkX % 10 === 0) {
            //Hardcoded.... I know but I don´t care. lol

            $chunk->setBlock(8, BridgerMinigame::BASE_Y, 8, BlockIds::STONE_BRICK, StoneBricks::CHISELED);
            $chunk->setBlock(8, (BridgerMinigame::BASE_Y - 1), 8, BlockIds::COBBLESTONE_WALL);
            $chunk->setBlock(8, (BridgerMinigame::BASE_Y - 2), 8, BlockIds::FENCE, 5);

            $chunk->setBlock(7, BridgerMinigame::BASE_Y, 8, BlockIds::STONE_BRICK);
            $chunk->setBlock(9, BridgerMinigame::BASE_Y, 8, BlockIds::STONE_BRICK);

            $chunk->setBlock(8, BridgerMinigame::BASE_Y, 7, BlockIds::DOUBLE_STONE_SLAB);
            $chunk->setBlock(8, BridgerMinigame::BASE_Y, 9, BlockIds::DOUBLE_STONE_SLAB);

            $meta = mt_rand(0, 15);
            $chunk->setBlock(7, BridgerMinigame::BASE_Y, 9, BlockIds::TERRACOTTA, $meta);
            $chunk->setBlock(9, BridgerMinigame::BASE_Y, 7, BlockIds::TERRACOTTA, $meta);
            $chunk->setBlock(7, BridgerMinigame::BASE_Y, 7, BlockIds::TERRACOTTA, $meta);
            $chunk->setBlock(9, BridgerMinigame::BASE_Y, 9, BlockIds::TERRACOTTA, $meta);

            $chunk->setBlock(7, (BridgerMinigame::BASE_Y - 1), 9, BlockIds::STONE_SLAB, 8);
            $chunk->setBlock(9, (BridgerMinigame::BASE_Y - 1), 7, BlockIds::STONE_SLAB, 8);
            $chunk->setBlock(7, (BridgerMinigame::BASE_Y - 1), 7, BlockIds::STONE_SLAB, 8);
            $chunk->setBlock(9, (BridgerMinigame::BASE_Y - 1), 9, BlockIds::STONE_SLAB, 8);

            $chunk->setBlock(8, (BridgerMinigame::BASE_Y - 1), 9, BlockIds::STONE_SLAB, 13);
            $chunk->setBlock(8, (BridgerMinigame::BASE_Y - 1), 7, BlockIds::STONE_SLAB, 13);
            $chunk->setBlock(9, (BridgerMinigame::BASE_Y - 1), 8, BlockIds::STONE_SLAB, 13);
            $chunk->setBlock(7, (BridgerMinigame::BASE_Y - 1), 8, BlockIds::STONE_SLAB, 13);

            $chunk->setBlock(6, (BridgerMinigame::BASE_Y - 1), 8, BlockIds::STONE_SLAB, 8);
            $chunk->setBlock(10, (BridgerMinigame::BASE_Y - 1), 8, BlockIds::STONE_SLAB, 8);

            $chunk->setBlock(6, BridgerMinigame::BASE_Y, 8, BlockIds::STONE_BRICK_STAIRS, 4);
            $chunk->setBlock(10, BridgerMinigame::BASE_Y, 8, BlockIds::STONE_BRICK_STAIRS, 5);
        }
        $chunk->setGenerated();
    }

    public function populateChunk(int $chunkX, int $chunkZ): void{

    }

    public function getSettings(): array{
        return [];
    }

    public function getName(): string{
        return "bridger";
    }

    public function getSpawn(): Vector3{
        return new Vector3(0, 50, 0);
    }
}