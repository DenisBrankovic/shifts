<?php
/*
* LICENSE
* You are not allowed to share this code and or files. All rights reserved Crezzur
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade our products to newer
* versions in the future. If you wish to customize our products for your
* needs please contact us for more information.
*
*  @author    Crezzur <info@crezzur.com>
*  @copyright 2020-2022 Crezzur - Jaimy Aerts
*  @license   All rights reserved
*  International Registered Trademark & Property of Crezzur
*/

class Formulas
{
    public function workHoursMonth($date, $userid)
    {
        // TIME WORKED IN ONE MONTH
        $month_start = date('Y-m-01 00:00:00', strtotime($date));
        $month_end = date('Y-m-t 23:59:59', strtotime($date));
        $sumtime = Db::getInstance()->getValue("SELECT SUM(UNIX_TIMESTAMP(date_end) - UNIX_TIMESTAMP(date_start)) AS sumtime FROM cal_taken
        WHERE userid = $userid AND status = 1 AND date_start BETWEEN '$month_start' AND '$month_end' ");

        // CURRENTLY NOT NEEDED
        //$dayOneShiftPartial = $this->firstDayOfTheMonth($date, $userid);
        //$lastDayShiftPartial = $this->lastDayOfTheMonth($date, $userid);
        //$workHoursOverall = $sumtime + $dayOneShiftPartial + $lastDayShiftPartial;
        return $this->timestampToHours($sumtime);
    }

    public function workHoursRef($date, $userid)
    {
        // TIME WORKED IN TWO MONTH, EXAMPLE: NOV - DEC
        $cur_ref = ceil(date('n', strtotime($date)) / 2);
        $ref_start = date('Y-m-d 00:00:00', strtotime(date('Y') .'-'. ((2 * $cur_ref)-1)));
        $ref_end = date('Y-m-t 23:59:59', strtotime(date('Y') .'-'. (2 * $cur_ref)));

        $sumtime = Db::getInstance()->getValue("SELECT SUM(UNIX_TIMESTAMP(date_end) - UNIX_TIMESTAMP(date_start)) AS sumtime FROM cal_taken
        WHERE status = 1 AND userid = $userid AND date_start  BETWEEN '$ref_start' AND '$ref_end'");

        // CURRENTLY NOT NEEDED
        //$dayOneShiftPartial = $this->firstDayOfTheMonth($date, $userid);
        //$lastDayShiftPartial = $this->lastDayOfTheMonth($date, $userid);
        //$workHoursOverall = $sumtime + $dayOneShiftPartial + $lastDayShiftPartial;
        return $this->timestampToHours($sumtime);
    }

    public function fiftyHourRule($date, $userid)
    {
        $result = Db::getInstance()->getRow("SELECT date_start, date_end, SUM(UNIX_TIMESTAMP(date_end) - UNIX_TIMESTAMP(date_start)) as hours FROM cal_taken 
        WHERE WEEK(date_start, 5) = WEEK('$date', 5) AND userid = $userid"); // 5 = saturday

        $hoursTotal = $result["hours"];
        $workTime = $this->timestampToHours($hoursTotal);
        if ($hoursTotal > 180000) {
            return "<strong>User $userid</strong> has worked $workTime hours this week, maximum of 50 hours allowed.<br>";
        } else {
            return "<strong>User $userid</strong> has worked $workTime hours this week<br>";
        }
    }

    function weekends($date, $userid)
    {
        $month_start = date('Y-m-01 00:00:00', strtotime($date));
        $month_end = date('Y-m-t 23:59:59', strtotime($date));
        $result = Db::getInstance()->executeS("SELECT weekday(date_start), weekday(date_end), TIMEDIFF(date_end, date_start), date_start, date_end, userid
        FROM cal_taken WHERE userid = {$userid} AND TIMEDIFF(date_end, date_start) > 0 AND (weekday(date_start) >= 5 OR (weekday(date_start) >= 4 AND weekday(date_end) = 5))
        AND date_start BETWEEN '$month_start' AND '$month_end' ORDER BY date_start");
        //WHERE userid = $userid AND status = 1 AND date_start BETWEEN '$month_start' AND '$month_end'
        $weekendHours = 0;
        foreach ($result as $row) {
            if (($row['weekday(date_start)'] >= "4" && $row['weekday(date_end)'] >= "5") || $row['weekday(date_start)'] >= "5") {
                $endOfDay = strtotime($row["date_end"]);
                $midnightDt = date("Y-m-d", strtotime($row["date_end"]));
                $midnightFull = $midnightDt." 00:00:00";
                $midnightDateTime = strtotime($midnightFull);
                $diff = $endOfDay - $midnightDateTime;
                $weekendHrs = date("H:i", $diff);
                $weekendHours += $diff;
            } else {
                $startOfDay = strtotime($row["date_start"]);
                $endOfDay = strtotime($row["date_end"]);
                $weekendHrs = $endOfDay - $startOfDay;
                $weekendHours += $weekendHrs;
            }
        }

        // CURRENTLY NOT NEEDED
        // $firstDay = firstWeekendOfTheMonth($conn, $date, $userid);
        // $lastDay = lastWeekendOfTheMonth($conn, $date, $userid);
        // $weekendHours += $firstDay += $lastDay;

        return $this->timestampToHours($weekendHours);
    }


    private function timestampToHours($timestamp)
    {
        $minutes = $timestamp / 60;
        $hours = $minutes / 60;
        $hoursRounded = floor($hours);
        $remainder = $minutes % 60;
        return "$hoursRounded:$remainder";
    }


    private function firstDayOfTheMonth($date, $userid)
    {
        $dayOneRows = Db::getInstance()->getRow("SELECT date_start, date_end, MonthStart('$date') FROM cal_taken 
        WHERE userid = $userid AND date_end > MonthStart('$date') AND date_start < MonthStart('$date') ORDER BY date_start");

        if ($dayOneRows) {
            $dayOneStart = date_create($dayOneRows["date_start"]);
            $dayOneEnd = date_create($dayOneRows["date_end"]);
            $firstSecondOfTheMonth = date_create($dayOneRows["MonthStart('{$date}')"]);
            $dayOneWorkHours = date_diff($firstSecondOfTheMonth, $dayOneEnd);
            $hours = $dayOneWorkHours->h;
            $minutes = $dayOneWorkHours->i;
            $seconds = $dayOneWorkHours->s;
            $result = $hours * 3600 + $minutes * 60 + $seconds;
        } else {
            $result = 0;
        }
        return $result;
    }

    private function lastDayOfTheMonth($date, $userid)
    {
        $lastDayRows = Db::getInstance()->getRow("SELECT date_start, date_end, MonthEnd('$date') FROM cal_taken WHERE userid = $userid
        AND date_end > MonthEnd('$date') AND date_start < MonthEnd('$date') ORDER BY date_start");

        if ($lastDayRows) {
            $lastDayStart = date_create($lastDayRows[0]["date_start"]);
            $lastDayEnd = date_create($lastDayRows[0]["date_end"]);
            $lastSecondOfTheMonth = date_create($lastDayRows[0]["MonthEnd('{$date}')"]);
            $lastDayWorkHours = date_diff($lastDayStart, $lastSecondOfTheMonth);
            $hours = $lastDayWorkHours->h;
            $minutes = $lastDayWorkHours->i;
            $seconds = $lastDayWorkHours->s;
            $result = $hours * 3600 + $minutes * 60 + $seconds;
            $result += 1;
        } else {
            $result = 0;
        }
        return $result;
    }


}