<?php
	include_once "database.php"; 
	
	/*Similar to the one for the ten hours rule. This one also has a version that returns the data calculated for the whole month based on the 
	date passed as an argument and another one that checks if the user had ten working days in a row before and after the specified day, disregarding
	the start or the end of the month. The second one is used in the shift change function.*/ 
	function tenDayRuleByMonth($conn, $date, $userid){
		$sql = "SELECT userid, date_start, date_end FROM tasks WHERE (date_start BETWEEN MonthStart('{$date}') AND MonthEnd('{$date}') AND userid = {$userid})
		OR (date_end BETWEEN MonthStart('{$date}') AND MonthEnd('{$date}') AND userid = {$userid}) ORDER BY date_start"; 
		
		$result = mysqli_query($conn, $sql); 
		
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		
		$counter = 0;
		
		for($i = 0; $i < sizeof($rows); $i++){
			if(confirmWorkDay($rows[$i]["date_start"], $rows[$i]["date_end"])){
				$counter++;
					if($counter >= 10){
					echo "On day {$rows[$i]['date_end']} this person has worked 9 days in a row."; 
					return false; 
				}
			}else{ 
				$counter = 0; 
			}
		}
		return true; 
	}
			 
	
	function tenDaysBefore($date){
		$date = date_create($date); 
		date_sub($date, date_interval_create_from_date_string("10 days"));
		return date_format($date,"Y-m-d H:i:s"); 
	}
	
	function tenDaysAfter($date){
		$date = date_create($date); 
		date_add($date, date_interval_create_from_date_string("10 days"));
		return date_format($date,"Y-m-d H:i:s"); 
	}
	
	function tenDayRuleByDate($conn, $date, $userid){
		
		$dateBefore = tenDaysBefore($date);
		$dateAfter = tenDaysAfter($date); 
				
		$sql = "SELECT userid, date_start, date_end FROM tasks WHERE (date_end BETWEEN '{$dateBefore}' AND '{$dateAfter}' AND userid = {$userid})
		OR (date_start BETWEEN '{$dateBefore}' AND '{$dateAfter}' AND userid = {$userid}) ORDER BY date_start"; 
				
		$result = mysqli_query($conn, $sql); 
		
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		
		$counter = 0;
		
		for($i = 0; $i < sizeof($rows); $i++){
			if(confirmWorkDay($rows[$i]["date_start"], $rows[$i]["date_end"])){
				$counter++;
					if($counter >= 10){
					echo "On day {$rows[$i]['date_end']} this person worked 9 days in a row."; 
					return false; 
				}
			}else{ 
				$counter = 0; 
			}
		}
		return true; 
	}
		 
		
	function confirmWorkDay($time1, $time2){
		
		$timeA = strtotime($time1);
		$timeB = strtotime($time2); 
		
		$interval = $timeB - $timeA; 
		
		if($interval > 0){
			return true;
		}else{ 
			return false; 
		}
	}
	
	