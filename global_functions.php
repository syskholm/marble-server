<?php
	class GlobalFunction{
		private $pdo;
		private $today;
		public $logger;
		public $now;
		
		/*
		 * Constructor
		 */
		function __construct($pdo){
			$this->pdo = $pdo;
			$this->today = date('Y-m-d', strtotime('today'));
			$this->now = date('d M, Y H:i:s', strtotime('now'));
			include_once('logging_ops.php');
			$this->logger = new EventLogger();
		}
		
		/*
		 * Device registration
		 */
		function registerDevice($DeviceName, $DeviceImei){
			$response = array();
			try{
				$stmt = $this->pdo->prepare('INSERT INTO device(DeviceImei, DeviceName) VALUES(:imei, :name)');
				if($this->isDeviceExist($DeviceImei) == TRUE){
					$response['success'] = 2;
				}else{
					$stmt->execute(array(
						':imei' => $DeviceImei,
						':name' => $DeviceName
					));
					$response['success'] = 1;
				}
			}catch(PDOException $e){
				$response['success'] = 0;
			}
			return $response;
		}
		
		/*
		 * Check if the device already exists
		 */
		function isDeviceExist($DeviceImei){
			$boolean = FALSE;
			try{
				$stmt = $this->pdo->prepare('SELECT DeviceName FROM device WHERE DeviceImei = :imei');
				$stmt->execute(array(':imei' => $DeviceImei));
				if($stmt->rowCount() > 0){
					$boolean = TRUE;
				}
			}catch(PDOException $e){
				
			}
			return $boolean;
		}
		
		function isDeviceExistA($DeviceImei, $eid){
			$response = array();
			try{
				$stmt = $this->pdo->prepare('SELECT DeviceName, COUNT(*) AS num FROM device WHERE DeviceImei = :imei');
				$stmt->execute(array(':imei' => $DeviceImei));
				$row = $stmt->fetch();
				if($row['num'] > 0){
					if($this->isUserExist($eid) == TRUE){
						$response['success'] = 1;
					}else{
						$response['success'] = 0;
					}
				}else{
					$response['success'] = 2;
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * User login
		 */
		function userLogin($username, $password){
			$response = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						user.EmployeeID, 
						user.AccessLevel, 
						employee.PersonID, 
						person.FirstName, 
						person.LastName 
					FROM user
					INNER JOIN employee ON(user.EmployeeID = employee.EmployeeID) 
					INNER JOIN person ON(employee.PersonID = person.PersonID) 
					WHERE user.Username = :uname
					AND user.Password = :pass 
					AND user.Status = :st
				');
				$stmt->execute(array(
					':uname' => $username,
					':pass' => sha1((md5(trim($password)))),
					':st' => 1
				));
				if($stmt->rowCount() > 0){
					$row = $stmt->fetch();
					$response['eid'] = $row['EmployeeID'];
					$response['ename'] = $row['FirstName'].' '.$row['LastName'];
					$response['level'] = $row['AccessLevel'];
					$response['success'] = 1;
				}else{
					$response['success'] = 0;
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get all registered devices 
		 */
		function getDevices(){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						DeviceImei, 
						DeviceName 
					FROM device ORDER BY DeviceName ASC
				');
				$stmt->execute();
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['imei'] = $row['DeviceImei'];
					$temp['name'] = $row['DeviceName'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Add system users
		 */
		function addUser($eid, $uname, $pass, $level){
			$response = array();
			if($this->isUserExist($eid) == TRUE){
				$response['success'] = 0;
			}else{
				if($level == 'Administrator'){
					$level = 1;
				}else{
					$level = 0;
				}
				try{
					$stmt = $this->pdo->prepare('INSERT INTO user(EmployeeID, Username, Password, DateModified, AccessLevel) VALUES(:eid, :uname, :pass, :date, :level)');
					$stmt->execute(array(
						':eid' => $eid,
						':uname' => $uname,
						':pass' => sha1((md5(trim($pass)))),
						':date' => $this->today,
						':level' => $level
					));
					$response['success'] = 1;
				}catch(PDOException $e){
					
				}
			}
			return $response;
		}
		
		/*
		 * Check if user already exists
		 */
		function isUserExist($eid){
			$bool = FALSE;
			try{
				$stmt = $this->pdo->prepare('SELECT EmployeeID, COUNT(*) AS num FROM user WHERE EmployeeID = :eid AND Status = :st');
				$stmt->execute(array(
					':eid' => $eid,
					':st' => 1
				));
				$row = $stmt->fetch();
				if($row['num'] == 1){
					$bool = TRUE;
				}
			}catch(PDOException $e){
				
			}
			return $bool;
		}
		
		/*
		 * Get a list of users
		 */
		function getUsers(){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						user.UserID, 
						user.Username, 
						user.DateModified, 
						user.AccessLevel, 
						employee.PersonID, 
						person.FirstName, 
						person.LastName 
					FROM user 
					INNER JOIN employee ON(user.EmployeeID = employee.EmployeeID) 
					INNER JOIN person ON(employee.PersonID = person.PersonID) 
					WHERE user.Status = :st
				');
				$stmt->execute(array(':st' => 1));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['uid'] = $row['UserID'];
					$temp['name'] = $row['LastName'].' '.$row['FirstName'];
					$temp['uname'] = $row['Username'];
					$temp['date'] = date('d M, Y', strtotime($row['DateModified']));
					if($row['AccessLevel'] == 1){
						$temp['level'] = 'Administrator';
					}else{
						$temp['level'] = 'Limited User';
					}
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Change User password
		 */
		function changePassword($uid, $newpass, $name){
			$response['info'] = array();
			$file = 'logfile.txt';
			try{
				$stmt = $this->pdo->prepare('UPDATE user SET Password = :pass WHERE UserID = :uid');
				$stmt->execute(array(
					':pass' => sha1((md5(trim($newpass)))),
					':uid' => $uid
				));
				$msg = '[ '.$this->now.' ]'.' Password for '.$name.' changed';
				$this->logger->changePassLog($file, $msg);
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Delete system user
		 */
		function deleteUser($uid, $name){
			$response['info'] = array();
			$file = 'delete_user.txt';
			try{
				$stmt = $this->pdo->prepare('UPDATE user SET Status = :st WHERE UserID = :uid');
				$stmt->execute(array(
					':st' => 0,
					':uid' => $uid
				));
				$msg = '[ '.$this->now.' ]'.' User '.$name.' deleted';
				$this->logger->changePassLog($file, $msg);
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}
	}
?>











