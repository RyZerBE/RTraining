<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\hitblockclutch\map;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\HitBlockClutchGameSession;

class HitBlockClutchMap {
    public const TOTAL_MODULES = 4;

    private int $seed;

    /** @var int[]  */
    private array $enemyPositions = [];
    /** @var int[]  */
    private array $modulePositions = [];

    public function __construct(int $seed){
        $this->seed = $seed;
    }

    public function getSeed(): int{
        return $this->seed;
    }

    public function generate(HitBlockClutchGameSession $gameSession): void {
        $random = new Random($this->getSeed());

        $this->enemyPositions = [];

        $originVector3 = $gameSession->getSpawn()->floor();
        $tempVector3 = $originVector3->asVector3();
        $tempVector3->y--;
        $tempVector3->z = 10;

        for($module = 1; $module <= self::TOTAL_MODULES; $module++) {
            $nextMinMax = $random->nextRange(4, 6);
            for($i = 1; $i <= $nextMinMax; $i++) {
                $tempVector3->z++;
                $gameSession->placeBlock($tempVector3, Block::get(BlockIds::IRON_BLOCK));
            }
            $tempVector3->z++;
            $gameSession->placeBlock($tempVector3, Block::get(BlockIds::EMERALD_BLOCK));
            $this->registerModulePosition($module, $tempVector3);

            $tempVector3->y += $random->nextRange(6, 10);

            $nextMinMax = $random->nextRange(3, 7);
            for($i = 1; $i <= 7; $i++) {
                $gameSession->placeBlock($tempVector3->add(0, $i, -1), Block::get(BlockIds::INVISIBLE_BEDROCK));
            }

            for($i = 1; $i <= $nextMinMax; $i++) {
                $gameSession->placeBlock($tempVector3, Block::get(BlockIds::IRON_BLOCK));
                $tempVector3->z++;
            }

            switch($random->nextMinMax(1, 3)) {
                default: {// Default jump
                    $tempVector3->z += $random->nextRange(3, 4);

                    $tempVector3->y += $random->nextRange(2, 4);
                    $this->registerEnemyPosition($module, $tempVector3);

                    $currentY = $tempVector3->y;
                    $targetY = $tempVector3->y - $random->nextRange(6, 15);
                    for($i = $currentY; $i >= $targetY; $i--) {
                        $tempVector3->y--;
                        $gameSession->placeBlock($tempVector3, Block::get(BlockIds::CONCRETE, 12));
                    }
                    $tempVector3->z++;
                    break;
                }
                case 1: {// Two-Zombies jump
                    $tempVector3->z += $random->nextRange(3, 4);

                    $tempVector3->y += $random->nextRange(2, 3);
                    $this->registerEnemyPosition($module, $tempVector3->add(1));
                    $this->registerEnemyPosition($module, $tempVector3->add(-1));

                    $currentY = $tempVector3->y;
                    $targetY = $tempVector3->y - $random->nextRange(9, 15);
                    for($i = $currentY; $i >= $targetY; $i--) {
                        $tempVector3->y--;
                        $gameSession->placeBlock($tempVector3->add(1), Block::get(BlockIds::CONCRETE, 12));
                        $gameSession->placeBlock($tempVector3->subtract(1), Block::get(BlockIds::CONCRETE, 12));
                    }
                    $tempVector3->z++;
                    break;
                }
            }
        }
        for($i = 1; $i <= 6; $i++) {
            $tempVector3->z++;
            $gameSession->placeBlock($tempVector3, Block::get(BlockIds::IRON_BLOCK));
        }
        $gameSession->placeBlock($tempVector3, Block::get(BlockIds::REDSTONE_BLOCK));
        $gameSession->placeBlock($tempVector3->add(0, 1, 0), Block::get(BlockIds::LIGHT_WEIGHTED_PRESSURE_PLATE));
    }

    /**
     * @return Vector3[]
     */
    public function getEnemyPositions(int $module): array{
        $positions = [];
        foreach(($this->enemyPositions[$module] ?? []) as $hash) {
            Level::getBlockXYZ($hash, $x, $y, $z);
            $positions[] = new Vector3($x + 0.5, $y, $z + 0.5);
        }
        return $positions;
    }

    public function getModuleByPosition(Vector3 $vector3): ?int {
        $hash = Level::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ());
        foreach($this->modulePositions as $module => $moduleHash) {
            if($hash === $moduleHash) return $module;
        }
        return null;
    }

    private function registerEnemyPosition(int $module, Vector3 $vector3): void {
        $this->enemyPositions[$module][] = Level::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ());
    }

    private function registerModulePosition(int $module, Vector3 $vector3): void {
        $this->modulePositions[$module] = Level::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ());
    }
}