<?php
	include_once "database.php"; 
	
	/*This function utilises the week designations in mysql, from 1 to 53. It doesn't work on the monthly basis. It simply returns the number
	of hours a user worked in the week to which the passed date belongs. The argument 1 in the mysql request specifies that the starting day
	of the week is Monday.*/
	function fiftyHourRule($conn, $date, $userid){
		$sql = "SELECT date_start, date_end, SUM(UNIX_TIMESTAMP(date_end) - UNIX_TIMESTAMP(date_start)) as hours FROM tasks 
		WHERE WEEK(date_start, 1) = WEEK('{$date}', 1) AND userid = {$userid}"; 
		
		$result = mysqli_query($conn, $sql); 
		
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		
		$hoursTotal = $rows[0]["hours"]; 
		$workTime = timestampToHours50($hoursTotal); 
		if($hoursTotal > 180000){
			echo "Hours total: {$workTime}<br>This person had more than 50 work hours in the selected week.";
			return false;
		}else{
			echo "Week hours total: {$workTime}"; 
			return true; 
		}
	}
		
	//fiftyHourRule($conn, "2021-11-04 07:00:00", 2); 
	
	function timestampToHours50($timestamp){
		$minutes = $timestamp / 60;
		$hours = $minutes / 60; 
		$hoursRounded = floor($hours); 
		$remainder = $minutes % 60; 
		
		return "{$hoursRounded} hours {$remainder} minutes"; 
	}
	
	