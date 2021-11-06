<?php

namespace ryzerbe\training\gameserver\game\time;

class TimeAPI {
    public static function convert(int $time): TimeFormat{
        $years = floor($time / 31104000);
        $months = floor($time % 31104000 / 2592000);
        $days = floor($time % 2592000 / 86400);
        $hours = floor($time % 86400 / 3600);
        $minutes = floor($time % 3600 / 60);
        $seconds = floor($time % 60);
        return new TimeFormat($years, $months, $days, $hours, $minutes, $seconds);
    }

    public static function getTimeFromArray(array $times): TimeFormat{
        $years = 0;
        $months = 0;
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        foreach($times as $time){
            if($time != ($result = str_replace("Y", "", $time))){
                $years = $result;
            }
            elseif($time != ($result = str_replace("M", "", $time))){
                $months = $result;
            }
            elseif($time != ($result = str_replace("d", "", $time))){
                $days = $result;
            }
            elseif($time != ($result = str_replace("h", "", $time))){
                $hours = $result;
            }
            elseif($time != ($result = str_replace("m", "", $time))){
                $minutes = $result;
            }
            elseif($time != ($result = str_replace("s", "", $time))){
                $seconds = $result;
            }
        }
        return new TimeFormat(intval($years), intval($months), intval($days), intval($hours), intval($minutes), intval($seconds));
    }

    public static function isTimeValid(int $time){
        if($time != 0){
            $c = $time - time();
            if($c <= 0){
                return false;
            }
        }
        return true;
    }
}