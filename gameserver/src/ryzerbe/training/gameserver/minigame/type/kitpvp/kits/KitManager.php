<?php

namespace ryzerbe\training\gameserver\minigame\type\kitpvp\kits;

use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\training\gameserver\session\SessionManager;
use function base64_decode;
use function base64_encode;
use function explode;
use function serialize;
use function unserialize;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_DEFLATE;

class KitManager {
    use SingletonTrait;
    /** @var Kit[]  */
    public array $kits = [];
    public array $editKit = [];
    public array $playerKit = [];

    /**
     * @return Kit[]
     */
    public function getKits(): array{
        return $this->kits;
    }

    public function getKitByName(string $kitName): ?Kit{
        return $this->kits[$kitName] ?? null;
    }

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

    public function deleteKit(Kit|string $kit){
        if($kit instanceof Kit) $kit = $kit->getName();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($kit): void{
            $mysqli->query("DELETE FROM `kitpvp_kits` WHERE name='$kit'");
        }, function(Server $server, $result) use ($kit): void{
            KitManager::getInstance()->unregisterKit($kit);
        });
    }

    public function registerKit(Kit $kit){
        $this->kits[$kit->getName()] = $kit;
        MainLogger::getLogger()->info($kit->getName()." kit for KitPvP loaded!");
    }

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

    public function loadPlayerKit(Player|string $player){
        if($player instanceof Player) $player = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($player){
            $res = $mysqli->query("SELECT * FROM `kitpvp_kits_player` WHERE playername='$player'");
            if($res->num_rows <= 0) {
                $mysqli->query("INSERT INTO `kitpvp_kits_player`(`playername`, `kit_name`) VALUES ('$player', 'Starter')");
                return "Starter";
            }

            $kitName = $res->fetch_assoc()["kit_name"];
            $res = $mysqli->query("SELECT * FROM `kitpvp_kits_sort` WHERE kit_name='$kitName' AND playername='$player'");
            if($res->num_rows <= 0) return $kitName;

            return $kitName."#".zlib_decode(base64_decode($res->fetch_assoc()["sort"]));
        }, function(Server $server, string $result) use ($player): void{
            $player = $server->getPlayerExact($player);
            if($player === null) return;

            $resI = explode("#", $result);
            $kitName = $resI[0];
            $kit = KitManager::getInstance()->getKitByName($kitName);
            if($kit === null) {
                $kit = KitManager::getInstance()->getKitByName("OnlySword");
            }

            $player->getArmorInventory()->setContents($kit->getArmor());
            if(isset($resI[1])) {
                $player->getInventory()->setContents(unserialize($resI[1]));
            }else {
                $player->getInventory()->setContents($kit->getItems());
            }

            KitManager::getInstance()->playerKit[$player->getName()] = $kitName;
            $session = SessionManager::getInstance()->getSessionOfPlayer($player);
            $session?->getGameSession()?->sendScoreboard();
        });
    }

    public function loadKitForPlayer(Player|string $player, string $kitName){
        if($player instanceof Player) $player = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($player, $kitName){
            $res = $mysqli->query("SELECT * FROM `kitpvp_kits_sort` WHERE kit_name='$kitName' AND playername='$player'");
            if($res->num_rows <= 0) return $kitName;

            return $kitName."#".zlib_decode(base64_decode($res->fetch_assoc()["sort"]));
        }, function(Server $server, string $result) use ($player): void{
            $player = $server->getPlayerExact($player);
            if($player === null) return;

            $resI = explode("#", $result);
            $kitName = $resI[0];
            $kit = KitManager::getInstance()->getKitByName($kitName);
            if($kit === null) {
                $kit = KitManager::getInstance()->getKitByName("OnlySword");
            }

            $player->getArmorInventory()->setContents($kit->getArmor());
            if(isset($resI[1])) {
                $player->getInventory()->setContents(unserialize($resI[1]));
            }else {
                $player->getInventory()->setContents($kit->getItems());
            }

            KitManager::getInstance()->playerKit[$player->getName()] = $kitName;
            $session = SessionManager::getInstance()->getSessionOfPlayer($player);
            $session?->getGameSession()?->sendScoreboard();
        });
    }
}