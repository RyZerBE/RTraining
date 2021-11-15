<?php

namespace ryzerbe\training\lobby;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\level\Location;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use ReflectionException;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\training\lobby\entity\NPCEntity;
use ryzerbe\training\lobby\item\TrainingItemManager;
use ryzerbe\training\lobby\kit\EnchantCommand;
use ryzerbe\training\lobby\kit\KitCommand;
use ryzerbe\training\lobby\kit\KitManager;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use ryzerbe\training\lobby\scheduler\TrainingTask;
use ryzerbe\training\lobby\util\SkinUtils;
use function is_dir;
use function json_encode;
use function scandir;
use function str_replace;
use function uniqid;

class Training extends PluginBase {
    public const PREFIX = TextFormat::BLUE.TextFormat::BOLD."Training ".TextFormat::RESET;

    /** @var Training|null  */
    public static ?Training $instance = null;

    /**
     * @throws ReflectionException
     */
    public function onEnable(){
        self::$instance = $this;

        TrainingItemManager::getInstance();

        $this->initListener(__DIR__."/listener/");
        $this->spawnEntities();

        $this->getScheduler()->scheduleRepeatingTask(new TrainingTask(), 1);

        Entity::registerEntity(NPCEntity::class, true);

        KitManager::getInstance()->loadKits();

        $this->getServer()->getCommandMap()->registerAll("training", [
            new KitCommand(),
            new EnchantCommand()
        ]);
    }

    public function spawnEntities(): void{
        $config = new Config("/root/RyzerCloud/data/NPC/default_geometry.json");
        $skin = new Skin(
            uniqid(),
            SkinUtils::readImage("/root/RyzerCloud/data/NPC/backup_skin.png"),
            "",
            $config->get("name"),
            $config->get("geo")
        );
        // CONFIGURATIONS \\
        $level = Server::getInstance()->getDefaultLevel();
        $npcEntity = new NPCEntity(new Location(2.5, 114, -5.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::AQUA."Configurations", TextFormat::WHITE."Click to configure");
        $closure = function(Player $player, NPCEntity $entity): void{
            $form = new SimpleForm(function(Player $player, $data): void{
                if($data === null) return;

                $trainingPlayer = TrainingPlayerManager::getPlayer($player);
                if($trainingPlayer === null) return;

                switch($data) {
                    case "Lobby":
                        $form = new CustomForm(function(Player $player, $data) use ($trainingPlayer): void{
                            if($data === null) return;

                            $trainingPlayer->getPlayerSettings()->setTeamRequests($data["team_requests"]);
                            $trainingPlayer->getPlayerSettings()->setChallengeRequests($data["match_requests"]);
                            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
                        });

                        $form->addToggle(LanguageProvider::getMessageContainer("training-team-request-setting", $player->getName()), $trainingPlayer->getPlayerSettings()->allowTeamRequests(), "team_requests");
                        $form->addToggle(LanguageProvider::getMessageContainer("training-match-request-setting", $player->getName()), $trainingPlayer->getPlayerSettings()->allowTeamRequests(), "match_requests");
                        $form->sendToPlayer($player);
                        break;
                    case "KitPvP":
                        $form = new SimpleForm(function(Player $player, $data) use ($trainingPlayer): void{
                            if($data === null) return;

                            switch($data){
                                case "kits":
                                    $kits = [];
                                    $actions = ["Select", "Sort"];
                                    foreach(KitManager::getInstance()->getKits() as $kit) {
                                        $kits[] = $kit->getName();
                                    }
                                    $form = new CustomForm(function(Player $player, $data) use ($trainingPlayer, $actions, $kits): void{
                                        if($data === null) return;

                                        $action = $actions[$data["action"]];
                                        $kitName = $kits[$data["kits"]];

                                        $kit = KitManager::getInstance()->getKitByName($kitName);
                                        if($kit === null) return;

                                        $playerName = $player->getName();
                                        switch($action) {
                                            case "Select":
                                                AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($kitName, $playerName): void{
                                                    $mysqli->query("UPDATE `kitpvp_kits_player` SET kit_name='$kitName' WHERE playername='$playerName'");
                                                }, function(Server $server, $result) use ($playerName, $kit): void{
                                                    $trainingPlayer = TrainingPlayerManager::getPlayer($playerName);
                                                    if($trainingPlayer === null) return;

                                                    $trainingPlayer->setKit($kit);
                                                    $trainingPlayer->getPlayer()->playSound("random.levelup", 5.0, 1.0, [$trainingPlayer->getPlayer()]);
                                                });
                                                break;
                                            case "Sort":
                                                KitManager::getInstance()->loadPlayerKitToSort($player, $kitName);
                                                break;
                                        }
                                    });

                                    $form->addDropdown("Kits", $kits, null, "kits");
                                    $form->addDropdown("Action", $actions, null, "action");
                                    $form->sendToPlayer($player);
                                    break;
                            }
                        });

                        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Kits", -1, "", "kits");
                        $form->sendToPlayer($player);
                        break;
                }
            });

            $form->setContent(LanguageProvider::getMessageContainer("training-configuration-select-game", $player->getName()));
            $form->setTitle(TextFormat::AQUA.TextFormat::BOLD."Settings");
            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::YELLOW.TextFormat::BOLD." Lobby", -1, "", "Lobby");
            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::BLUE.TextFormat::BOLD." KitPvP", -1, "", "KitPvP");
            $form->sendToPlayer($player);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();

