<?php
	include_once "database.php"; 
	
	/*Calculates the weekend work times based on the sql designations for days in the week. Like the previous functions, it doesn't calculate
	those weekends when the shift is cut in half at the beginning and the end of the month. Those parts are calculated separately by the 
	functions below.*/
	function weekends($conn, $date, $userid){
		$sql = "SELECT weekday(date_start), weekday(date_end), TIMEDIFF(date_end, date_start), date_start, date_end, userid FROM shifts1 WHERE userid = {$userid} AND
		TIMEDIFF(date_end, date_start) > 0 AND (weekday(date_start) >= 5 OR (weekday(date_start) >= 4 AND weekday(date_end) = 5)) AND date_start BETWEEN MonthStart('{$date}')
		AND MonthEnd('{$date}') AND date_end BETWEEN MonthStart('{$date}') AND MonthEnd('{$date}') ORDER BY date_start"; 
		
		$result = mysqli_query($conn, $sql); 

		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC); 
		 
		$weekendHours = 0; 
		
		foreach($rows as $row){
			if($row['weekday(date_start)'] == "4" && $row['weekday(date_end)'] >= "5"){
				 $endOfDay = strtotime($row["date_end"]); 
				 $midnightDt = date("Y-m-d", strtotime($row["date_end"]));
				 $midnightFull = $midnightDt." 00:00:00";
				 $midnightDateTime = strtotime($midnightFull); 
				 $diff = $endOfDay - $midnightDateTime;
				 $weekendHrs = date("H:i", $diff); 
				 $weekendHours += $diff;
				  
			}else if ($row['weekday(date_start)'] >= "5" && $row['weekday(date_end)'] >= "5"){
				$startOfDay = strtotime($row["date_start"]);
				$endOfDay = strtotime($row["date_end"]);
				$weekendHrs = $endOfDay - $startOfDay; 
				$weekendHours += $weekendHrs; 
			}else if($row['weekday(date_start)'] == "6" && $row['weekday(date_end)'] == "0"){
				$start = strtotime($row["date_start"]);
				$end = strtotime(convertTime($row["date_end"], "00:00:00"));
								
				$sundayRemainder = $end - $start; 
				$weekendHours += $sundayRemainder; 
			}
		}
		
		echo weekends($conn, "2021-10-03 22:00:03", 2); 
		
		$firstDay = firstWeekendOfTheMonth($conn, $date, $userid);
		$lastDay = lastWeekendOfTheMonth($conn, $date, $userid); 
		$weekendHours += $firstDay += $lastDay; 
		
		return timestampToHours($weekendHours); 
	}
		
	function firstWeekendOfTheMonth($conn, $date, $userid){
		$sql = "SELECT weekday(date_start), weekday(date_end), TIMEDIFF(date_end, date_start), date_start, date_end, userid FROM shifts1 WHERE userid = {$userid} AND
		TIMEDIFF(date_end, date_start) > 0 AND (weekday(date_start) >= 5 OR (weekday(date_start) >= 4 AND weekday(date_end) = 5)) AND date_start < 
		MonthStart('{$date}') AND date_end > MonthStart('{$date}')"; 
		
		$result = mysqli_query($conn, $sql); 
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		
		if($rows){
			
			if($rows[0]['weekday(date_start)'] >= "4" && $rows[0]['weekday(date_end)'] >= "5"){
				$dayOneMidnight = firstSecondOfTheMonth($rows[0]["date_start"]); 
				$dayOneEnd = $rows[0]["date_end"];
				$diff = strtotime($dayOneEnd) - strtotime($dayOneMidnight); 
				return $diff;
			}else{
				return 0;
			}
		}else{
			return 0; 
		}
	}
	
	
	function lastWeekendOfTheMonth($conn, $date, $userid){
		$sql = "SELECT weekday(date_start), weekday(date_end), MonthEnd('{$date}'), TIMEDIFF(date_end, date_start), date_start, date_end, userid FROM shifts1 WHERE userid = {$userid} AND
		TIMEDIFF(date_end, date_start) > 0 AND (weekday(date_start) >= 5 OR (weekday(date_start) >= 4 AND weekday(date_end) = 5)) AND date_start < MonthEnd('{$date}')
		AND date_end > MonthEnd('{$date}')"; 
		
		$result = mysqli_query($conn, $sql); 
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		var_dump($rows); 
		if($rows){
			if($rows[0]['weekday(date_start)'] >= "4" && ($rows[0]['weekday(date_end)'] >= "5" || $rows[0]['weekday(date_end)'] < "5")){
				$lastDayMidnight = firstSecondOfTheMonth($rows[0]["date_start"]); 
				$lastDayStart = $rows[0]["date_start"];
				$diff = strtotime($lastDayMidnight) - strtotime($lastDayStart); 
				return $diff;
			}else{
				return 0;
			}
		}else{
			return 0; 
		}		 
	}
		
				
	// function createInterval($time1, $time2){
		// $timeA = date_timestamp_get(date_create($time1));
		// $timeB = date_timestamp_get(date_create($time2)); 
		
		// $interval = $timeB - $timeA; 
		
		// return $interval; 
	// }
	
	function firstSecondOfTheMonth($date){
		$date1 = date_create($date); 
		$date2 = date_add($date1, date_interval_create_from_date_string('1 day'));
		$date2str = date_format($date2, "Y-m-d H:i:s");
		$date2Fragmented = explode(" ", $date2str);
		
		$day3InConstruction = [$date2Fragmented[0], "00:00:00"];
		
		$date3 = implode(" ", $day3InConstruction); 

		return $date3;
	}
	
	function timestampToHours($timestamp){
		$minutes = $timestamp / 60;
		$hours = $minutes / 60; 
		$hoursRounded = floor($hours); 
		$remainder = $minutes % 60; 
		$seconds = $timestamp % 60; 
		return "{$hoursRounded}:{$remainder}:{$seconds}"; 
	}
	
		
	function convertTime($workDay, $eveningShift){
		if($eveningShift === "06:00:00"){
			$workDay = incrementWorkDate($workDay);
			$wd = explode(" ", $workDay);
		
			$workDate = $wd[0];
			$workTime = $wd[1];
			
			$workTime = $eveningShift;
			
			$newTime = array($workDate, $workTime);
			$newWD = implode(" ", $newTime);
		}else{
			$wd = explode(" ", $workDay);
		
			$workDate = $wd[0];
			$workTime = $wd[1];
			
			$workTime = $eveningShift;
			
			$newTime = array($workDate, $workTime);
			$newWD = implode(" ", $newTime);
		}						
		return $newWD; 
	}
	
	function incrementWorkDate($workDay){
		$wd = date_create($workDay); 
		date_add($wd, date_interval_create_from_date_string('1 day'));
		$wdString = date_format($wd, "Y-m-d H:i:s");
		$day = explode(" ", $wdString); 
		$date = $day[0];
		$time = $day[1];
		$dawnOfaNewDay = array($date, $time); 
		$newDay = implode(" ", $dawnOfaNewDay); 
		return $newDay;
	}
	
	 