<?php

namespace ryzerbe\training\lobby;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
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
use ryzerbe\core\util\ItemUtils;
use ryzerbe\core\util\loader\ListenerDirectoryLoader;
use ryzerbe\training\lobby\command\BetaCommand;
use ryzerbe\training\lobby\entity\NPCEntity;
use ryzerbe\training\lobby\form\type\minigame\KitPvPSettingsForm;
use ryzerbe\training\lobby\form\type\minigame\MLGRushSettingsForm;
use ryzerbe\training\lobby\item\TrainingItemManager;
use ryzerbe\training\lobby\kit\EnchantCommand;
use ryzerbe\training\lobby\kit\KitCommand;
use ryzerbe\training\lobby\kit\KitManager;
use ryzerbe\training\lobby\minigame\Minigame;
use ryzerbe\training\lobby\minigame\MinigameManager;
use ryzerbe\training\lobby\minigame\setting\NPCSettings;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use ryzerbe\training\lobby\scheduler\TrainingTask;
use ryzerbe\training\lobby\util\SkinUtils;
use function file_get_contents;
use function json_encode;
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

        $this->getScheduler()->scheduleRepeatingTask(new TrainingTask(), 1);

        Entity::registerEntity(NPCEntity::class, true);

        KitManager::getInstance()->loadKits(function(): void {
            $this->initAll();
        });

        $this->getServer()->getCommandMap()->registerAll("training", [
            new KitCommand(),
            new EnchantCommand(),
            new BetaCommand(),
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
        $this->initMinigames($skin);
        $this->initExtraNPCs($skin);
    }

    private function initMinigames(Skin $skin): void {
        $level = Server::getInstance()->getDefaultLevel();
        $kitPvPItems = [];
        foreach(KitManager::getInstance()->getKits() as $kit) {
            foreach($kit->getItems() as $key => $item) {
                $kitPvPItems[$kit->getName()][$item->getVanillaName()."_".$key] = $item;
            }
        }

        $config = new Config("/root/RyzerCloud/data/NPC/default_geometry.json");
        // MLGRush Queue skin \\
        $mlgSkinQueue = new Skin(
            uniqid(),
            SkinUtils::readImage("/root/RyzerCloud/data/NPC/mlgrush.png"),
            "",
            "geometry.mlgrush",
            file_get_contents("/root/RyzerCloud/data/NPC/geo_mlgrush.json")
        );

        MinigameManager::getInstance()->registerMinigames(
            (new Minigame("MLGRush"))
                ->setElo(true)
                ->setQueue(true)
                ->setMultiplayer(true)
                ->setItems([
                    "stick" => ItemUtils::addEnchantments(Item::get(ItemIds::STICK)->setCustomName("§r§aStick"), [
                        Enchantment::KNOCKBACK => 1
                    ]),
                    "pickaxe" => ItemUtils::addEnchantments(Item::get(ItemIds::GOLDEN_PICKAXE)->setCustomName("§r§aPickaxe"), [
                        Enchantment::EFFICIENCY => 1,
                        Enchantment::UNBREAKING => 5
                    ]),
                    "block" => Item::get(BlockIds::SANDSTONE)->setCustomName("§r§aBlock")
                ])
                ->setNpcSettings(new NPCSettings(
                    new Location(4.5, 115, 10.5, 180, 0, $level),
                    $mlgSkinQueue,
                    TextFormat::LIGHT_PURPLE.TextFormat::BOLD."M".TextFormat::WHITE."L".TextFormat::LIGHT_PURPLE."GRush ".TextFormat::EOL.TextFormat::WHITE."Prove your skills".TextFormat::EOL.TextFormat::GREEN."✔ Elo enabled ✔",
                    null, "MLGRush"
                ))
                ->setSettings(function(Player $player): void {
                    MLGRushSettingsForm::open($player);
                }),

            (new Minigame("KitPvP"))
                ->setElo(true)
                ->setQueue(true)
                ->setMultiplayer(true)
                ->setNpcSettings(new NPCSettings(
                    new Location(0.5, 115, 10.5, 180, 0, $level),
                    $skin,
                    TextFormat::BLUE.TextFormat::BOLD."KitPvP".TextFormat::EOL.TextFormat::WHITE."Prove your skills".TextFormat::EOL.TextFormat::GREEN."✔ Elo enabled ✔",
                    null, "KitPvP"
                ))
                ->setItems($kitPvPItems)
                ->setSettings(function(Player $player): void {
                    KitPvPSettingsForm::open($player);
                }),

            (new Minigame("Clutches"))
                ->setGroup("MLG-Training")
                ->setNpcSettings(new NPCSettings(
                    new Location(5.5, 115, -5.5, 0, 0, $level),
                    $skin,
                    TextFormat::GOLD.TextFormat::BOLD."MLG-Training".TextFormat::EOL.TextFormat::WHITE."Clutches and more",
                    "MLG-Training"
                )),

            (new Minigame("Random MLG"))
                ->setGroup("MLG-Training")
                ->setReleased(false)
                ->setBeta(true)
                ->setTeaser(true),

            (new Minigame("Aim Trainer"))
                ->setGroup("Aim-Trainer")
                ->setNpcSettings(new NPCSettings(
                    new Location(-0.5, 115, -5.5, 0, 0, $level),
                    $skin,
                    TextFormat::GRAY.TextFormat::BOLD."AimTrainer".TextFormat::EOL.TextFormat::WHITE."Practice your aim",
                    "Aim-Trainer"
                )),

            (new Minigame("Bridger"))
                ->setGroup("Bridger")
                ->setNpcSettings(new NPCSettings(
                    new Location(8.5, 115, -4.5, 0, 0, $level),
                    $skin,
                    TextFormat::YELLOW.TextFormat::BOLD."Bridger".TextFormat::EOL.TextFormat::WHITE."Practice your building skills",
                    "Bridger"
                )),
        );
    }

    private function initExtraNPCs(Skin $skin): void {
        $level = Server::getInstance()->getDefaultLevel();

        // Tournament Queue \\
        $tournamentQueueSkin = new Skin(
            uniqid(),
            SkinUtils::readImage("/root/RyzerCloud/data/NPC/cwqueue.png"),
            "",
            "geometry.cwqueue",
            file_get_contents("/root/RyzerCloud/data/NPC/geo_cwqueue.json")
        );
        $closure = function(Player $player, NPCEntity $entity): void {
            if(!$player->isOp()) return;
            $pk = new MatchPacket();
            $pk->addData("group", "Training");
            $pk->addData("minigame", "MLGRush");
            $pk->addData("tournament", "1");
            $pk->addData("players", json_encode([$player->getName()]));
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
        };
        $npcEntity = new NPCEntity(new Location(1.5, 117, -49.5, 0, 0, $level), $tournamentQueueSkin);
        $npcEntity->updateTitle(TextFormat::BLUE.TextFormat::BOLD."Tournament", TextFormat::WHITE."Soon");
        $npcEntity->setInteractClosure($closure);
        $npcEntity->setAttackClosure($closure);
        $npcEntity->spawnToAll();

        // GameZone \\
        $npcEntity = new NPCEntity(new Location(2.5, 117, 23.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::BOLD.TextFormat::GOLD."» ".TextFormat::RESET.TextFormat::GREEN.TextFormat::BOLD."Game Zone".TextFormat::RESET.TextFormat::BOLD.TextFormat::GOLD." «", "");
        $npcEntity->spawnToAll();
        $npcEntity->setScale(0.00000001);

        // ??? \\
        $npcEntity = new NPCEntity(new Location(-3.5, 115, -4.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::BLUE.TextFormat::BOLD."???", TextFormat::WHITE."Soon");
        $npcEntity->spawnToAll();

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
                    default: {
                        $minigame = MinigameManager::getInstance()->getMinigame($data);
                        if($minigame === null) break;
                        ($minigame->getSettings())($player);
                        break;
                    }
                }
            });
            $form->setContent(LanguageProvider::getMessageContainer("training-configuration-select-game", $player->getName()));
            $form->setTitle(TextFormat::AQUA.TextFormat::BOLD."Settings");
            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::YELLOW.TextFormat::BOLD." Lobby", -1, "", "Lobby");
            foreach(MinigameManager::getInstance()->getMinigames() as $minigame) {
                $settings = $minigame->getSettings();
                if($settings === null) continue;
                $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::BLUE.TextFormat::BOLD." ".$minigame->getName(), -1, "", $minigame->getName());
            }
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