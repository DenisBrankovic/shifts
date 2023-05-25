<?php
	include "database.php";
	include "shifts.php";
	include "eveningHours.php";
	include "weekends.php"; 
	
	function getAllNumbers($conn, $date, $userid){
		if(!checkUsers($conn, $userid)) return;
		
		$hoursTotal = workHours($conn, $date, $userid); 
		$eveningHours = getEveningTime($conn, $date, $userid);
		$nightHours = getNightTime($conn, $date, $userid);
		$weekendHours = weekends($conn, $date, $userid);
		
		$m = date_create($date);
		$month = date_format($m, "F"); 
		$workTime = array("Month" => $month, "User" => $userid, "Total" => $hoursTotal, "Evening hours" => $eveningHours, 
		"Night hours" => $nightHours, "Weekend hours" => $weekendHours); 
		
		foreach($workTime as $key=>$value){
			echo "{$key}: {$value}<br>"; 
		}
	}
	
	 