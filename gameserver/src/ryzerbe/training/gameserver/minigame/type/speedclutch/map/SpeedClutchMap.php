<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\speedclutch\map;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use ryzerbe\training\gameserver\minigame\type\speedclutch\SpeedClutchGameSession;

class SpeedClutchMap {
    public const TOTAL_MODULES = 5;

    private int $seed;

    /** @var int[]  */
    private array $modulePositions = [];

    public function __construct(int $seed){
        $this->seed = $seed;
    }

    public function getSeed(): int{
        return $this->seed;
    }

    public function generate(SpeedClutchGameSession $gameSession): void {
        $random = new Random($this->getSeed());

        $originVector3 = $gameSession->getSpawn()->floor();
        $tempVector3 = $originVector3->asVector3();
        $tempVector3->y--;
        $tempVector3->z = 10;

        $this->generatePath($tempVector3, $gameSession, $random, 2, 4);
        for($module = 1; $module <= self::TOTAL_MODULES; $module++) {
            switch($random->nextMinMax(1, 4)) {
                default: {// Default obstacle
                    $this->generatePath($tempVector3, $gameSession, $random, 0, 4);

                    $length = $random->nextRange(5, 10);
                    for($z = 0; $z <= $length; $z++) {
                        $height = $random->nextRange(1, 4);
                        $placed = 0;
                        for($y = 1; $y <= $height; $y++) {
                            if($random->nextRange(0, (9 - $y)) === 0) continue;
                            $placed++;
                            $gameSession->placeBlock($tempVector3->add(0, $y, $z), Block::get(BlockIds::CONCRETE, 14));
                        }
                        if($placed <= 0) {
                            $gameSession->placeBlock($tempVector3->add(0, 0, $z), Block::get(BlockIds::AIR));
                        }
                    }
                    $tempVector3->z += $length;
                    break;
                }
                case 1: {// Wall obstacle
                    $this->generatePath($tempVector3, $gameSession, $random, 3, 5);
                    $amount = ($random->nextRange(0, 3) === 0 ? $random->nextRange(1, 4) : 1);
                    for($wall = 1; $wall <= $amount; $wall++){
                        $wallLength = $random->nextRange(1, 5);
                        $wallHeight = $random->nextRange(3, 5);
                        for($x = -$wallLength; $x <= $wallLength; $x++) {
                            for($y = 0; $y <= $wallHeight; $y++) {
                                $gameSession->placeBlock($tempVector3->add($x, $y, 0), Block::get(BlockIds::CONCRETE, 14));
                            }
                        }
                        $tempVector3->z++;
                    }
                    $this->generatePath($tempVector3, $gameSession, $random, 3, 5);
                    break;
                }
                case 2: {// Gap obstacle
                    $this->generatePath($tempVector3, $gameSession, $random, 1, 3, true, Block::get(BlockIds::AIR));
                    break;
                }
            }
        }
        $this->generatePath($tempVector3, $gameSession, $random, 1, 4, false);
        $gameSession->placeBlock($tempVector3, Block::get(BlockIds::REDSTONE_BLOCK));
        $gameSession->placeBlock($tempVector3->add(0, 1, 0), Block::get(BlockIds::LIGHT_WEIGHTED_PRESSURE_PLATE));
    }

    private function generatePath(Vector3 &$vector3, SpeedClutchGameSession $gameSession, Random $random, int $minLength = 0, int $maxLength = 1, bool $randomValue = true, ?Block $block = null): void {
        $length = ($randomValue ? $random->nextRange($minLength, $maxLength) : $maxLength);
        $block = $block ?? Block::get(BlockIds::IRON_BLOCK);
        for($z = ($randomValue ? 0 : $minLength); $z <= $length; $z++) {
            $gameSession->placeBlock($vector3, $block);
            $vector3->z++;
        }
    }

    public function getModuleByPosition(Vector3 $vector3): ?int {
        $hash = Level::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ());
        foreach($this->modulePositions as $module => $moduleHash) {
            if($hash === $moduleHash) return $module;
        }
        return null;
    }

    private function registerModulePosition(int $module, Vector3 $vector3): void {
        $this->modulePositions[$module] = Level::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ());
    }
}