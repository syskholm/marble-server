<?php
	class Report{
		private $pdo;
		
		function __construct($pdo){
			$this->pdo = $pdo;
		}
		
		/*
		 * Get cash drawer for a given period of time
		 */
		function getCashDrawer($from, $to){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						SUM(orderdetail.Quantity * orderdetail.UnitPrice) AS total , 
						COUNT(*) AS num 
					FROM orderdetail 
					WHERE ModifiedDate BETWEEN :from AND :to
				');
				$stmt->execute(array(
					':from' => $from,
					':to' => $to
				));
				$row = $stmt->fetch();
				if($row['num'] > 0){
					$response['total'] = $row['total'];
				}else{
					$response['total'] = 0;
				}
				$response['paid'] = $this->getTotalPayment($from, $to);
				$response['debt'] = $response['total'] - $response['paid'];
				$response['pdebt'] = $this->getTotalDebtsPaid($from, $to);
				$response['tcash'] = $response['paid'] + $response['pdebt'];
				$response['exp'] = $this->getTotalExpenses($from, $to);
				$response['net'] = $response['tcash'] - $response['exp'];
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get total payment
		 */
		function getTotalPayment($from, $to){
			$response = 0;
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						AmountPaid, 
						SUM(AmountPaid) AS paid, 
						COUNT(*) AS rows 
					FROM payment WHERE note = :nt AND DatePaid BETWEEN :from AND :to
				');
				$stmt->execute(array(
					':nt' => 'good',
					':from' => $from,
					':to' => $to
				));
				$row = $stmt->fetch();
				if($row['rows'] > 0){
					$response = $row['paid'];
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Get total expenses
		 */
		function getTotalExpenses($from, $to){
			$response = 0;
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						ExpenseAmount, 
						SUM(ExpenseAmount) AS total, 
						COUNT(*) AS rows 
					FROM expense WHERE ExpenseDate BETWEEN :from AND :to
				');
				$stmt->execute(array(
					':from' => $from,
					':to' => $to
				));
				$row = $stmt->fetch();
				if($row['rows'] > 0){
					$response = $row['total'];
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get total debts paid
		 */
		function getTotalDebtsPaid($from, $to){
			$response = 0;
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						AmountPaid, 
						SUM(AmountPaid) AS paid, 
						COUNT(*) AS num 
					FROM payment WHERE note = :nt AND DatePaid BETWEEN :f AND :t 
				');
				$stmt->execute(array(
					':nt' => 'debt',
					':f' => $from,
					':t' => $to
				));
				$row = $stmt->fetch();
				if($row['num'] > 0){
					$response = $row['paid'];
				}
			}catch(PDOException $e){
				echo $e->getMessage();
			}
			return $response;
		}
		
		/*
		 * Get sales report for a paticular device
		 */
		function getSalesReport($from, $to, $option){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						SUM(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						salesorder.SalesOrderNumber, 
						salesorder.SalesOrderID 
					FROM orderdetail 
					INNER JOIN salesorder ON(orderdetail.SalesOrderID = salesorder.SalesOrderID) 
					WHERE salesorder.OrderDate BETWEEN :from AND :to 
					AND salesorder.DeviceImei = :imei 
					GROUP BY orderdetail.SalesOrderID 
				');
				$stmt->execute(array(
					':from' => $from,
					':to' => $to,
					':imei' => $option
				));
				$tamount = 0; $tpaid = 0; $tbal = 0;
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['order'] = $row['SalesOrderNumber'];
					$temp['amount'] = $row['total'];
					$temp['paid'] = $this->getOrderPayment($row['SalesOrderID']);
					$temp['bal'] = $temp['amount'] - $temp['paid'];
					$tamount += $temp['amount'];
					$tpaid += $temp['paid'];
					$tbal += $temp['bal'];
					array_push($response['info'], $temp);
				}
				$response['tamount'] = $tamount;
				$response['tpaid'] = $tpaid;
				$response['tbal'] = $tbal;
				$response['from'] = date('d M, Y', strtotime($from));
				$response['to'] = date('d M, Y', strtotime($to));
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get payment for a particular order
		 */
		function getOrderPayment($sid){
			$response = 0;
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						AmountPaid, 
						SUM(AmountPaid) AS paid, 
						COUNT(*) AS rows 
					FROM payment WHERE SalesOrderID = :sid 
				');
				$stmt->execute(array(':sid' => $sid));
				$row = $stmt->fetch();
				if($row['rows'] > 0){
					$response = $row['paid'];
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Get a consolidared sales report for all devices
		 */
		function getConsolidatedSalesReport($from, $to, $option){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						SUM(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						salesorder.SalesOrderNumber, 
						salesorder.SalesOrderID 
					FROM orderdetail 
					INNER JOIN salesorder ON(orderdetail.SalesOrderID = salesorder.SalesOrderID) 
					WHERE salesorder.OrderDate BETWEEN :from AND :to  
					GROUP BY orderdetail.SalesOrderID 
				');
				$stmt->execute(array(
					':from' => $from,
					':to' => $to
				));
				$tamount = 0; $tpaid = 0; $tbal = 0;
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['order'] = $row['SalesOrderNumber'];
					$temp['amount'] = $row['total'];
					$temp['paid'] = $this->getOrderPayment($row['SalesOrderID']);
					$temp['bal'] = $temp['amount'] - $temp['paid'];
					$tamount += $temp['amount'];
					$tpaid += $temp['paid'];
					$tbal += $temp['bal'];
					array_push($response['info'], $temp);
				}
				$response['tamount'] = $tamount;
				$response['tpaid'] = $tpaid;
				$response['tbal'] = $tbal;
				$response['from'] = date('d M, Y', strtotime($from));
				$response['to'] = date('d M, Y', strtotime($to));
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Get sales item report for a particular device
		 */
		function getSalesItemReport($from, $to, $option){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						product.ProductName, 
						salesorder.DeviceImei 
					FROM orderdetail 
					INNER JOIN product ON(orderdetail.ProductID = product.ProductID) 
					LEFT JOIN salesorder ON(orderdetail.SalesOrderID = salesorder.SalesOrderID) 
					WHERE orderdetail.ModifiedDate BETWEEN :from AND :to 
					AND salesorder.DeviceImei = :imei
				');
				$stmt->execute(array(
					':from' => $from,
					':to' => $to,
					':imei' => $option
				));
				$total = 0;
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['name'] = $row['ProductName'];
					$temp['qty'] = $row['Quantity'];
					$temp['price'] = $row['UnitPrice'];
					$temp['amt'] = $row['total'];
					$total += $row['total'];
					array_push($response['info'], $temp);
				}
				$response['total'] = $total;
				$response['from'] = date('d M, Y', strtotime($from));
				$response['to'] = date('d M, Y', strtotime($to));
			}catch(PDOException $e){
				echo $e->getMessage();
			}
			return $response;
		}

		/*
		 * Get sales item report for a particular device
		 */
		function getConsolidatedSalesItemReport($from, $to, $option){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						product.ProductName 
					FROM orderdetail 
					INNER JOIN product ON(orderdetail.ProductID = product.ProductID) 
					WHERE orderdetail.ModifiedDate BETWEEN :from AND :to
				');
				$stmt->execute(array(
					':from' => $from,
					':to' => $to
				));
				$total = 0;
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['name'] = $row['ProductName'];
					$temp['qty'] = $row['Quantity'];
					$temp['price'] = $row['UnitPrice'];
					$temp['amt'] = $row['total'];
					$total += $row['total'];
					array_push($response['info'], $temp);
				}
				$response['total'] = $total;
				$response['from'] = date('d M, Y', strtotime($from));
				$response['to'] = date('d M, Y', strtotime($to));
			}catch(PDOException $e){
				
			}
			return $response;
		}
	}
?>














