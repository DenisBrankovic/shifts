<?php 

	$dbHost = "localhost";
	$dbUsername = "root";
	$dbPassword = "";
	$dbName = "shifts";
	
	$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);
	
	if(!$conn){
		die("Unable to establish connection.");
	}
	
	/*Gets all the users and dates from the database and returns arrays of dates and users, so that the entries in the shiftChange
	function are checked against those arrays.*/ 
	
	function getUsers($conn){
		$sql = "SELECT userid FROM tasks"; 
		
		$result = mysqli_query($conn, $sql); 
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		 
		$users = array();
		foreach($rows as $row){
			array_push($users, $row["userid"]); 
		}
		return $users;
	}
	
	function getDates($conn){
		$sql = "SELECT * FROM tasks";
			
		$result = mysqli_query($conn, $sql); 
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
		 
		$dates = array();
		foreach($rows as $row){
			array_push($dates, $row["date_start"]); 
			array_push($dates, $row["date_end"]);
		}
		return $dates;
	}
	
	
	function checkUsers($conn, $user){
		$userList = getUsers($conn); 
		
		if(in_array($user, $userList)){
			return true; 
		}else{
			echo "User {$user} not found.<br>";
			return false; 
		}
	}
	
	
	function checkDates($conn, $date){
		$dateList = getDates($conn);
			
		if(in_array($date, $dateList)){
			return true; 
		}else{
			echo "Date {$date} not found.<br>"; 
			return false; 
		}
	}
	
	