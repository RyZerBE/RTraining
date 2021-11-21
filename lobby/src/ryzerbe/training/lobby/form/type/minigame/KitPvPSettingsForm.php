<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type\minigame;

use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\training\lobby\inventory\InventorySortManager;
use ryzerbe\training\lobby\kit\KitManager;
use ryzerbe\training\lobby\player\TrainingPlayerManager;

class KitPvPSettingsForm {
    public static function open(Player $player): void {
        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingPlayer === null) return;
        $form = new SimpleForm(function(Player $player, $data) use ($trainingPlayer): void{
            if($data === null) return;
            switch($data){
                case "kits": {
                    $playername = $player->getName();
                    AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($playername): string {
                        $query = $mysqli->query("SELECT kit_name FROM kitpvp_kits_player WHERE playername='$playername'");
                        if($query->num_rows <= 0) return "OnlySword";
                        return $query->fetch_assoc()["kit_name"];
                    }, function(Server $server, string $selectedKit) use ($player, $trainingPlayer): void {
                        $form = new SimpleForm(function(Player $player, mixed $data): void {
                            if($data === null) return;
                            $kit = KitManager::getInstance()->getKitByName($data);
                            if($kit === null) return;
                            $form = new SimpleForm(function(Player $player, mixed $data) use ($kit): void {
                                if($data === null) return;
                                switch($data) {
                                    case "select": {
                                        $kitName = $kit->getName();
                                        $playerName = $player->getName();
                                        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($kitName, $playerName): void{
                                            $mysqli->query("UPDATE `kitpvp_kits_player` SET kit_name='$kitName' WHERE playername='$playerName'");
                                        }, function() use ($playerName, $kit): void{
                                            $trainingPlayer = TrainingPlayerManager::getPlayer($playerName);
                                            if($trainingPlayer === null) return;
                                            $trainingPlayer->setKit($kit);
                                            $trainingPlayer->getPlayer()->playSound("random.levelup", 5.0, 1.0, [$trainingPlayer->getPlayer()]);
                                        });
                                        break;
                                    }
                                    case "sort": {
                                        InventorySortManager::getInstance()->loadSession($player, "KitPvP", $kit->getName(), null);
                                        break;
                                    }
                                }
                            });
                            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Select", 0, "", "select");
                            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Sort Inventory", 0, "", "sort");
                            $form->sendToPlayer($player);
                        });
                        foreach(KitManager::getInstance()->getKits() as $kit){
                            $form->addButton(($kit->getName() !== $selectedKit ? TextFormat::DARK_GRAY : TextFormat::GREEN)."⇨ ".TextFormat::GREEN.$kit->getName(), 0, "", $kit->getName());
                        }
                        $form->sendToPlayer($player);
                    });
                    break;
                }
            }
        });

        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Kits", -1, "", "kits");
        $form->sendToPlayer($player);
    }
}