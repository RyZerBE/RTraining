<?php

namespace ryzerbe\training\gameserver\minigame\type\aimtrainer;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItemManager;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\item\MinigameHubItem;
use ryzerbe\training\gameserver\minigame\Minigame;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\entity\AimTrainerEntity;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\generator\AimTrainerGenerator;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\item\AimTrainerConfigurationItem;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\item\AimTrainerResetItem;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use function in_array;
use function mt_rand;

class AimTrainerMinigame extends Minigame {
    private Level $level;

    /**
     * @throws ReflectionException
     */
    public function __construct(){
        parent::__construct();

        Entity::registerEntity(AimTrainerEntity::class, true);
        $items = [
            new AimTrainerConfigurationItem(Item::get(ItemIds::BOOK)->setCustomName(TextFormat::RED."Settings"), 8),
            new AimTrainerResetItem(Item::get(ItemIds::DYE, 15)->setCustomName(TextFormat::RED."Reset"), 5),
        ];
        CustomItemManager::getInstance()->registerAll($items);

        GeneratorManager::addGenerator(AimTrainerGenerator::class, "aimtrainer", true);
        Server::getInstance()->generateLevel("AimTrainer", mt_rand(), AimTrainerGenerator::class);
        $this->level = Server::getInstance()->getLevelByName("AimTrainer");
        $this->level->setTime(6000);
        $this->level->stopTime();
        $this->level->setAutoSave(true); //no despawn of AimTrainer Entity
    }

    public function getName(): string{
        return "Aim Trainer";
    }

    public function getLevel(): Level{
        return $this->level;
    }

    public function initSettings(): void{
        $this->settings = new AimTrainerSettings();
    }

    public function constructGameSession(Session $session): GameSession{
        $usedIds = [];
        foreach($this->getSessionManager()->getSessions() as $Session) {
            $gameSession = $Session->getGameSession();
            if($gameSession instanceof AimTrainerGameSession) $usedIds[] = $gameSession->getPlatformId();
        }
        $id = 0;
        while(in_array($id, $usedIds)) $id++;
        return new AimTrainerGameSession($session, $this->getLevel(), $id);
    }

    public function onDamage(EntityDamageEvent $event): void{
        if($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $entity = $event->getEntity();
            if(!$entity instanceof Player) return;
            $gameSession = SessionManager::getInstance()->getSessionOfPlayer($entity)?->getGameSession();
            if(!$gameSession instanceof AimTrainerGameSession) return;
            $event->setCancelled();

            $entity->teleport($gameSession->getSpawn());
        }
    }

    public function onLoad(Session $session): void{
        /** @var AimTrainerGameSession $gameSession */
        $gameSession = $session->getGameSession();

        /** @var PMMPPlayer $player */
        $player = $session->getPlayer();
        if($player === null) return;

        $player->teleport($gameSession->getSpawn());
        $player->setImmobile(false);

        CustomItemManager::getInstance()->getCustomItemByClass(AimTrainerConfigurationItem::class)?->giveToPlayer($player, MinigameDefaultSlots::SLOT_CONFIGURATION_ITEM);
        CustomItemManager::getInstance()->getCustomItemByClass(MinigameHubItem::class)?->giveToPlayer($player, MinigameDefaultSlots::SLOT_LEAVE_ITEM);

        $gameSession->sendScoreboard();
        $this->scheduleUpdate($session);
    }

    public function onUnload(Session $session): void{
        /** @var AimTrainerGameSession $gameSession */
        $gameSession = $session->getGameSession();

        $gameSession->getEntity()?->flagForDespawn();
        $gameSession->getLevel()->setBlock($gameSession->getBlockPosition(), Block::get(BlockIds::AIR));

        $player = $session->getPlayer();
        if($player === null) return;

        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
    }
}