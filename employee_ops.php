<?php
	class Employee{
		private $pdo;
		public $customer;
		private $today;
		private $user;
		
		function __construct($pdo){
			include_once('customer_ops.php');
			include_once('global_functions.php');
			$this->pdo = $pdo;
			$this->customer = new Customer($pdo);
			$this->today = date('Y-m-d', strtotime('today'));
			$this->user = new GlobalFunction($pdo);
		}
		
		/*
		 * Add new employee
		 */
		function addEmployee($fname, $lname, $gender, $address, $phone, $title, $salary, $dob, $hdate, $ptype){
			$response['info'] = array();
			$pid = $this->customer->addPerson($ptype, $fname, $lname);
			try{
				$stmt = $this->pdo->prepare('
					INSERT INTO employee(PersonID, JobTitle, Gender, BirthDate, HireDate, ModifiedDate, SalaryID) 
					VALUES(:pid, :title, :gender, :dob, :hdate, :mdate, :sid)
				');
				$stmt->execute(array(
					':pid' => $pid,
					':title' => $title,
					':gender' => $gender,
					':dob' => $dob,
					':hdate' => $hdate,
					':mdate' => $this->today,
					':sid' => $salary
				));
				$this->addEmployeeAddress($pid, $address, $phone);
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}

                /*
		 * Add employee address
		 */
		function addEmployeeAddress($pid, $addr, $phone){
			try{
				$stmt = $this->pdo->prepare('INSERT INTO address(PersonID, AddressLine, Contact, ModifiedDate) 
					VALUES(:pid, :addr, :tel, :date)');
				$stmt->execute(array(
					':pid' => $pid,
					':addr' => $addr,
					':tel' => $phone,
					':date' => $this->today
				));
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Get salary scales
		 */
		function getSalary(){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('SELECT SalaryID, SalaryAmount FROM salary ORDER BY SalaryAmount ASC');
				$stmt->execute();
				$temp1 = array();
				$temp1['sid'] = 0;
				$temp1['amount'] = 'Salary';
				array_push($response['info'], $temp1);
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['sid'] = $row['SalaryID'];
					$temp['amount'] = 'UGX '.number_format($row['SalaryAmount'], 0);
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get all employees
		 */
		function getEmployees(){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						employee.EmployeeID, 
						employee.JobTitle, 
						person.FirstName, 
						person.LastName, 
						salary.SalaryAmount,  
						address.AddressLine, 
						address.Contact 
					FROM employee 
					INNER JOIN person ON(employee.PersonID = person.PersonID) 
					INNER JOIN salary ON(employee.SalaryID = salary.SalaryID) 
					INNER JOIN address ON(person.PersonID = address.PersonID) 
					WHERE employee.ActiveFlag = :flag 
					ORDER BY person.LastName ASC
				');
				$stmt->execute(array(':flag' => 1));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['eid'] = $row['EmployeeID'];
					$temp['ename'] = $row['LastName'].' '.$row['FirstName'];
					$temp['title'] = 'Job Title: '.$row['JobTitle'];
					$temp['salary'] = 'Salary: UGX '.number_format($row['SalaryAmount'], 0);
					$temp['tel'] = 'Tel: '.$row['Contact'];
					$temp['loc'] = $row['AddressLine'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Get single employee details
		 */
		function getSingleEmployee($eid){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						employee.JobTitle, 
						employee.Gender, 
						employee.BirthDate, 
						employee.HireDate, 
						person.FirstName, 
						person.LastName, 
						person.PersonID, 
						salary.SalaryAmount,  
						address.AddressLine, 
						address.Contact 
					FROM employee 
					INNER JOIN person ON(employee.PersonID = person.PersonID) 
					INNER JOIN salary ON(employee.SalaryID = salary.SalaryID) 
					INNER JOIN address ON(person.PersonID = address.PersonID) 
					WHERE employee.ActiveFlag = :flag 
					AND employee.EmployeeID = :eid
				');
				$stmt->execute(array(
					':flag' => 1,
					':eid' => $eid
				));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['ename'] = $row['LastName'].' '.$row['FirstName'];
					$temp['title'] = $row['JobTitle'];
					if($row['Gender'] == 'M'){
						$temp['gender'] = 'Male';
					}else{
						$temp['gender'] = 'Female';
					}
					$temp['dob'] = date('Y', strtotime('today')) - date('Y', strtotime($row['BirthDate']));
					$temp['hdate'] = date('d M, Y', strtotime($row['HireDate']));
					$temp['salary'] = 'UGX '.number_format($row['SalaryAmount'], 0);
					$temp['tel'] = $row['Contact'];
					$temp['loc'] = $row['AddressLine'];
					$temp['fname'] = $row['FirstName'];
					$temp['lname'] = $row['LastName'];
					$temp['pid'] = $row['PersonID'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Edit employee details
		 */
		function editEmployeeDetail($eid, $pid, $fname, $lname, $title, $salary, $tel, $addr){
			$response['info'] = array();
			if($fname == ''){
				$fname = NULL;
			}
			if($lname == ''){
				$lname = NULL;
			}
			if($title == ''){
				$title = NULL;
			}
			if($tel == ''){
				$tel = NULL;
			}
			if($addr == ''){
				$addr = NULL;
			}
			try{
				$stmt = $this->pdo->prepare('
					UPDATE employee 
						SET 
							JobTitle = COALESCE(:title, JobTitle), 
							ModifiedDate = COALESCE(:mdate, ModifiedDate), 
							SalaryID = COALESCE(:sid, SalaryID) 
						WHERE EmployeeID = :eid	
				');
				$stmt->execute(array(
					':title' => $title,
					':mdate' => $this->today,
					':sid' => $salary,
					':eid' => $eid
				));
				$this->updatePerson($pid, $fname, $lname, $this->today);
				$this->updateAddress($pid, $addr, $tel, '', $this->today);
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Update person table
		 */
		function updatePerson($pid, $fname, $lname, $date){
			try{
				$stmt = $this->pdo->prepare('
					UPDATE person 
						SET 
							FirstName = COALESCE(:fname, FirstName),
							LastName = COALESCE(:lname, LastName),
							DateRegistered = COALESCE(:date, DateRegistered)
						WHERE PersonID = :pid
				');
				$stmt->execute(array(
					':fname' => $fname,
					':lname' => $lname,
					':date' => $date,
					':pid' => $pid
				));
			}catch(PDOException $e){
				
			}
		}

		/*
		 * Update address table
		 */
		function updateAddress($pid, $addr, $phone, $email, $date){
			try{
				$stmt = $this->pdo->prepare('
					UPDATE address 
						SET 
							AddressLine = COALESCE(:addr, AddressLine),
							Contact = COALESCE(:phone, Contact),
							EmailAddress = COALESCE(:email, EmailAddress),
							ModifiedDate = COALESCE(:date, ModifiedDate) 
						WHERE PersonID = :pid
				');
				$stmt->execute(array(
					':addr' => $addr,
					':phone' => $phone,
					':email' => $email,
					':date' => $date,
					':pid' => $pid
				));
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Delete employee
		 */
		function deleteEmployee($eid){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('UPDATE employee SET ActiveFlag = :flag WHERE EmployeeID = :eid');
				$stmt->execute(array(
					':flag' => 0,
					':eid' => $eid
				));
				if($this->user->isUserExist($eid) == TRUE){
					$this->deleteUser($eid);
				}
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Delete system user
		 */
		function deleteUser($eid){
			$file = 'delete_user.txt';
			try{
				$stmt = $this->pdo->prepare('UPDATE user SET Status = :st WHERE EmployeeID = :eid');
				$stmt->execute(array(
					':st' => 0,
					':eid' => $eid
				));
				$msg = '[ '.$this->user->now.' ]'.' User '.$this->getEmployeeName($eid).' deleted';
				$this->user->logger->changePassLog($file, $msg);
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Get employee name
		 */
		function getEmployeeName($eid){
			$response = '';
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						employee.PersonID, 
						person.FirstName, 
						person.LastName 
					FROM employee 
					INNER JOIN person ON(employee.PersonID = person.PersonID) 
					WHERE employee.EmployeeID = :eid
				');
				$stmt->execute(array(':eid' => $eid));
				$row = $stmt->fetch();
				$response = $row['LastName'].' '.$row['FirstName'];
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get employee dropdown
		 */
		function getEmployeeMenu(){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						employee.PersonID, 
						employee.EmployeeID, 
						person.FirstName, 
						person.LastName 
					FROM employee 
					INNER JOIN person ON(employee.PersonID = person.PersonID) 
					WHERE employee.ActiveFlag = :flag
				');
				$stmt->execute(array(':flag' => 1));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['pid'] = $row['EmployeeID'];
					$temp['name'] = $row['LastName'].' '.$row['FirstName'];
					$temp['fname'] = $row['FirstName'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
	}
?>





