<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\hitblockclutch\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\HitBlockClutchGameSession;
use ryzerbe\training\gameserver\session\SessionManager;

class HitBlockClutchMinigameResetItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 40);
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof HitBlockClutchGameSession) return;
        if($gameSession->isTimerRunning()){
            $gameSession->stopTimer();
            $gameSession->resetGame();
        }
    }
}