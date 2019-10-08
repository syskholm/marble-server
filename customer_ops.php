<?php
	class Customer{
		private $pdo;
		private $today;
		
		function __construct($pdo){
			$this->pdo = $pdo;
			$this->today = date('Y-m-d', strtotime('today'));
		}
		
		/*
		 * Get customer dropdown
		 */
		function getCustomerMenu(){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						customer.CustomerID, 
						customer.PersonID, 
						person.FirstName, 
						person.LastName 
					FROM customer 
					INNER JOIN person ON(customer.PersonID = person.PersonID) 
                                        WHERE customer.StatusFlag = :stf 
					ORDER BY person.LastName ASC
				');
				$stmt->execute(array(':stf' => 1));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['cid'] = $row['CustomerID'];
					$temp['name'] = $row['LastName'].' '.$row['FirstName'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Add new customer
		 */
		function addCustomer($fname, $lname, $address, $phone, $ptype, $email){
			$response['info'] = array();
			$pid = $this->addPerson($ptype, $fname, $lname);
			try{
				$stmt = $this->pdo->prepare('INSERT INTO customer(PersonID, AccountNumber, ModifiedDate) 
					VALUES(:pid, :accn, :date)');
				$stmt->execute(array(
					':pid' => $pid,
					':accn' => $this->getAccountNumber($ptype),
					':date' => $this->today
				));
				$this->addCustomerAddress($pid, $address, $phone, $email);
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Add person
		 */
		function addPerson($ptype, $fname, $lname){
			$response = 0;
			try{
				$stmt = $this->pdo->prepare('INSERT INTO person(PersonType, FirstName, LastName, DateRegistered) 
					VALUES(:ptype, :fname, :lname, :date)');
				$stmt->execute(array(
					':ptype' => $ptype,
					':fname' => $fname,
					':lname' => $lname,
					':date' => $this->today
				));
				$response = $this->getPersonID();
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Add customer address
		 */
		function addCustomerAddress($pid, $addr, $phone, $email){
			try{
				$stmt = $this->pdo->prepare('INSERT INTO address(PersonID, AddressLine, Contact, EmailAddress, ModifiedDate) 
					VALUES(:pid, :addr, :tel, :email, :date)');
				$stmt->execute(array(
					':pid' => $pid,
					':addr' => $addr,
					':tel' => $phone,
					':email' => $email,
					':date' => $this->today
				));
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Get PersonID last registered
		 */
		function getPersonID(){
			$response = 0;
			try{
				$stmt = $this->pdo->prepare('SELECT MAX(PersonID) AS pid FROM person');
				$stmt->execute();
				$row = $stmt->fetch();
				$response = $row['pid'];
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get customer account number
		 */
		function getAccountNumber($ptype){
			$response = '';
			try{
				$stmt = $this->pdo->prepare('SELECT MAX(CustomerID) AS cid FROM customer');
				$stmt->execute();
				if($stmt->rowCount() === 0){
					$response['cid'] = 1;	
				}else{
					$row = $stmt->fetch();
					$response = $ptype.$this->randomString($row['cid'] + 1);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		function randomString($sid){
			$key = "";
			$array = array_map('intval', str_split($sid));
			$zeroes = 5 - sizeof($array);
			
			for($i = 0; $i < $zeroes; $i++){
				$key .= '0';
			}
			return $key.$sid;
		}
		
		/*
		 * Get a list of all customers
		 */
		function getCustomerList(){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						customer.PersonID, 
						customer.CustomerID, 
						customer.AccountNumber, 
						person.FirstName, 
						person.LastName, 
						address.AddressLine, 
						address.Contact, 
						address.EmailAddress 
					FROM customer 
					INNER JOIN person ON(customer.PersonID = person.PersonID) 
					LEFT JOIN address ON(person.PersonID = address.PersonID) 
                                        WHERE customer.StatusFlag = :stf 
					ORDER BY person.LastName ASC
				');
				$stmt->execute(array(':stf' => 1));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['cname'] = $row['LastName'].' '.$row['FirstName'];
					$temp['accn'] = $row['AccountNumber'];
					$temp['tel'] = $row['Contact'];
					$temp['addr'] = $row['AddressLine'];
					$temp['email'] = $row['EmailAddress'];
					$temp['pid'] = $row['PersonID'];
					$temp['cid'] = $row['CustomerID'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get all customers with debts
		 */
		function getDebtorList(){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.SalesOrderID, 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						SUM(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						salesorder.CustomerID,  
						customer.PersonID, 
						person.FirstName, 
						person.LastName, 
						address.AddressLine, 
						address.Contact 
					FROM orderdetail 
					INNER JOIN salesorder ON(orderdetail.SalesOrderID = salesorder.SalesOrderID) 
					INNER JOIN customer ON(salesorder.CustomerID = customer.CustomerID) 
					INNER JOIN person ON(customer.PersonID = person.PersonID) 
					INNER JOIN address ON(person.PersonID = address.PersonID) 
					GROUP BY salesorder.SalesOrderNumber 
					ORDER BY salesorder.OrderDate DESC
				');
				$stmt->execute();
				while($row = $stmt->fetch()){
					$temp = array();
					if(($row['total'] - $this->getAmountPaid($row['SalesOrderID'])) > 0){
						$temp['sid'] = $row['SalesOrderID'];
						$temp['bal'] = $row['total'] - $this->getAmountPaid($temp['sid']);
						$temp['cid'] = $row['CustomerID'];
						$temp['name'] = $row['LastName'].' '.$row['FirstName'];
						$temp['tel'] = $row['Contact'];
						$temp['addr'] = $row['AddressLine'];
						array_push($response['info'], $temp);
					}
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Get total sum of debts for a particular customer
		 */
		function getDebtTotals(){
			$response['info'] = array();
			$customers = $this->getCustomerMenu();
			$debtors = $this->getDebtorList();
			
			foreach($customers['info'] as $value){
				$total = 0;
				$cid = ''; $name = ''; $tel = ''; $addr = '';
				foreach($debtors['info'] as $debt){
					if($value['cid'] == $debt['cid']){
						$total += $debt['bal'];
						$cid = $debt['cid'];
						$name = $debt['name'];
						$tel = $debt['tel'];
						$addr = $debt['addr'];
					}
				}
				if($total > 0){
					$temp = array();
					$temp['cid'] = $cid;
					$temp['name'] = $name;
					$temp['tel'] = 'Tel: '.$tel;
					$temp['addr'] = $addr;
					$temp['bal'] = 'UGX '.number_format($total, 0);
					array_push($response['info'], $temp);
				}
			}
			return $response;
		}
		
		/*
		 * Get all individual debts for a particular customer
		 */
		function getIndividualDebts($cid){
			$response['info'] = array();
			$response['total'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.SalesOrderID, 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						SUM(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						salesorder.CustomerID,  
						salesorder.SalesOrderNumber, 
						salesorder.EmployeeID, 
						customer.PersonID, 
						customer.AccountNumber, 
						person.FirstName, 
						person.LastName, 
						address.AddressLine, 
						address.Contact 
					FROM orderdetail 
					INNER JOIN salesorder ON(orderdetail.SalesOrderID = salesorder.SalesOrderID) 
					INNER JOIN customer ON(salesorder.CustomerID = customer.CustomerID) 
					INNER JOIN person ON(customer.PersonID = person.PersonID) 
					INNER JOIN address ON(person.PersonID = address.PersonID) 
					WHERE salesorder.CustomerID = :cid 
					GROUP BY salesorder.SalesOrderNumber 
					ORDER BY salesorder.OrderDate DESC
				');
				$stmt->execute(array(':cid' => $cid));
				$total = 0;
				while($row = $stmt->fetch()){
					$temp = array();
					if(($row['total'] - $this->getAmountPaid($row['SalesOrderID'])) > 0){
						$temp['order'] = $row['SalesOrderNumber'];
						$temp['bal'] = number_format(($row['total'] - $this->getAmountPaid($row['SalesOrderID'])), 0);
						$temp['balance'] = $row['total'] - $this->getAmountPaid($row['SalesOrderID']);
						$temp['name'] = $row['LastName'].' '.$row['FirstName'];
						$temp['tel'] = $row['Contact'];
						$temp['addr'] = $row['AddressLine'];
						$temp['accn'] = $row['AccountNumber'];
						$temp['ename'] = $this->getEmployeeName($row['EmployeeID']);
						$total += $temp['balance'];
						array_push($response['info'], $temp);
					}
				}
				array_push($response['total'], array('total' => number_format($total, 0)));
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get total amount paid on a particular order
		 */
		function getAmountPaid($sid){
			$response = 0;
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						AmountPaid, 
						SUM(AmountPaid) AS total, 
						COUNT(*) AS num 
					FROM payment WHERE SalesOrderID = :sid
				');
				$stmt->execute(array(':sid' => $sid));
				$row = $stmt->fetch();
				if($row['num'] > 0){
					$response = $row['total'];
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Debt payment
		 */
		function debtPaymentA($cid, $amount){
			$response = array();
			$debtors = $this->getDebtorList();
			try{
				$stmt = $this->pdo->prepare('INSERT INTO payment(SalesOrderID, AmountPaid, DatePaid, note) VALUES(:sid, :amt, :date, :nt)');
				foreach($debtors['info'] as $value){
					if($value['cid'] == $cid){
						if($value['bal'] > $amount){
							$stmt->execute(array(
								':sid' => $value['sid'],
								':amt' => $amount,
								':date' => $this->today,
								':nt' => 'debt'
							));
							$amount = 0;
						}else{
							$stmt->execute(array(
								':sid' => $value['sid'],
								':amt' => $value['bal'],
								':date' => $this->today,
								':nt' => 'debt'
							));
							$amount -= $value['bal'];
						}
					}
					if($amount <= 0){
						break;
					}
				}
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Edit customer details
		 */
		function editCustomerDetails($fname, $lname, $addr, $phone, $email, $pid){
			$response['info'] = array();
			if($fname == ''){
				$fname = NULL;
			}
			if($lname == ''){
				$lname = NULL;
			}
			if($addr == ''){
				$addr = NULL;
			}
			if($phone == ''){
				$phone = NULL;
			}
			if($email == ''){
				$email = NULL;
			}
			try{
				$stmt = $this->pdo->prepare('UPDATE customer SET ModifiedDate = :date WHERE PersonID = :pid');
				$stmt->execute(array(
					':date' => $this->today,
					':pid' => $pid
				));
				$this->updatePerson($fname, $lname, $pid);
				$this->updateAddress($addr, $phone, $email, $this->today, $pid);
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Update person table
		 */
		function updatePerson($fname, $lname, $pid){
			try{
				$stmt = $this->pdo->prepare('
					UPDATE person 
						SET 
							FirstName = COALESCE(:fname, FirstName), 
							LastName = COALESCE(:lname, LastName) 
						WHERE PersonID = :pid
				');
				$stmt->execute(array(
					':fname' => $fname,
					':lname' => $lname,
					':pid' => $pid
				));
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Update address table
		 */
		function updateAddress($addr, $phone, $email, $date, $pid){
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
		 * Get the employee's name
		 */
		function getEmployeeName($eid){
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						employee.EmployeeID, 
						person.FirstName, 
						person.LastName 
					FROM employee 
					INNER JOIN person ON(employee.PersonID = person.PersonID) 
					WHERE employee.EmployeeID = :eid
				');
				$stmt->execute(array(':eid' => $eid));
				$row = $stmt->fetch();
			}catch(PDOException $e){
				
			}
			return $row['FirstName'];
		}
	}
?>













