<?php

namespace ryzerbe\training\gameserver\minigame\trait;

use pocketmine\block\Block;
use pocketmine\level\Level;

trait BlockStorageTrait {
    private array $blocks = [];

    public function addBlock(Block $block, string $identifier = "default"): void {
        $this->blocks[$identifier][Level::blockHash($block->x, $block->y, $block->z)] = $block->asPosition();
    }

    public function removeBlock(Block|int $hash, string $identifier = "default"): void {
        if($hash instanceof Block) $hash = Level::blockHash($hash->x, $hash->y, $hash->z);
        unset($this->blocks[$identifier][$hash]);
    }

    public function resetBlocks(string $identifier = "default"): void {
        foreach(($this->blocks[$identifier] ?? []) as $position) {
            $position->getLevel()->setBlockIdAt($position->x, $position->y, $position->z, 0);
            $position->getLevel()->setBlockDataAt($position->x, $position->y, $position->z, 0);
        }
        $this->blocks[$identifier] = [];
    }
}