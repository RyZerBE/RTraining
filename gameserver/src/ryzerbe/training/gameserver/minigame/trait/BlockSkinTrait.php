<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\trait;

use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;

trait BlockSkinTrait {
    private ?Item $blockSkin = null;

    /**
     * @return ItemBlock
     */
    public function getBlockSkin(): Item {
        return $this->blockSkin ?? Item::get(BlockIds::SANDSTONE, 0, 64);
    }

    public function setBlockSkin(?Item $blockSkin): void{
        $this->blockSkin = $blockSkin->setCount(64);
    }

    public function getBlockSkinKey(): int {
        $blockSkin = $this->getBlockSkin();
        foreach($this->getBlockSkins() as $key => $skin) {
            if($blockSkin->equals($skin, true, false)) {
                return $key;
            }
        }
        return 0;
    }

    /**
     * @return ItemBlock[]
     */
    public function getBlockSkins(): array {
        return [
            Item::get(BlockIds::SANDSTONE)
        ];
    }
}