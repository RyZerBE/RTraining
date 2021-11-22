<?php

namespace ryzerbe\training\gameserver\minigame\type\mlgrush;

use pocketmine\block\BlockIds;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\util\ItemUtils;
use ryzerbe\statssystem\provider\StatsAsyncProvider;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\game\team\Team;
use ryzerbe\training\gameserver\minigame\trait\BlockStorageTrait;
use ryzerbe\training\gameserver\minigame\trait\InventorySortTrait;
use ryzerbe\training\gameserver\minigame\trait\MinigameStatesTrait;
use ryzerbe\training\gameserver\minigame\trait\TeamEloTrait;
use ryzerbe\training\gameserver\minigame\trait\TeamPointsTrait;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\util\Countdown;
use ryzerbe\training\gameserver\util\ScoreboardUtils;
use function array_key_first;
use function array_keys;
use function arsort;
use function boolval;
use function count;
use function shuffle;

class MLGRushGameSession extends GameSession {
    use BlockStorageTrait;
    use TeamPointsTrait;
    use MinigameStatesTrait;
    use InventorySortTrait;
    use TeamEloTrait;

    public const STATE_VOTING = 2;

    private bool $infiniteBlocks = true;
    private int $maxPoints = PHP_INT_MAX;

    private ?Countdown $countdown;

    public int $tick = 0;

    private int $votes = 0;
    private array $voting = [];

    public function __construct(Session $session, ?Level $level){
        parent::__construct($session, $level);
        $this->registerItems($session->getMinigame()->getName(), null, [
            "stick" => ItemUtils::addEnchantments(Item::get(ItemIds::STICK)->setCustomName("§r§aStick"), [
                Enchantment::KNOCKBACK => 1
            ]),
            "pickaxe" => ItemUtils::addEnchantments(Item::get(ItemIds::GOLDEN_PICKAXE)->setCustomName("§r§aPickaxe"), [
                Enchantment::EFFICIENCY => 1,
                Enchantment::UNBREAKING => 5
            ]),
            "block" => Item::get(BlockIds::SANDSTONE, 0, 64)->setCustomName("§r§a" .  Item::get(BlockIds::SANDSTONE)->getVanillaName())
        ]);
    }

    public function isInfiniteBlocks(): bool{
        return $this->infiniteBlocks;
    }

    public function setInfiniteBlocks(bool $infiniteBlocks): void{
        $this->infiniteBlocks = $infiniteBlocks;
    }

    public function getMaxPoints(): int{
        return $this->maxPoints;
    }

    public function setMaxPoints(int $points): void{
        $this->maxPoints = $points;
    }

    public function getVotes(): int{
        return $this->votes;
    }

    public function addVote(): void {
        $this->votes++;
    }

    public function getVoting(): array{
        return $this->voting;
    }

    public function vote(string $key, int|string $vote): void {
        if(!isset($this->voting[$key][$vote])){
            $this->voting[$key][$vote] = 0;
        }
        $this->voting[$key][$vote]++;
    }

    public function validateVoting(): void {
        $pointsVoting = $this->getVoting()["points"];
        $keys = array_keys($pointsVoting);
        shuffle($keys);
        $random = [];
        foreach ($keys as $key) $random[$key] = $pointsVoting[$key];
        arsort($random);
        $this->setMaxPoints(array_key_first($random));

        $infiniteBlocksVoting = $this->getVoting()["infiniteBlocks"];
        $keys = array_keys($infiniteBlocksVoting);
        shuffle($keys);
        $random = [];
        foreach ($keys as $key) $random[$key] = $infiniteBlocksVoting[$key];
        arsort($random);
        $this->setInfiniteBlocks(boolval(array_key_first($random)));
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

    public function resetPlayer(Player $player): void {
        $session = $this->getSession();
        /** @var MLGRushMinigame $minigame */
        $minigame = $session->getMinigame();
        $map = $minigame->getMap();
        $level = $map->getLevel();

        $player->setHealth($player->getMaxHealth());
        $player->resetMotion();
        $player->resetFallDistance();
        $this->loadInventory($player, $minigame->getName(), null, null);

        $location = $map->getGameMap()->getTeamLocation($session->getTeamByPlayer($player)?->getId(), $level)->asLocation();
        while(($level->getBlock($location)->isSolid() || $level->getBlock($location->add(0, 1))->isSolid()) && $location->y < Level::Y_MAX) $location->y++;
        $player->teleport($location);
    }

    public function resetAll(): void {
        $this->resetBlocks();
        foreach($this->getSession()->getOnlinePlayers() as $player) {
            $this->resetPlayer($player);
        }
    }

    public function checkGameEnd(bool $ignoreWinner = false): void {
        $session = $this->getSession();
        $minigame = $session->getMinigame();

        $endRound = false;
        $winner = null;

        $aliveTeams = [];
        $winnerTeams = [];
        foreach($session->getTeams() as $team){
            if($team->isAlive()) $aliveTeams[] = $team;
            if($this->getPoints($team) >= $this->getMaxPoints()) $winnerTeams[] = $team;
        }
        if(count($aliveTeams) <= 1){
            $winner = $aliveTeams[0] ?? null;
            $endRound = true;
        }
        elseif(count($winnerTeams) >= 1) {
            $winner = $winnerTeams[0] ?? null;
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
                    if($team->getId() !== $winner->getId()){
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
        $session = $this->getSession();
        /** @var MLGRushMinigame $minigame */
        $minigame = $session->getMinigame();
        foreach($session->getOnlinePlayers() as $player) {
            ScoreboardUtils::rmScoreboard($player, "training");
            ScoreboardUtils::createScoreboard($player, $minigame->getSettings()->PREFIX, "training");
            ScoreboardUtils::setScoreboardEntry($player, ($line = 0), "", "training");
            ScoreboardUtils::setScoreboardEntry($player, ++$line, TextFormat::GRAY."○ Points", "training");
            foreach($session->getTeams() as $team) {
                ScoreboardUtils::setScoreboardEntry($player, ++$line, TextFormat::DARK_GRAY."⇨ ".($team->isPlayer($player) ? "§l" : "").$team->getColor().$team->getName()." §r§7» ".TextFormat::GREEN.$this->getPoints($team), "training");
            }
            ScoreboardUtils::setScoreboardEntry($player, ++$line, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, ++$line, TextFormat::GRAY."○ Map", "training");
            ScoreboardUtils::setScoreboardEntry($player, ++$line, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$minigame->getMap()->getGameMap()->getMapName(), "training");
            ScoreboardUtils::setScoreboardEntry($player, ++$line, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, ++$line, TextFormat::WHITE."⇨ ".TextFormat::AQUA."ryzer.be", "training");
        }
    }
}