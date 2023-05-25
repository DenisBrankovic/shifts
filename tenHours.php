<?php
	include_once "database.php"; 
	
	
	function tenHourRuleByMonth($conn, $date, $userid){
		if(!checkUsers($conn, $userid)) return; 
		
		$sql = "SELECT date_start, date_end FROM tasks WHERE userid = {$userid} AND TIMEDIFF(date_end, date_start) > 0 AND date_end > MonthStart('{$date}')
		AND date_start < MonthEnd('{$date}') ORDER BY date_start"; 
		
		$result = mysqli_query($conn, $sql);

		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		var_dump($rows); 
		for($i = 1; $i <= sizeof($rows) - 1; $i++){
			$res = createInterv($rows[$i-1]["date_end"], $rows[$i]["date_start"]);
		}
	}
	
	function tenHourRuleByDate($conn, $dateStart, $dateEnd, $userid){
		$sql = "SELECT date_start, date_end FROM shifts1 WHERE userid = {$userid} AND TIMEDIFF(date_end, date_start) > 0 ORDER BY date_start"; 
		
		$result = mysqli_query($conn, $sql);
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		
		$currentStart = strtotime($dateStart);
		$currentEnd = strtotime($dateEnd); 
		$previousShiftEnd = "";
		$nextShiftStart = "";
		for($i = 1; $i <= sizeof($rows) - 1; $i++){
			
			if($rows[$i]["date_start"] == $dateStart){
				if($rows[$i - 1]){
					$previousShiftEnd = $rows[$i - 1]["date_end"];
				}
				if($rows[$i + 1]){
					$nextShiftStart = $rows[$i + 1]["date_start"];
				}
			}
			$previous = strtotime($previousShiftEnd);
			$next = strtotime($nextShiftStart);
		}
		$diffForward = $next - $currentEnd; 
		$diffBack = $currentStart - $previous; 
		if($diffBack <= 36000){
			echo "Less than 10 hours of time off on {$dateStart}.";
			return false;
		}
		if($diffForward <= 36000){
			echo "Less than 10 hours of time off on {$dateEnd}.";
			return false;
		}
		return true; 
	} 
		 
			
	// function tenHourRuleByDate($conn, $dateStart, $dateEnd, $userid){
		// if(!checkUsers($conn, $userid)) return;
		
		// if($dateStart > $dateEnd){
			// echo "Please, check the selected dates. Date start must be lower than date end."; 
			// return; 
		// }
		
		// $sql = "SELECT date_end, date_start FROM tasks WHERE date_end BETWEEN '{$dateStart}' - INTERVAL 10 HOUR AND '{$dateStart}' OR 
		// date_start BETWEEN '{$dateEnd}' AND '{$dateEnd}' + INTERVAL 10 HOUR AND TIMEDIFF(date_end, date_start) > 0 AND userid = {$userid} ORDER BY date_start"; 
		
		// $result = mysqli_query($conn, $sql);

		// $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		
		// if($rows){
			// echo "Selected time is not available.<br>User {$userid} has the following schedule:<br>"; 
			// foreach($rows as $row){
				// $scheduledStart = $row["date_start"];
				// $scheduledEnd = $row["date_end"];
				// echo "{$scheduledStart} - {$scheduledEnd}<br>"; 
			// }
			// return false;
		// }else{ 
			// return true; 
		// }
	// }
			
	function createInterv($time1, $time2){
		$timeA = strtotime($time1);
		$timeB = strtotime($time2); 
		
		$interval = $timeB - $timeA; 
		
		if($interval < 36000){
			echo "User didn't have at least ten hours of free time on {$time2}<br>"; 
			return false; 
		}
		return true;		 
	}
		
	 