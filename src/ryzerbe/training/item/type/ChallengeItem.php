<?php

namespace ryzerbe\training\item\type;

use baubolp\core\provider\LanguageProvider;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\Player;
use ryzerbe\training\form\type\TeamRequestForm;
use ryzerbe\training\item\TrainingItem;
use ryzerbe\training\player\TrainingPlayerManager;
use ryzerbe\training\Training;

class ChallengeItem extends TrainingItem {

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onHit(EntityDamageByEntityEvent $event){
        $entity = $event->getEntity();
        $hitter = $event->getDamager();
        $event->setCancelled();
        if($hitter instanceof Player && $entity instanceof Player) {
            $item = $hitter->getInventory()->getItemInHand();
            if(!$this->checkItem($item)) return;
            if($hitter->hasItemCooldown($item)) return;
            $hitter->resetItemCooldown($item, 20);

            $willChallenge = TrainingPlayerManager::getPlayer($entity);
            $challenger = TrainingPlayerManager::getPlayer($hitter);
            if($willChallenge === null || $challenger === null) return;

            if($willChallenge->getTeam() === null && $challenger->getTeam() !== null) {
                $hitter->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-teams-only-challenge-teams", $hitter->getName()));
                return;
            }

            if($willChallenge->getTeam() !== null && $challenger->getTeam() === null){
                $hitter->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-already-in-team", $hitter->getName()));
                return;
            }

            if($willChallenge->getTeam() !== null && $challenger->getTeam() !== null) {
                $creatorName = $willChallenge->getTeam()->getCreator()->getPlayer()->getName();
                if($creatorName != $entity->getName()) {
                    $hitter->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-isnt-team-creator", $hitter->getName(), ["#creator" => $creatorName]));
                    return;
                }
            }

            if(!$willChallenge->getPlayerSettings()->allowChallengeRequests()) {
                $hitter->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-challenge-request-disabled", $hitter->getName()));
                return;
            }
            $willChallenge->challenge($challenger);
        }
    }

    /**
     * @param PlayerInteractEntityEvent $event
     */
    public function onEntityInteract(PlayerInteractEntityEvent $event){
        $entity = $event->getEntity();
        $player = $event->getPlayer();

        if(!$entity instanceof Player) return;
        $item = $player->getInventory()->getItemInHand();
        if(!$this->checkItem($item)) return;
        if($player->hasItemCooldown($item)) return;
        $player->resetItemCooldown($item, 20);

        $trainingEntity = TrainingPlayerManager::getPlayer($entity);
        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingEntity === null) return;
        if($trainingPlayer === null) return;

        if($trainingEntity->getTeam() !== null){
            $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-player-already-in-team", $player->getName()));
            return;
        }

        if($trainingPlayer->getTeam() !== null){
            $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-already-in-team", $player->getName()));
            return;
        }

        if($trainingEntity->hasTeamRequest($player)){
            $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-already-invited-team", $player->getName()));
            return;
        }

        if(!$trainingEntity->getPlayerSettings()->allowTeamRequests()) {
            $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-team-request-disabled", $player->getName()));
            return;
        }

        TeamRequestForm::open($player, ["playerName" => $entity->getName()]);
    }
}