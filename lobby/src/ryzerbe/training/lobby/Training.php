<?php

namespace ryzerbe\training\lobby;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Location;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\ItemUtils;
use ryzerbe\core\util\loader\ListenerDirectoryLoader;
use ryzerbe\statssystem\provider\StatsProvider;
use ryzerbe\statssystem\StatsSystem;
use ryzerbe\training\lobby\entity\NPCEntity;
use ryzerbe\training\lobby\form\element\Button;
use ryzerbe\training\lobby\form\type\MinigameListForm;
use ryzerbe\training\lobby\inventory\InventorySortManager;
use ryzerbe\training\lobby\item\TrainingItemManager;
use ryzerbe\training\lobby\kit\EnchantCommand;
use ryzerbe\training\lobby\kit\KitCommand;
use ryzerbe\training\lobby\kit\KitManager;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use ryzerbe\training\lobby\queue\Queue;
use ryzerbe\training\lobby\queue\QueueManager;
use ryzerbe\training\lobby\scheduler\TrainingTask;
use ryzerbe\training\lobby\util\SkinUtils;
use function uniqid;

class Training extends PluginBase {
    public const PREFIX = TextFormat::BLUE.TextFormat::BOLD."Training ".TextFormat::RESET;

    public static ?Training $instance = null;

    /**
     * @throws ReflectionException
     */
    public function onEnable(){
        self::$instance = $this;

        TrainingItemManager::getInstance();

        ListenerDirectoryLoader::load($this, $this->getFile(), __DIR__ . "/listener/");
        $this->initAll();

        $this->getScheduler()->scheduleRepeatingTask(new TrainingTask(), 1);

        Entity::registerEntity(NPCEntity::class, true);

        KitManager::getInstance()->loadKits();

        $this->getServer()->getCommandMap()->registerAll("training", [
            new KitCommand(),
            new EnchantCommand()
        ]);
    }

    public function initAll(): void{
        $config = new Config("/root/RyzerCloud/data/NPC/default_geometry.json");
        $skin = new Skin(
            uniqid(),
            SkinUtils::readImage("/root/RyzerCloud/data/NPC/backup_skin.png"),
            "",
            $config->get("name"),
            $config->get("geo")
        );
        $this->initMinigames();
        $this->initMinigameNPCs($skin);
        $this->initMinigameEloQueueNPCs($skin);
        $this->initExtraNPCs($skin);
    }

    private function initMinigames(): void {
        InventorySortManager::getInstance()->registerItems("MLGRush", [
            "stick" => ItemUtils::addEnchantments(Item::get(ItemIds::STICK)->setCustomName("§r§aStick"), [
                Enchantment::KNOCKBACK => 1
            ]),
            "pickaxe" => ItemUtils::addEnchantments(Item::get(ItemIds::GOLDEN_PICKAXE)->setCustomName("§r§aPickaxe"), [
                Enchantment::EFFICIENCY => 1,
                Enchantment::UNBREAKING => 5
            ]),
            "block" =>  Item::get(BlockIds::SANDSTONE)->setCustomName("§r§aBlock")
        ]);

        QueueManager::getInstance()->registerQueues(
            new Queue("MLGRush"),
            new Queue("KitPvP"),
        );

        AsyncExecutor::submitMySQLAsyncTask(StatsSystem::DATABASE, function(mysqli $mysqli): void {
            StatsProvider::createCategory($mysqli, "MLGRush", [
                "elo" => "INT"
            ], [
                "elo" => 1000
            ]);

            StatsProvider::createCategory($mysqli, "KitPvP", [
                "elo" => "INT"
            ], [
                "elo" => 1000
            ]);
        });
    }

