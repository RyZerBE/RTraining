<?php

namespace ryzerbe\training\lobby\team;


class TeamManager {

    /** @var Team[] */
    public static array $teams = [];

    /**
     * @return Team[]
     */
    public static function getTeams(): array{
        return self::$teams;
    }

    /**
     * @param Team $team
     */
    public static function createTeam(Team $team): void{
        self::$teams[$team->getId()] = $team;
    }

    /**
     * @param $team
     */
    public static function unregisterTeam($team): void{
        if($team instanceof Team) $team = $team->getId();
        unset(self::$teams[$team]);
    }

    /**
     * @param string $id
     * @return Team|null
     */
    public static function getTeam(string $id): ?Team{
        return self::$teams[$id] ?? nuLl;
    }
}