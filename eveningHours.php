<?php
	include_once "database.php"; 
	
	/*Gets all the shifts that are uninterrupted by the break of the month and adds up only those parts selected by the calculateEveningTime function, which
	calculates evening hours (19:00 - 22:00).*/
	
	function getEveningTime($conn, $date, $userid){
				
		$sql = "select * from tasks WHERE userid = {$userid} AND date_start >= MonthStart('{$date}')
		AND date_start < MonthEnd('{$date}') ORDER BY date_start"; 
		
		$eveningTimeStart = "19:00:00";
		$eveningTimeEnd = "22:00:00";
		
		$result = mysqli_query($conn, $sql); 
		$workDays = mysqli_fetch_all($result, MYSQLI_ASSOC);
				
		$eveningTime = 0;
		foreach($workDays as $day){
			$start = $day["date_start"];
			$end = $day["date_end"]; 
			
			$eveningS = convertTime($start, $eveningTimeStart);
			$eveningE = convertTime($start, $eveningTimeEnd);
			$eveningTime += calculateEveningTime($start, $end, $eveningS, $eveningE); 
		}
		 
		$eveningTimeConverted = timestampToHours($eveningTime);
		
		return $eveningTimeConverted;  
	}
		 
	//Same like getEveningTime just for the night hours (22:00 - 06:00). 
	
	function getNightTime($conn, $date, $userid){
				
		$sql = "select userid, date_start, date_end from tasks WHERE userid = {$userid} AND date_start >= MonthStart('{$date}')
		AND date_end <= MonthEnd('{$date}') ORDER BY date_start";

		$eveningTimeStart = "22:00:00";
		$eveningTimeEnd = "06:00:00"; 
		
		$result = mysqli_query($conn, $sql); 
		$workDays = mysqli_fetch_all($result, MYSQLI_ASSOC);
				
		$eveningTime = 0;
		foreach($workDays as $day){
			$start = $day["date_start"];
			$end = $day["date_end"]; 
			
			$eveningS = convertTime($start, $eveningTimeStart);
			$eveningE = convertTime($start, $eveningTimeEnd);
			$eveningTime += calculateEveningTime($start, $end, $eveningS, $eveningE); 
		}
		$eveningTime += firstDay($conn, $date, $userid); 
		$eveningTime += lastDay($conn, $date, $userid); 
		$eveningTimeConverted = timestampToHours($eveningTime);
		
		return $eveningTimeConverted; 
	}
	
	/*Calculates the difference between the start/end of the evening hours and start/end of individual shifts and returns time difference as time interval.
	getEveningTime selects each day of the month and implements this function on it extracting only evening work hours. It adds them up and returns
	the total hours.*/
	function calculateEveningTime($start, $end, $eveningTimeStart, $eveningTimeEnd){
		$interval = 0; 
		if($start <= $eveningTimeStart && $end >= $eveningTimeStart){			
			if($end >= $eveningTimeEnd){ 
					$interval = createInterval($eveningTimeStart, $eveningTimeEnd);
				}else{
					$interval = createInterval($eveningTimeStart, $end); 
				}
			}else if($start >= $eveningTimeStart && $start <= $eveningTimeEnd){
				$interval = createInterval($start, $eveningTimeEnd); 
			}
		return $interval; 
	}
	
	//Calculates the evening hours of the first day in case the shift is cut into two parts by the break of the month.
	function firstDay($conn, $date, $userid){
		$sql = "SELECT userid, date_start, date_end, MonthStart('{$date}') FROM tasks WHERE userid = {$userid} AND TIMEDIFF(date_end, date_start) > 0
		AND date_start <= MonthStart('{$date}') AND date_end > MonthStart('{$date}') ORDER BY date_start"; 
				
		$result = mysqli_query($conn, $sql);
		$dayOneRows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		
		if($dayOneRows){
			$dayOneStart = $dayOneRows[0]["date_start"]; 
			$dayOneEnd = $dayOneRows[0]["date_end"]; 
			$firstSecondOfTheMonth = $dayOneRows[0]["MonthStart('{$date}')"];
			$nightTimeEnd = convertTime($dayOneStart, "06:00:00"); 
						
			$result = nightTimeFirstDay($dayOneStart, $dayOneEnd, $firstSecondOfTheMonth, $nightTimeEnd); 
		}else{
			$result = 0; 
		}
			return $result; 
	}
	 
	/*This function does the same thing like the one called calculateEveningTime, but only for the first day when it's broken in two.
	Since the start of the shift contains the previous month I had to get the substring of the formatted datetime and manually change its
	month part. This is done by the convertTime function below.*/
	
	function nightTimeFirstDay($start, $end, $nightTimeStart, $nightTimeEnd){
		$interval = 0;
			
			if($start <= $nightTimeStart && $end >= $nightTimeEnd){
				$interval = createInterval($nightTimeStart, $nightTimeEnd);
				}else if($start >= $nightTimeStart && $end >= $nightTimeEnd){
					$interval = createInterval($start, $nightTimeEnd);
				}else if($start < $nightTimeStart && $end > $nightTimeStart && $end <= $nightTimeEnd){
					$interval = createInterval($nightTimeStart, $end);
				}else if($start == convertTime($start, "00:00:00")){
					$nightTimeEnd = date_create($start); 
					$nightTimeEnd = date_add($nightTimeEnd, date_interval_create_from_date_string("6 hours"));
					$nightTimeEnd = date_format($nightTimeEnd, "Y-m-d H:i:s");
					if($end <= $nightTimeEnd){
						$interval = createInterval($start, $end);
					}else{
						$interval = createInterval($start, $nightTimeEnd); 
					}
				}

		return $interval; 
	}
		
	function lastDay($conn, $date, $userid){
		$sql = "SELECT userid, date_start, date_end, MonthEnd('{$date}') FROM tasks WHERE userid = {$userid} AND TIMEDIFF(date_end, date_start) > 0 AND date_start < 
		MonthEnd('{$date}') AND date_end >= MonthEnd('{$date}') ORDER BY date_start"; 
				
		$result = mysqli_query($conn, $sql);
		$dayOneRows = mysqli_fetch_all($result, MYSQLI_ASSOC); 
		 
		if($dayOneRows){
			$lastDayStart = $dayOneRows[0]["date_start"]; 
			$lastDayEnd = $dayOneRows[0]["date_end"]; 
			$nightTimeStart = convertTime($lastDayStart, "22:00:00"); 
			$lastSecondOfTheMonth = $dayOneRows[0]["MonthEnd('{$date}')"];
									
			$result = nightTimeLastDay($lastDayStart, $lastDayEnd, $nightTimeStart, $lastSecondOfTheMonth); 
		}else{
			$result = 0; 
		}
			return $result; 
	}
		 
	
	function nightTimeLastDay($start, $end, $nightTimeStart, $lastSecondOfTheMonth){
		
		if($start <= $nightTimeStart && $end < $lastSecondOfTheMonth && $end > $nightTimeStart){
			$interval = createInterval($nightTimeStart, $end); 
		}else if($start <= $nightTimeStart && $end > $lastSecondOfTheMonth){
			if($end == convertTime($end, "00:00:00")){
				$interval = 0; 
			}else{
				$interval = createInterval($nightTimeStart, $lastSecondOfTheMonth) + 1;
			}
		}else if($start >= $nightTimeStart && $start < $lastSecondOfTheMonth && $end > $lastSecondOfTheMonth){
			$interval = createInterval($start, $lastSecondOfTheMonth) + 1; 
		}
		
		return $interval; 
	}
	/*This function manipulates the datetime string in cases when a shift starts on one day and carries over into the next one. It basically
	 increments the date part by one day and manually adds "06:00:00" to the time part of the string in order to get the end part of the
	 night shift and calculate the difference between that point and the start of the shift or the night hours depending on the situation.*/
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
		$wdString = date_format($wd, "Y-m-d H:i:y");
		$day = explode(" ", $wdString); 
		$date = $day[0];
		$time = $day[1];
		$dawnOfaNewDay = array($date, $time); 
		$newDay = implode(" ", $dawnOfaNewDay); 
		return $newDay;
	}
	
	//Gets two datetime strings, turns them into datetime objects and then into time intervals in order to add them up in the functions above. 
	function createInterval($time1, $time2){
		$timeA = date_timestamp_get(date_create($time1));
		$timeB = date_timestamp_get(date_create($time2)); 
		
		$interval = $timeB - $timeA; 
		
		return $interval; 
	}
	
	// function timestampToHours($timestamp){
		// $minutes = $timestamp / 60;
		// $hours = $minutes / 60; 
		// $hoursRounded = floor($hours); 
		// $remainder = $minutes % 60; 
		// $seconds = $timestamp % 60; 
		// return "{$hoursRounded}:{$remainder}:{$seconds}"; 
	// }