    private function initMinigameNPCs(Skin $skin): void {
        $level = Server::getInstance()->getDefaultLevel();

        // MLG-Training \\
        $npcEntity = new NPCEntity(new Location(5.5, 115, -5.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::GOLD.TextFormat::BOLD."MLG-Training", TextFormat::WHITE."Clutches and more");
        $closure = function(Player $player, NPCEntity $entity): void{
            MinigameListForm::open($player, TextFormat::GOLD."MLG-Training", [
                new Button(TextFormat::GOLD."Clutches\n".TextFormat::DARK_GRAY."(".TextFormat::GREEN."Click to create session".TextFormat::DARK_GRAY.")", -1, "", "Clutches"),
                new Button(TextFormat::GOLD."Random MLG\n".TextFormat::DARK_GRAY."(".TextFormat::RED."SOON".TextFormat::DARK_GRAY.")", -1, "", "soon"),
            ]);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();

        // AIM-Trainer \\
        $npcEntity = new NPCEntity(new Location(-0.5, 115, -5.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::GRAY.TextFormat::BOLD."AimTrainer", TextFormat::WHITE."Practice your aim");
        $closure = function(Player $player, NPCEntity $entity): void{
            MinigameListForm::open($player, TextFormat::DARK_GRAY."Aim-Trainer", [
                new Button(TextFormat::GRAY.TextFormat::BOLD."Aim Trainer\n".TextFormat::RESET.TextFormat::DARK_GRAY."(".TextFormat::GREEN."Click to create session".TextFormat::DARK_GRAY.")", -1, "", "Aim Trainer")
            ]);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();

        // Bridger \\
        $npcEntity = new NPCEntity(new Location(8.5, 115, -4.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::YELLOW.TextFormat::BOLD."Bridger", TextFormat::WHITE."Practice your building skills");
        $closure = function(Player $player, NPCEntity $entity): void{
            MinigameListForm::open($player, TextFormat::DARK_GRAY."Bridger", [
                new Button(TextFormat::GRAY.TextFormat::BOLD."Bridger\n".TextFormat::RESET.TextFormat::DARK_GRAY."(".TextFormat::GREEN."Click to create session".TextFormat::DARK_GRAY.")", -1, "", "Bridger")
            ]);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();

        // ??? \\
        $npcEntity = new NPCEntity(new Location(-3.5, 115, -4.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::BLUE.TextFormat::BOLD."???", TextFormat::WHITE."Soon");
        $closure = function(Player $player, NPCEntity $entity): void{};
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();
    }

    private function initMinigameEloQueueNPCs(Skin $skin): void {
        $level = Server::getInstance()->getDefaultLevel();

        // KitPvP \\
        $npcEntity = new NPCEntity(new Location(0.5, 115, 10.5, 180, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::BLUE.TextFormat::BOLD."KitPvP", TextFormat::WHITE."Prove your skills".TextFormat::EOL.TextFormat::GREEN."✔ Elo enabled ✔");
        $closure = function(Player $player, NPCEntity $entity): void{
            $queue = QueueManager::getInstance()->getQueue("KitPvP");
            $queue->handlePlayer($player);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();

        // MLGRush \\
        $npcEntity = new NPCEntity(new Location(4.5, 115, 10.5, 180, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::LIGHT_PURPLE.TextFormat::BOLD."M".TextFormat::WHITE."L".TextFormat::LIGHT_PURPLE."GRush ", TextFormat::WHITE."Prove your skills".TextFormat::EOL.TextFormat::GREEN."✔ Elo enabled ✔");
        $closure = function(Player $player, NPCEntity $entity): void{
            $queue = QueueManager::getInstance()->getQueue("MLGRush");
            $queue->handlePlayer($player);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();
    }

    private function initExtraNPCs(Skin $skin): void {
        $level = Server::getInstance()->getDefaultLevel();

        // CONFIGURATIONS \\
        $npcEntity = new NPCEntity(new Location(2.5, 114, -5.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::AQUA."Configurations", TextFormat::WHITE."Click to configure");
        $closure = function(Player $player, NPCEntity $entity): void{
            $form = new SimpleForm(function(Player $player, $data): void{
                if($data === null) return;

                $trainingPlayer = TrainingPlayerManager::getPlayer($player);
                if($trainingPlayer === null) return;

                switch($data) {
                    case "Lobby": {
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
                    }
                    case "KitPvP": {
                        $form = new SimpleForm(function(Player $player, $data) use ($trainingPlayer): void{
                            if($data === null) return;
                            switch($data){
                                case "kits": {
                                    $kits = [];
                                    $actions = ["Select", "Sort"];
                                    foreach(KitManager::getInstance()->getKits() as $kit){
                                        $kits[] = $kit->getName();
                                    }
                                    $form = new CustomForm(function(Player $player, $data) use ($trainingPlayer, $actions, $kits): void{
                                        if($data === null) return;
                                        $action = $actions[$data["action"]];
                                        $kitName = $kits[$data["kits"]];
                                        $kit = KitManager::getInstance()->getKitByName($kitName);
                                        if($kit === null) return;
                                        $playerName = $player->getName();
                                        switch($action){
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
                            }
                        });

                        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Kits", -1, "", "kits");
                        $form->sendToPlayer($player);
                        break;
                    }
                    case "MLGRush": {
                        $form = new SimpleForm(function(Player $player, mixed $data): void {
                            if($data === null) return;
                            switch($data) {
                                case "sort": {
                                    InventorySortManager::getInstance()->loadSession($player, "MLGRush", null);
                                    break;
                                }
                            }
                        });
                        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Sort Inventory", 0, "", "sort");
                        $form->sendToPlayer($player);
                        break;
                    }
                }
            });
            $form->setContent(LanguageProvider::getMessageContainer("training-configuration-select-game", $player->getName()));
            $form->setTitle(TextFormat::AQUA.TextFormat::BOLD."Settings");
            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::YELLOW.TextFormat::BOLD." Lobby", -1, "", "Lobby");
            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::BLUE.TextFormat::BOLD." KitPvP", -1, "", "KitPvP");
            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::BLUE.TextFormat::BOLD." MLGRush", -1, "", "MLGRush");
            $form->sendToPlayer($player);
        };
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();
    }

    public static function getInstance(): ?Training{
        return self::$instance;
    }
}