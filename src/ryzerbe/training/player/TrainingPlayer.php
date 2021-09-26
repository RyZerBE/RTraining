<?php

namespace ryzerbe\training\player;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use baubolp\core\provider\AsyncExecutor;
use baubolp\core\provider\LanguageProvider;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\training\challenge\Challenge;
use ryzerbe\training\challenge\ChallengeManager;
use ryzerbe\training\form\type\SelectGameForm;
use ryzerbe\training\kit\Kit;
use ryzerbe\training\kit\KitManager;
use ryzerbe\training\player\setting\PlayerSettings;
use ryzerbe\training\team\Team;
use ryzerbe\training\team\TeamManager;
use ryzerbe\training\Training;
use function count;
use function implode;
use function json_encode;
use function time;

class TrainingPlayer {

    /** @var Player  */
    private Player $player;

    /** @var array  */
    private array $teamRequests = [];

    /** @var string|null  */
    private ?string $teamId = null;
    /** @var Kit  */
    private Kit $kit;

    /** @var PlayerSettings  */
    private PlayerSettings $playerSettings;

    /**
     * @param Player $player
     */
    public function __construct(Player $player){
        $this->player = $player;
        $this->playerSettings = new PlayerSettings($player);
        $this->kit = KitManager::getInstance()->getKitByName("Starter");
        $this->load();
    }

    public function load(): void{
        $playerName = $this->getPlayer()->getName();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($playerName): array{
            $res = $mysqli->query("SELECT * FROM settings WHERE playername='$playerName'");
            $loadedData = [];
            if($res->num_rows > 0) {
                while($data = $res->fetch_assoc()) {
                    $loadedData["team_request"] = $data["team_request"];
                    $loadedData["challenge_request"] = $data["challenge_request"];
                }
            }else $mysqli->query("INSERT INTO `settings`(`playername`, `team_request`, `challenge_request`) VALUES ('$playerName', true, true)");


            $res = $mysqli->query("SELECT * FROM `kitpvp_kits_player` WHERE playername='$playerName'");
            if($res->num_rows <= 0) {
                $mysqli->query("INSERT INTO `kitpvp_kits_player`(`playername`, `kit_name`) VALUES ('$playerName', 'Starter')");
                $loadedData["kitName"] = "Starter";
            }else {
                $kitName = $res->fetch_assoc()["kit_name"];
                $loadedData["kitName"] = $kitName;
            }

            return $loadedData;
        }, function(Server $server, array $loadedData) use ($playerName): void{
            $trainingPlayer = TrainingPlayerManager::getPlayer($playerName);
            if($trainingPlayer === null) return;

            $kit = KitManager::getInstance()->getKitByName($loadedData["kitName"] ?? "Starter");
            if($kit === null) $kit = KitManager::getInstance()->getKitByName("Starter");

            $trainingPlayer->setKit($kit);
            $trainingPlayer->getPlayerSettings()->setChallengeRequests($loadedData["challenge_request"] ?? true);
            $trainingPlayer->getPlayerSettings()->setTeamRequests($loadedData["team_request"] ?? true);
        });
    }

    /**
     * @return array
     */
    public function getTeamRequests(): array{
        return $this->teamRequests;
    }

    /**
     * @param Player|string $player
     */
    public function addTeamRequest(Player|string $player): void{
        if($player instanceof Player) $player = $player->getName();
        $this->teamRequests[$player] = time() + 15;
    }

    /**
     * @param Player|string $player
     * @return bool
     */
    public function hasTeamRequest(Player|string $player): bool{
        if($player instanceof Player) $player = $player->getName();

        return isset($this->teamRequests[$player]);
    }

    /**
     * @param Player|string $player
     */
    public function removeTeamRequest(Player|string $player): void{
        if($player instanceof Player) $player = $player->getName();

        unset($this->teamRequests[$player]);
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player{
        return $this->player;
    }

    /**
     * @return Challenge[]
     */
    public function getChallenges(): array{
        return ChallengeManager::getInstance()->getPlayerChallenges($this->getPlayer());
    }

    /**
     * @param TrainingPlayer $challenger
     */
    public function challenge(TrainingPlayer $challenger){
        $manager = ChallengeManager::getInstance();
        if($manager->hasChallenged($challenger->getPlayer(), $this->getPlayer())){
            $challenger->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-already-challenged", $challenger->getPlayer()->getName(), ["#player" => $this->getPlayer()->getName()]));
            return;
        }

        if($manager->hasChallenged($this->getPlayer(), $challenger->getPlayer())){
            $challenge = $manager->getChallenges()[$challenger->getPlayer()->getName()][$this->getPlayer()->getName()] ?? null;
            if($this->getTeam() !== null) {
                if($challenger->getTeam() === null) {
                    $challenger->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-request-invalid",  $challenger->getPlayer()->getName()));
                    return;
                }
                if(count($challenge->getTeam()->getPlayers()) != count($this->getTeam()->getPlayers())) {
                    $challenge->remove();
                    $challenger->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-request-invalid",  $challenger->getPlayer()->getName()));
                    return;
                }
            }
            $challenger->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-challenge-accept", $challenger->getPlayer()->getName(), ["#player" => $this->getPlayer()->getName()]));
            $this->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-challenge-accepted", $challenger->getPlayer()->getName(), ["#player" => $challenger->getPlayer()->getName()]));

            SelectGameForm::open($this->getPlayer(), ["opponent" => $challenger->getPlayer()->getName(), "challenge" => $challenge]);
            return;
        }

        $challenger->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-challenged-player", $challenger->getPlayer()->getName(), ["#player" => $this->getPlayer()->getName()]));
        $this->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-got-challenge", $challenger->getPlayer()->getName(), ["#player" => $challenger->getPlayer()->getName()]));
        $manager->addChallenge($this, new Challenge($challenger, $this->getPlayer()->getName(), TeamManager::getTeam($challenger->getTeamId() ?? "hurensohn")));
    }

    /**
     * @return ?string
     */
    public function getTeamId(): ?string{
        return $this->teamId;
    }

    /**
     * @param string|null $teamId
     */
    public function setTeamId(?string $teamId): void{
        $this->teamId = $teamId;
    }

    /**
     * @return Team|null
     */
    public function getTeam(): ?Team{
        if($this->teamId === null) return null;
        return TeamManager::getTeam($this->teamId) ?? null;
    }

    /**
     * @return bool
     */
    public function isInTeam(): bool{
        return $this->getTeamId() !== null;
    }

    /**
     * @return PlayerSettings
     */
    public function getPlayerSettings(): PlayerSettings{
        return $this->playerSettings;
    }

    /**
     * @return Kit
     */
    public function getKit(): Kit{
        return $this->kit;
    }

    /**
     * @param Kit $kit
     */
    public function setKit(Kit $kit): void{
        $this->kit = $kit;
    }
}