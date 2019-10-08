<?php
	class Expense{
		private $pdo;
		
		function __construct($pdo){
			$this->pdo = $pdo;
		}
		
		/*
		 * Add expenses
		 */
		function addExpense($date, $amount, $desc, $imei){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					INSERT INTO 
						expense(ExpenseAmount, Description, ExpenseDate, DeviceImei) 
					VALUES(:amount, :desc, :date, :imei)');
				$stmt->execute(array(
					':amount' => $amount,
					':desc' => $desc,
					':date' => $date,
					':imei' => $imei
				));
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
 		}
		
		/*
		 * Get all expenses
		 */
		 function getExpenses($from, $to){
		 	$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						ExpenseAmount, 
						Description, 
						ExpenseDate 
					FROM expense 
					WHERE ExpenseDate BETWEEN :f AND :t ORDER BY ExpenseDate ASC
				');
				$stmt->execute(array(
					':f' => $from,
					':t' => $to
				));
				$total = 0;
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['desc'] = $row['Description'];
					$temp['amt'] = number_format($row['ExpenseAmount'], 0);
					$temp['date'] = date('d M, Y', strtotime($row['ExpenseDate']));
					$total += $row['ExpenseAmount'];
					array_push($response['info'], $temp);
				}
				$response['total'] = 'UGX '.number_format($total, 0);
				$response['from'] = $from;
				$response['to'] = $to;
			}catch(PDOException $e){
				
			}
			return $response;
		 }

		/*
		 * Get all expenses for a particular device
		 */
		 function getDeviceExpenses($from, $to, $imei){
		 	$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						ExpenseAmount, 
						Description, 
						ExpenseDate 
					FROM expense 
					WHERE DeviceImei = :imei AND ExpenseDate BETWEEN :f AND :t 
					ORDER BY ExpenseDate ASC
				');
				$stmt->execute(array(
					':imei' => $imei,
					':f' => $from,
					':t' => $to
				));
				$total = 0;
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['desc'] = $row['Description'];
					$temp['amt'] = $row['ExpenseAmount'];
					$temp['date'] = date('d M, Y', strtotime($row['ExpenseDate']));
					$total += $row['ExpenseAmount'];
					array_push($response['info'], $temp);
				}
				$response['total'] = 'UGX '.number_format($total, 0);
				$response['from'] = $from;
				$response['to'] = $to;
			}catch(PDOException $e){
				
			}
			return $response;
		 }
	}
?>







