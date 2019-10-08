<?php
	class Product{
		private $pdo;
		private $today;
		
		function __construct($pdo){
			$this->pdo = $pdo;
			$this->today = date('Y-m-d', strtotime('today'));
		}
		
		/*
		 * Add new product
		 */
		function addProduct($pname, $price){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('INSERT INTO product(ProductName, UnitPrice, ModifiedDate) VALUES(:name, :price, :date)');
				$stmt->execute(array(
					':name' => $pname,
					':price' => $price,
					':date' => $this->today
				));
				$response['success'] = 1;
			}catch(PDOException $e){
				$response['success'] = 0;				
			}
			return $response;
		}
		
		/*
		 * Get all products
		 */
		function getProducts(){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						ProductID, 
						ProductName, 
						UnitPrice 
					FROM product 
					WHERE ActiveFlag = :flag 
					ORDER BY ProductName ASC
				');
				$stmt->execute(array(':flag' => 1));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['pid'] = $row['ProductID'];
					$temp['pname'] = $row['ProductName'];
					$temp['price'] = number_format($row['UnitPrice'], 0);
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Update product
		 */
		function editProduct($pid, $pname, $price){
			$response['info'] = array();
			if($pname == ""){
				$pname = NULL;
			}
			if($price == ""){
				$price = NULL;
			}
			try{
				$stmt = $this->pdo->prepare('
					UPDATE product 
						SET 
							ProductName = COALESCE(:name, ProductName), 
							UnitPrice = COALESCE(:price, UnitPrice), 
							ModifiedDate = :date 
						WHERE ProductID = :pid
				');
				$stmt->execute(array(
					':name' => $pname,
					':price' => $price,
					':date' => $this->today,
					':pid' => $pid
				));
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Delete product
		 */
		function deleteProduct($pid){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('UPDATE product SET ActiveFlag = :flag WHERE ProductID = :pid');
				$stmt->execute(array(
					':flag' => 0,
					':pid' => $pid
				));
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}
	}
?>







