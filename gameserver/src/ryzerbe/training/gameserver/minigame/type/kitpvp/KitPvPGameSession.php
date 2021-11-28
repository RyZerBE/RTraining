<?php

namespace ryzerbe\training\gameserver\minigame\type\kitpvp;

use pocketmine\level\Level;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\statssystem\provider\StatsAsyncProvider;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\game\team\Team;
use ryzerbe\training\gameserver\minigame\trait\BlockStorageTrait;
use ryzerbe\training\gameserver\minigame\trait\InventorySortTrait;
use ryzerbe\training\gameserver\minigame\trait\StatesTrait;
use ryzerbe\training\gameserver\minigame\trait\TeamEloTrait;
use ryzerbe\training\gameserver\minigame\type\kitpvp\kits\Kit;
use ryzerbe\training\gameserver\minigame\type\kitpvp\kits\KitManager;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\util\Countdown;
use ryzerbe\training\gameserver\util\ScoreboardUtils;
use function array_rand;
use function count;
use function floor;
use function pow;

class KitPvPGameSession extends GameSession {
    use BlockStorageTrait;
    use StatesTrait;
    use TeamEloTrait;
    use InventorySortTrait;

    private ?Countdown $countdown;
    public int $tick = 0;
    public ?Kit $kit = null;

    public function __construct(Session $session, ?Level $level){
        parent::__construct($session, $level);
        $minigame = $session->getMinigame()->getName();
        foreach(KitManager::getInstance()->getKits() as $kit) {
            //TODO: Do not register the items every time
            $this->registerItems($minigame, $kit->getName(), $kit->getItems());
        }
    }

    public function loadPlayerKits(): void{
        $session = $this->getSession();
        $kit = $this->getKit();
        if($kit === null) {
            $kits = KitManager::getInstance()->getKits();
            $kit = $kits[array_rand($kits)];
            $this->setKit($kit);
        }
        foreach($session->getOnlinePlayers() as $player) {
            $this->loadInventory($player, $this->getSession()->getMinigame()->getName(), $kit->getName(), function() use ($player, $kit): void {
                $player->getArmorInventory()->setContents($kit->getArmor());
            });
        }
    }

    public function getKit(): ?Kit{
        return $this->kit;
    }

    public function setKit(?Kit $kit): void{
        $this->kit = $kit;
    }

    public function getCountdown(): ?Countdown{
        return $this->countdown;
    }

    public function stopCountdown(): void{
        $this->countdown = null;
    }

    public function startCountdown(int $seconds, int $state){
        $this->countdown = new Countdown($seconds, $state);
    }

    public function checkGameEnd(bool $ignoreWinner = false): void {
        $session = $this->getSession();
        $minigame = $session->getMinigame();

        $endRound = false;
        $winner = null;

        $aliveTeams = [];
        foreach($session->getTeams() as $team){
            if($team->isAlive()) $aliveTeams[] = $team;
        }
        if(count($aliveTeams) <= 1){
            $winner = $aliveTeams[0] ?? null;
            $endRound = true;
        }

        if($endRound) {
            $this->setRunning(false);

            foreach($session->getOnlinePlayers() as $sessionPlayer){
                $sessionPlayer->setGamemode(3);
                $sessionPlayer->getArmorInventory()->clearAll();
                $sessionPlayer->getInventory()->clearAll();
            }
            if(!$winner instanceof Team || $ignoreWinner){
                $this->startCountdown(3, Countdown::END);
                return;
            }
            if($this->getSettings()->elo) {
                $looserElo = 0;
                foreach($session->getTeams() as $team) {
                    if($team->getId() !== $winner->getId()){
                        $looserElo += $team->getElo();
                    }
                }
                $looserElo /= (count($session->getTeams()) - 1);
                $elo = floor(((1) / (1 + (pow(10, ($winner->getElo() - $looserElo) / 400)))) * 30);
                if($elo < 5) {
                    $elo = 5.0;
                }elseif($elo > 50) {
                    $elo = 50.0;
                }

                foreach($session->getPlayers() as $player) {
                    $team = $session->getTeamByPlayer($player);
                    if($team === null || $team->getId() !== $winner->getId()){
                        StatsAsyncProvider::deductStatistic($player, $minigame->getName(), "elo", $elo);
                        Server::getInstance()->getPlayerExact($player)?->sendMessage(TextFormat::DARK_GRAY."[".TextFormat::BLUE."ELO".TextFormat::DARK_GRAY."] ".TextFormat::RED."- $elo Elo");
                    } else {
                        StatsAsyncProvider::appendStatistic($player, $minigame->getName(), "elo", $elo);
                        Server::getInstance()->getPlayerExact($player)?->sendMessage(TextFormat::DARK_GRAY."[".TextFormat::BLUE."ELO".TextFormat::DARK_GRAY."] ".TextFormat::GREEN."+ $elo Elo");
                    }
                }
            }

            foreach($session->getOnlinePlayers() as $sessionPlayer){
                $sessionPlayer->sendTitle($winner->getColor().$winner->getName(), TextFormat::GREEN." WON THE FIGHT!");
            }
            $this->startCountdown(8, Countdown::END);
            $this->setState(Countdown::END);
        }
    }

    public function sendScoreboard(): void{
        foreach($this->getSession()->getOnlinePlayers() as $player) {
            ScoreboardUtils::rmScoreboard($player, "training");
            ScoreboardUtils::createScoreboard($player, $this->getSession()->getMinigame()->getSettings()->PREFIX, "training");
            ScoreboardUtils::setScoreboardEntry($player, 0, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, 1, TextFormat::GRAY."○ Kit", "training");
            ScoreboardUtils::setScoreboardEntry($player, 2, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.($this->getKit()?->getName()) ?? "???", "training");
            ScoreboardUtils::setScoreboardEntry($player, 3, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, 4, TextFormat::GRAY."○ Map", "training");
            ScoreboardUtils::setScoreboardEntry($player, 5, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getSession()->getMinigame()->getMap()->getGameMap()->getMapName(), "training");
            ScoreboardUtils::setScoreboardEntry($player, 6, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, 7, TextFormat::WHITE."⇨ ".TextFormat::AQUA."ryzer.be", "training");
        }
    }
}