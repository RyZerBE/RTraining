<?php

namespace ryzerbe\training\kit;

use baubolp\core\provider\AsyncExecutor;
use baubolp\core\provider\LanguageProvider;
use mysqli;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use ryzerbe\training\Training;
use function base64_decode;
use function base64_encode;
use function serialize;
use function strlen;
use function unserialize;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_DEFLATE;

class KitManager {
    use SingletonTrait;
    /** @var Kit[]  */
    public array $kits = [];
    /** @var array  */
    public array $editKit = [];
    /** @var array  */
    public array $sort = [];

    /**
     * @return Kit[]
     */
    public function getKits(): array{
        return $this->kits;
    }

    /**
     * @param string $kitName
     * @return Kit|null
     */
    public function getKitByName(string $kitName): ?Kit{
        return $this->kits[$kitName] ?? null;
    }

    /**
     * @param Kit $kit
     */
    public function createKit(Kit $kit){
        $armorString = base64_encode(zlib_encode(serialize($kit->getArmor()), ZLIB_ENCODING_DEFLATE));
        $itemsString = base64_encode(zlib_encode(serialize($kit->getItems()), ZLIB_ENCODING_DEFLATE));
        $kitName = $kit->getName();
        $mysql = "INSERT INTO `kitpvp_kits`(`name`, `items`, `armor`) VALUES ('$kitName', '$itemsString', '$armorString')";
        $oKit = $this->getKitByName($kit->getName());
        if($oKit !== null){
            $mysql = "UPDATE `kitpvp_kits` SET items='$itemsString',armor='$armorString' WHERE name='$kitName'";
            $this->unregisterKit($oKit);
        }

        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($armorString, $itemsString, $kitName, $mysql): void{
            $mysqli->query($mysql);
        }, function(Server $server, $result) use ($kit): void{
            KitManager::getInstance()->registerKit($kit);
        });
    }

    /**
     * @param Kit|string $kit
     */
    public function deleteKit(Kit|string $kit){
        if($kit instanceof Kit) $kit = $kit->getName();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($kit): void{
            $mysqli->query("DELETE FROM `kitpvp_kits` WHERE name='$kit'");
        }, function(Server $server, $result) use ($kit): void{
            KitManager::getInstance()->unregisterKit($kit);
        });
    }

    /**
     * @param Kit $kit
     */
    public function registerKit(Kit $kit){
        $this->kits[$kit->getName()] = $kit;
        MainLogger::getLogger()->info($kit->getName()." kit for KitPvP loaded!");
    }

    /**
     * @param Kit|string $kit
     */
    public function unregisterKit(Kit|string $kit){
        if($kit instanceof Kit) $kit = $kit->getName();

        unset($this->kits[$kit]);
    }

    public function loadKits(): void{
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli): array{
            $res = $mysqli->query("SELECT * FROM kitpvp_kits");
            if($res->num_rows <= 0) return [];

            $kits = [];
            while($data = $res->fetch_assoc()) {
                $kits[] = $data;
            }

            return $kits;
        }, function(Server $server, array $result): void{
            foreach($result as $data) {
                $kit = new Kit($data["name"], unserialize(zlib_decode(base64_decode($data["items"]))), unserialize(zlib_decode(base64_decode($data["armor"]))));
                KitManager::getInstance()->registerKit($kit);
            }
        });
    }

    /**
     * @param Player|string $player
     * @param string $kitName
     */
    public function loadPlayerKitToSort(Player|string $player, string $kitName){
        if($player instanceof Player) $player = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($player, $kitName){
            $res = $mysqli->query("SELECT * FROM `kitpvp_kits_sort` WHERE kit_name='$kitName' AND playername='$player'");
            if($res->num_rows <= 0) return $kitName;

            return $res->fetch_assoc()["sort"];
        }, function(Server $server, string $result) use ($player, $kitName): void{
            $player = $server->getPlayerExact($player);
            if($player === null) return;

            if(strlen($result) > 16) {
                $player->getInventory()->setContents(unserialize(zlib_decode(base64_decode($result))));
            }else {
                $kit = KitManager::getInstance()->getKitByName($result);
                if($kit === null) {
                    $kit = KitManager::getInstance()->getKitByName("OnlySword");
                }

                $player->getInventory()->setContents($kit->getItems());
            }
            $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("sneak-to-save", $player->getName()));
            KitManager::getInstance()->sort[$player->getName()] = $kitName;
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 9999, 2, false));
            $player->setImmobile();
        });
    }

    /**
     * @param Player|string $player
     * @param string $kitName
     * @param array $sort
     */
    public function savePlayerKitSort(Player|string $player, string $kitName, array $sort){
        if($player instanceof Player) $player = $player->getName();
        $sortString = base64_encode(zlib_encode(serialize($sort), ZLIB_ENCODING_DEFLATE));
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($player, $kitName, $sortString){
            $res = $mysqli->query("SELECT * FROM `kitpvp_kits_sort` WHERE kit_name='$kitName' AND playername='$player'");
            if($res->num_rows > 0) {
                $mysqli->query("UPDATE `kitpvp_kits_sort` SET sort='$sortString' WHERE kit_name='$kitName' AND playername='$player'");
            }else {
                $mysqli->query("INSERT INTO `kitpvp_kits_sort`(`playername`, `kit_name`, `sort`) VALUES ('$player', '$kitName', '$sortString')");
            }
        }, function(Server $server, $result) use ($player): void{
            $player = $server->getPlayerExact($player);
            if($player === null) return;

            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        });
    }
}