        // MLG-Training \\
        $npcEntity = new NPCEntity(new Location(5.5, 115, -5.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::GOLD.TextFormat::BOLD."MLG-Training", TextFormat::WHITE."Clutches and more");
        $closure = function(Player $player, NPCEntity $entity): void{
            $form = new SimpleForm(function(Player $player, $data): void{
                if($data === null) return;

                if($data === "soon") return;

                $pk = new MatchPacket();
                $pk->addData("group", "Training");
                $pk->addData("minigame", $data);
                $pk->addData("players", json_encode([$player->getName()]));
                CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            });

            $form->setTitle(TextFormat::GOLD."MLG-Training");
            $form->addButton(TextFormat::GOLD."Clutches\n".TextFormat::DARK_GRAY."(".TextFormat::GREEN."Click to create session".TextFormat::DARK_GRAY.")", -1, "", "Clutches");
            $form->addButton(TextFormat::GOLD."Random MLG\n".TextFormat::DARK_GRAY."(".TextFormat::RED."SOON".TextFormat::DARK_GRAY.")", -1, "", "soon");
            $form->sendToPlayer($player);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();

        // AIM-Trainer \\
        $npcEntity = new NPCEntity(new Location(-0.5, 115, -5.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::GRAY.TextFormat::BOLD."AimTrainer", TextFormat::WHITE."Practice your aim");
        $closure = function(Player $player, NPCEntity $entity): void{
            $form = new SimpleForm(function(Player $player, $data): void{
                if($data === null) return;
                if($data === "soon") return;

                $pk = new MatchPacket();
                $pk->addData("group", "Training");
                $pk->addData("minigame", $data);
                $pk->addData("players", json_encode([$player->getName()]));
                CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            });
            $form->setTitle(TextFormat::DARK_GRAY."Aim-Trainer");
            $form->addButton(TextFormat::GRAY.TextFormat::BOLD."Aim Trainer\n".TextFormat::RESET.TextFormat::DARK_GRAY."(".TextFormat::GREEN."Click to create session".TextFormat::DARK_GRAY.")", -1, "", "Aim Trainer");
            $form->sendToPlayer($player);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();

        // Bridger \\
        $npcEntity = new NPCEntity(new Location(8.5, 115, -4.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::YELLOW.TextFormat::BOLD."Bridger", TextFormat::WHITE."Practice your building skills");
        $closure = function(Player $player, NPCEntity $entity): void{
            $form = new SimpleForm(function(Player $player, $data): void{
                if($data === null) return;
                if($data === "soon") return;

                $pk = new MatchPacket();
                $pk->addData("group", "Training");
                $pk->addData("minigame", $data);
                $pk->addData("players", json_encode([$player->getName()]));
                CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            });
            $form->setTitle(TextFormat::DARK_GRAY."Bridger");
            $form->addButton(TextFormat::GRAY.TextFormat::BOLD."Bridger\n".TextFormat::RESET.TextFormat::DARK_GRAY."(".TextFormat::GREEN."Click to create session".TextFormat::DARK_GRAY.")", -1, "", "Bridger");
            $form->sendToPlayer($player);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();

        // KitPvP \\
        $npcEntity = new NPCEntity(new Location(-3.5, 115, -4.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::BLUE.TextFormat::BOLD."KitPvP", TextFormat::WHITE."Practice your PvP skills");
        $closure = function(Player $player, NPCEntity $entity): void{

        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();
    }

    /**
     * @param string $directory
     * @throws ReflectionException
     */
    private function initListener(string $directory): void{
        foreach(scandir($directory) as $listener){
            if($listener === "." || $listener === "..") continue;
            if(is_dir($directory.$listener)){
                $this->initListener($directory.$listener."/");
                continue;
            }
            $dir = str_replace([$this->getFile()."src/", "/"], ["", "\\"], $directory);
            $refClass = new ReflectionClass($dir.str_replace(".php", "", $listener));
            $class = new ($refClass->getName());
            if($class instanceof Listener){
                Server::getInstance()->getPluginManager()->registerEvents($class, $this);
                Server::getInstance()->getLogger()->debug("Registered ".$refClass->getShortName()." listener");
            }
        }
    }

    /**
     * @return Training|null
     */
    public static function getInstance(): ?Training{
        return self::$instance;
    }
}