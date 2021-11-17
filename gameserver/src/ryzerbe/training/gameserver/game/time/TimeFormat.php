<?php

namespace ryzerbe\training\gameserver\game\time;

class TimeFormat {
    private int $years, $months, $days, $hours, $minutes, $seconds;

    public function __construct(int $years, int $months, int $days, int $hours, int $minutes, int $seconds){
        $this->years = $years;
        $this->months = $months;
        $this->days = $days;
        $this->hours = $hours;
        $this->minutes = $minutes;
        $this->seconds = $seconds;
    }

    public function getAddTime(): int{
        if($this->getTime() === 0) return 0;
        return $this->getTime() + time();
    }

    public function getTime(): int{
        return $this->getYears() * 31104000 + $this->getMonths() * 2592000 + $this->getDays() * 86400 + $this->getHours() * 3600 + $this->getMinutes() * 60 + $this->getSeconds();
    }

    public function getYears(): int{
        return $this->years;
    }

    public function getMonths(): int{
        return $this->months;
    }

    public function getDays(): int{
        return $this->days;
    }

    public function getHours(): int{
        return $this->hours;
    }

    public function getMinutes(): int{
        return $this->minutes;
    }

    public function getSeconds(): int{
        return $this->seconds;
    }

    public function asString(bool $ignoreZeroSeconds = true): string{
        if($this->getTime() === 0) return "Never (Permanent)";
        return (
            ($this->getYears() !== 0 ? $this->getYears() . " Year" . ($this->getYears() === 1 ? "" : "s") . ", " : "") .
            ($this->getMonths() !== 0 ? $this->getMonths() . " Month" . ($this->getMonths() === 1 ? "" : "s") . ", " : "") .
            ($this->getDays() !== 0 ? $this->getDays() . " Day" . ($this->getDays() === 1 ? "" : "s") . ", " : "") .
            ($this->getHours() !== 0 ? $this->getHours() . " Hour" . ($this->getHours() === 1 ? "" : "s") . ", " : "") .
            ($this->getMinutes() !== 0 ? $this->getMinutes() . " Minute" . ($this->getMinutes() === 1 ? "" : "s") . ", " : "") .
            ($this->getSeconds() !== 0 || $ignoreZeroSeconds ? $this->getSeconds() . " Second" . ($this->getSeconds() === 1 ? "" : "s") : "")
        );
    }
}