<?php

namespace ryzerbe\training\lobby\kit;

use Closure;
use mysqli;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\util\async\AsyncExecutor;
use function base64_decode;
use function base64_encode;
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

    public function loadKits(Closure $closure): void{
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli): array{
            $res = $mysqli->query("SELECT * FROM kitpvp_kits");
            if($res->num_rows <= 0) return [];

            $kits = [];
            while($data = $res->fetch_assoc()) {
                $kits[] = $data;
            }

            return $kits;
        }, function(Server $server, array $result) use ($closure): void{
            foreach($result as $data) {
                $kit = new Kit($data["name"], unserialize(zlib_decode(base64_decode($data["items"]))), unserialize(zlib_decode(base64_decode($data["armor"]))));
                KitManager::getInstance()->registerKit($kit);
            }
            $closure();
        });
    }
}