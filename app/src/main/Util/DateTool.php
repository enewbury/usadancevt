<?php
/**
 * Created by Eric Newbury.
 * Date: 5/4/16
 */

namespace EricNewbury\DanceVT\Util;


use DateTime;

class DateTool
{

    public function __call($name, $arguments) {
        return call_user_func_array(array(DateTool::class, $name), $arguments);
    }
    
    public static function getListSection(DateTime $dateTime){

        if($dateTime < new DateTime('last sunday -1week')){
            return $dateTime->format('F');
        }
        else if ($dateTime < new DateTime('last sunday')){
            return 'Last Week';
        }
        else if ($dateTime < new DateTime('today')){
            return 'Earlier This Week';
        }
        else if ($dateTime <= new DateTime('tomorrow')){
            return 'Today';
        }
        if($dateTime < new DateTime('this saturday')){
            return 'This Week';
        }
        else if($dateTime < new DateTime('next monday')){
            return 'This Weekend';
        }
        else if($dateTime < new DateTime('next sunday +1 week')){
            return 'Next Week';
        }
        else if ($dateTime <= new DateTime('first day of next month')){
            return 'Later in '. date('F');
        }
        else{
            return $dateTime->format('F');
        }
    }

    public static function isThisWeek(DateTime $dateTime){
        return ($dateTime <= new DateTime('+7 days') && $dateTime >= new DateTime('today'));
    }

    public static function getColloquial(DateTime $dateTime)
    {
        $day = self::getColloquialDate($dateTime);
        $day.= ' at ';
        $day.=self::getMinimalistTime($dateTime);
        return $day;
    }
    public static function getColloquialDate(DateTime $dateTime){
        $checkDate = clone $dateTime;
        $checkDate->setTime(0,0);
        $today = new DateTime('today');
        $tomorrow = new DateTime('tomorrow');
        $nextWeek = new DateTime('+ 7 days');
        $nextWeek->setTime(0, 0);
        if($checkDate == $today){
            $day = 'Today';
        }
        else if($checkDate == $tomorrow){
            $day = 'Tomorrow';
        }
        else if($checkDate < $nextWeek && $checkDate > $today){
            $day = $checkDate->format('l');
        }
        else{
            $day = $checkDate->format('M j');
        }
        return $day;
    }
    
    public static function getMinimalistTime(DateTime $dateTime){
        if ($dateTime->format('i') != '00'){
            return $dateTime->format('g:iA');
        }
        else{
            return $dateTime->format('gA');
        }
    }

    public static function getDateDiffReadable(DateTime $time1, DateTime $time2, $precision = 2 ) {
        $time1 = $time1->getTimestamp();
        $time2 = $time2->getTimestamp();
        // If time1 > time2 then swap the 2 values
        if( $time1 > $time2 ) {
            list( $time1, $time2 ) = array( $time2, $time1 );
        }
        // Set up intervals and diffs arrays
        $intervals = array( 'year', 'month', 'day', 'hour', 'minute', 'second' );
        $diffs = array();
        foreach( $intervals as $interval ) {
            // Create temp time from time1 and interval
            $ttime = strtotime( '+1 ' . $interval, $time1 );
            // Set initial values
            $add = 1;
            $looped = 0;
            // Loop until temp time is smaller than time2
            while ( $time2 >= $ttime ) {
                // Create new temp time from time1 and interval
                $add++;
                $ttime = strtotime( "+" . $add . " " . $interval, $time1 );
                $looped++;
            }
            $time1 = strtotime( "+" . $looped . " " . $interval, $time1 );
            $diffs[ $interval ] = $looped;
        }
        $count = 0;
        $times = array();
        foreach( $diffs as $interval => $value ) {
            // Break if we have needed precission
            if( $count >= $precision ) {
                break;
            }
            // Add value and interval if value is bigger than 0
            if( $value > 0 ) {
                if( $value != 1 ){
                    $interval .= "s";
                }
                // Add value and interval to times array
                $times[] = $value . " " . $interval;
                $count++;
            }
        }
        // Return string with times
        return implode( ", ", $times );
    }

}