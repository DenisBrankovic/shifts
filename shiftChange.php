<?php 
	include "database.php";
	
	function shiftSwitch($conn, $userA, $userAShifts, $userB, $userBShifts){
					
			mysqli_begin_transaction($conn); 
			
			try { 
					foreach($userBShifts as $shift){
						$sql = "SELECT * FROM cal_tasks WHERE id = {$shift}";
						$result = mysqli_query($conn, $sql);
						$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
						
						
						if($rows[0]["userid"] != $userB){
							echo "Some of the selected shifts don't belong to userB.";
							return false;
				}
			}
					foreach($userAShifts as $shift){
						$sql = "SELECT * FROM cal_tasks WHERE id = {$shift}";
						$result = mysqli_query($conn, $sql);
						$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
												
						if($rows[0]["userid"] != $userA){
							echo "Some of the selected shifts don't belong to userB.";
							return false;
						}
					}	
						
					foreach($userBShifts as $shift){
						$sql = "UPDATE cal_tasks SET userid = {$userA} WHERE id = {$shift}";
						$result = mysqli_query($conn, $sql);
					}
				
					foreach($userAShifts as $shift){
						$sql = "UPDATE cal_tasks SET userid = {$userB} WHERE id = {$shift}";
						$result = mysqli_query($conn, $sql);
					}
										
					$allShifts = array_merge($userAShifts, $userBShifts); 
					$checkResults = array();
					
					foreach($allShifts as $shiftid){
						$sql = "SELECT id, userid, date_start, date_end from cal_tasks WHERE id = {$shiftid} ORDER BY date_start"; 
						$result = mysqli_query($conn, $sql);
						$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
						
						$user = $rows[0]["userid"]; 
						$dateStart = $rows[0]["date_start"];
						$dateEnd = $rows[0]["date_end"]; 
						
						if(!checkConditions($conn, $user, $dateStart, $dateEnd)){
							array_push($checkResults, "denied"); 
						}else{
							array_push($checkResults, "allowed"); 
						}						
					}
					if(in_array("denied", $checkResults)){
						mysqli_rollback($conn); 
						echo "<br>Shift change couldn't be completed.<br>"; 
					}else{
						mysqli_commit($conn); 
						echo "<br>Shift change has been accepted."; 
					}				
			} catch (mysqli_sql_exception $exception){
				mysqli_rollback($conn); 

				throw $exception;
			}		 
	}
	
	$shiftsA = array(754, 747);
	$shiftsB = array(705, 781);
	
	shiftSwitch($conn, 3, $shiftsA, 1, $shiftsB); 
	
	function checkConditions($conn, $user, $dateStart, $dateEnd){
		
		if(!tenHourRuleByDate($conn, $dateStart, $dateEnd, $user)){
			return false; 
		}
		if(!fiftyHourRule($conn, $dateStart, $user)){
			return false; 
		}
		if(!tenDayRuleByDate($conn, $dateStart, $user)){
			return false; 
		}
		return true; 
	}
	
	function checkUsersAndDates($conn, $userA, $userANewDateStart, $userANewDateEnd, $userB, $userBNewDateStart, $userBNewDateEnd){
		if(!checkUsers($conn, $userA)){
			return false;
		}
		if(!checkUsers($conn, $userB)){
			return false;
		}
		if(!checkDates($conn, $userANewDateStart)){
			return false;
		}
		if(!checkDates($conn, $userANewDateEnd)){
			return false;
		}
		if(!checkDates($conn, $userBNewDateStart)){
			return false;
		}
		if(!checkDates($conn, $userBNewDateEnd)){
			return false;
		}
		return true; 
	}
		
	//Ten hour rule 
	
	function tenHourRuleByDate($conn, $dateStart, $dateEnd, $userid){
		$sql = "SELECT date_start, date_end FROM shifts1 WHERE userid = {$userid} AND TIMEDIFF(date_end, date_start) > 0 ORDER BY date_start"; 
		
		$result = mysqli_query($conn, $sql);
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		
		$currentStart = strtotime($dateStart);
		$currentEnd = strtotime($dateEnd); 
		$previousShiftEnd = "";
		$nextShiftStart = "";
		$diffBack = 0; 
		$diffForward = 0;
		for($i = 1; $i <= sizeof($rows) - 1; $i++){
			
			if($rows[$i]["date_start"] == $dateStart){
				if(isset($rows[$i - 1])){
						$previousShiftEnd = $rows[$i - 1]["date_end"];
						$previous = strtotime($previousShiftEnd);
						$diffBack = $currentStart - $previous;
						if($diffBack <= 36000){
						echo "Less than 10 hours of time off on {$dateStart}.<br>";
						return false;
					}
				}
				if(isset($rows[$i + 1])){
						$nextShiftStart = $rows[$i + 1]["date_start"];
						$next = strtotime($nextShiftStart);
						$diffForward = $next - $currentEnd;
						if($diffForward <= 36000){
						echo "Less than 10 hours of time off on {$dateEnd}.<br>";
						return false;
					}
				}
			}
		}
		return true; 
	}
			
	function createInterval($time1, $time2){
		$timeA = strtotime($time1);
		$timeB = strtotime($time2); 
		
		$interval = $timeB - $timeA; 
		
		if($interval < 36000){
			echo "User didn't have at least ten hours of free time on {$time2}<br>";
			return false; 
		}
		return true; 
	}
	
	
	//Fifty hour rule 
	
	
	function fiftyHourRule($conn, $date, $userid){
		$sql = "SELECT SUM(UNIX_TIMESTAMP(date_end) - UNIX_TIMESTAMP(date_start)) as hours FROM shifts1 
		WHERE WEEK(date_start, 1) = WEEK('{$date}', 1) AND userid = {$userid}"; 
			
		$result = mysqli_query($conn, $sql); 
		
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC); 
				
		if($rows){
			$hoursTotal = $rows[0]["hours"]; 
			$workTime = timestampToHours($hoursTotal); 
			if($hoursTotal > 180000){
				echo "<br>Week hours total for the selected date: <br>User {$userid}: {$workTime}<br>This person had more than 50 work hours in the selected week.";
				return false;
			}else{ 
				return true; 
			}
		}
	}
		
	function timestampToHours($timestamp){
		$minutes = $timestamp / 60;
		$hours = $minutes / 60; 
		$hoursRounded = floor($hours); 
		$remainder = $minutes % 60; 
		
		return "{$hoursRounded} hours {$remainder} minutes"; 
	}
	
	//Ten day rule 
		
	
	function tenDayRuleByDate($conn, $date, $userid){
		
		$dateBefore = tenDaysBefore($date);
		$dateAfter = tenDaysAfter($date); 
				
		$sql = "SELECT userid, date_start, date_end FROM shifts1 WHERE (date_end BETWEEN '{$dateBefore}' AND '{$dateAfter}' AND userid = {$userid})
		OR (date_start BETWEEN '{$dateBefore}' AND '{$dateAfter}' AND userid = {$userid}) ORDER BY date_start"; 
				
		$result = mysqli_query($conn, $sql); 
		
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		
		$counter = 0;
		
		for($i = 0; $i < sizeof($rows); $i++){
			if(confirmWorkDay($rows[$i]["date_start"], $rows[$i]["date_end"])){
				$counter++;
					if($counter >= 10){
					echo "<br>On day {$rows[$i]['date_end']} user {$userid} worked 9 days in a row."; 
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