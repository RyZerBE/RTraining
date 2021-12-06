<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\minigame\Minigame;
use ryzerbe\training\lobby\minigame\MinigameManager;
use ryzerbe\training\lobby\tournament\TournamentManager;
use function array_filter;
use function array_map;
use function array_values;
use function boolval;
use function intval;

class TournamentConfigurationForm {
    public static function open(Player $player): void {
        $minigames = array_values(array_map(function(Minigame $minigame): string {
            return $minigame->getName();
        }, array_filter(MinigameManager::getInstance()->getMinigames(), function(Minigame $minigame): bool {
            return $minigame->isMultiplayer();
        })));
        $form = new CustomForm(function(Player $player, mixed $data) use ($minigames): void {
            if($data === null) return;

            $public = boolval($data["public"]);
            $players = intval($data["players"]);
            $minigame = MinigameManager::getInstance()->getMinigame($minigames[$data["minigame"]] ?? "Unknown");

            $tournament = TournamentManager::createTournament($player, $minigame, $players, true);
            if(!$public) TournamentInviteMembersForm::open($player, $tournament);

            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
            $player->sendMessage(LanguageProvider::getMessageContainer("tournament-created", $player));
            //TODO: Message

            /*
            $pk = new MatchPacket();
            $pk->addData("group", "Training");
            $pk->addData("minigame", "MLGRush");
            $pk->addData("tournament", "1");
            $pk->addData("players", json_encode([$player->getName()]));
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            */
        });
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Create Tournament");
        $form->addSlider("§cPlayers", 4, 16, 2, 4, "players");
        $form->addDropdown("§cMinigame", $minigames, 0, "minigame");
        $form->addToggle("§cPublic", true, "public");
        $form->sendToPlayer($player);
    }
}