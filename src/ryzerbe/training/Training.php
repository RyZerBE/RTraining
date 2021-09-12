<?php

namespace ryzerbe\training;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use jojoe77777\FormAPI\SimpleForm;
use ryzerbe\training\entity\NPCEntity;
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
use ryzerbe\training\item\TrainingItemManager;
use ryzerbe\training\util\SkinUtils;
use function is_dir;
use function json_encode;
use function scandir;
use function str_replace;
use function uniqid;

class Training extends PluginBase {
    public const PREFIX = TextFormat::BLUE.TextFormat::BOLD."Training ".TextFormat::RESET;

    /** @var Training|null  */
    public static ?Training $instance = null;

    public function onEnable(){
        self::$instance = $this;
        TrainingItemManager::getInstance();
        $this->initListener(__DIR__."/listener/");
        $this->spawnEntities();
        Entity::registerEntity(NPCEntity::class, true);
    }

    public function spawnEntities(): void{
        $skin = new Skin(
            uniqid(),
            SkinUtils::readImage("/root/RyzerCloud/data/NPC/backup_skin.png"),
            "",
            (new Config("/root/RyzerCloud/data/NPC/default_geometry.json"))->get("name"),
            (new Config("/root/RyzerCloud/data/NPC/default_geometry.json"))->get("geo")
        );
        // CONFIGURATIONS \\
        $level = Server::getInstance()->getDefaultLevel();
        $npcEntity = new NPCEntity(new Location(2.5, 114, -5.5, 0, 0, $level), $skin);
        $npcEntity->updateTitle(TextFormat::AQUA."Configurations", TextFormat::WHITE."Click to configure");
        $closure = function(Player $player, NPCEntity $entity): void{

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