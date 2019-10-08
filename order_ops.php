<?php
	class Order{
		private $pdo;
		private $today;
		
		function __construct($pdo){
			$this->pdo = $pdo;
			$this->today = date('Y-m-d', strtotime('today'));
		}
		
		/*
		 * Get item dropdown
		 */
		function getItems(){
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
					$temp['nprice'] = $row['UnitPrice'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Add items to the shopping cart
		 */
		function AddToCart($pid, $qty, $price, $desc, $imei){
			$response = array();
			try{
				$stmt = $this->pdo->prepare('INSERT INTO cart(ProductID, Quantity, Price, Description, DeviceImei) 
					VALUES(:pid, :qty, :price, :desc, :imei)');
				$stmt->execute(array(
					':pid' => $pid,
					':qty' => $qty,
					':price' => $price,
					':desc' => $desc,
					':imei' => $imei
				));
				$response['success'] = 1;
			}catch(PDOException $e){
				$response['success'] = 0;
			}
			return $response;
		}
		
		/*
		 * Get cart items
		 */
		function getCartItems($imei){
			$response['info'] = array();
			$response['order'] = array();
			$response['total'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						cart.CartID, 
						cart.ProductID, 
						cart.Quantity, 
						cart.Price, 
						cart.Description, 
						product.ProductName 
					FROM cart 
					INNER JOIN product ON(cart.ProductID = product.ProductID) 
					WHERE cart.DeviceImei = :imei
				');
				$stmt->execute(array(':imei' => $imei));
				$total = 0;
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['cid'] = $row['CartID'];
					$temp['pid'] = $row['ProductID'];
					$temp['pname'] = $row['ProductName'];
					$temp['qty'] = $row['Quantity'];
					$temp['price'] = number_format($row['Price'], 0);
					$temp['px'] = $row['Price'];
					$temp['amt'] = number_format($temp['qty'] * $row['Price'], 0);
					$total += ($temp['qty'] * $row['Price']);
					$temp['descn'] = $row['Description'];
					array_push($response['info'], $temp);
				}
				array_push($response['order'], $this->getOrderNumber());
				array_push($response['total'], array('total' => number_format($total, 0)));
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get order number
		 */
		function getOrderNumber(){
			$response = array();
			try{
				$stmt = $this->pdo->prepare('SELECT MAX(SalesOrderID) AS sid FROM salesorder');
				$stmt->execute();
				if($stmt->rowCount() === 0){
					$response['sid'] = 1;	
				}else{
					$row = $stmt->fetch();
					$response['sid'] = 'SO-'.$this->randomString($row['sid'] + 1);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		function randomString($sid){
			$key = "";
			$array = array_map('intval', str_split($sid));
			$zeroes = 8 - sizeof($array);
			
			for($i = 0; $i < $zeroes; $i++){
				$key .= '0';
			}
			return $key.$sid;
		}

		/*
		 * Delete item from the cart
		 */
		function deleteCartItem($cid){
			try{
				$stmt = $this->pdo->prepare('DELETE FROM cart WHERE CartID = :cid');
				$stmt->execute(array(':cid' => $cid));
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Add sales order
		 */	
		function addSalesOrder($cid, $eid, $imei, $odate, $rdate, $amount){
			$response['info'] = array();
			$num = $this->getOrderNumber();
			$SalesOrderID = explode("-", $num['sid']);
			$sid = $SalesOrderID[1];
			$cartItems = $this->getCartItems($imei);
			try{
				$stmt = $this->pdo->prepare('INSERT INTO salesorder(SalesOrderNumber, CustomerID, EmployeeID, DeviceImei, OrderDate, ModifiedDate, OrderPeriod) 
					VALUES(:num, :cid, :eid, :imei, :odt, :rdt, :op)');
				$stmt->execute(array(
					':num' => $num['sid'],
					':cid' => $cid,
					':eid' => $eid,
					':imei' => $imei,
					':odt' => $odate,
					':rdt' => $rdate,
					':op' => $this->daysBtn($odate, $rdate)
				));
				foreach($cartItems['info'] as $value){
					$this->addLineItems($sid, $value['pid'], $value['qty'], $value['px'], $value['descn'], $odate);
				}
				$this->addPayment($sid, $amount, $odate);
				$this->emptyCart($imei);
				$response['success'] = 1;
				$response['sid'] = $sid;
			}catch(PDOException $e){
				$response['success'] = 0;
			}
			return $response;
		}
		
		/*
		 * Add sales line items
		 */
		function addLineItems($sid, $pid, $qty, $price, $desc, $date){
			try{
				$stmt = $this->pdo->prepare('INSERT INTO orderdetail(SalesOrderID, ProductID, Quantity, UnitPrice, Description, ModifiedDate) 
					VALUES(:sid, :pid, :qty, :price, :desc, :dt)');
				$stmt->execute(array(
					':sid' => $sid,
					':pid' => $pid,
					':qty' => $qty,
					':price' => $price,
					':desc' => $desc,
					':dt' => $date
				));
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Add payment
		 */
		function addPayment($sid, $amount, $date){
			try{
				$stmt = $this->pdo->prepare('INSERT INTO payment(SalesOrderID, AmountPaid, DatePaid) 
					VALUES(:sid, :paid, :dt)');
				$stmt->execute(array(
					':sid' => $sid,
					':paid' => $amount,
					':dt' => $date
				));
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Check if order number already exists
		 */
		function isNumberExist($sid){
			$response = '';
			try{
				//$stmt = $this->pdo->prepare('SELECT ');
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Delete cart items
		 */
		function emptyCart($imei){
			try{
				$stmt = $this->pdo->prepare('DELETE FROM cart WHERE DeviceImei = :imei');
				$stmt->execute(array(':imei' => $imei));
			}catch(PDOException $e){
				
			}
		}
		
		/*
		 * Get all orders for a particulat customer
		 */
		function getCustomerOrders($cid, $imei){
			$response['info'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.SalesOrderID, 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						SUM(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						salesorder.SalesOrderNumber, 
						salesorder.CustomerID, 
						salesorder.OrderDate, 
						salesorder.StatusFlag, 
						customer.PersonID, 
						person.FirstName, 
						person.LastName 
					FROM orderdetail 
					INNER JOIN salesorder ON(orderdetail.SalesOrderID = salesorder.SalesOrderID) 
					INNER JOIN customer ON(salesorder.CustomerID = customer.CustomerID) 
					INNER JOIN person ON(customer.PersonID = person.PersonID) 
					WHERE salesorder.CustomerID = :cid 
					AND salesorder.DeviceImei = :imei 
					GROUP BY salesorder.SalesOrderNumber 
					ORDER BY salesorder.OrderDate DESC
				');
				$stmt->execute(array(
					':cid' => $cid,
					':imei' => $imei
				));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['sid'] = $row['SalesOrderID'];
					$temp['name'] = $row['FirstName'].' '.$row['LastName'];
					$temp['order'] = $row['SalesOrderNumber'];
					$temp['amt'] = "UGX ".number_format($row['total'], 0);
					$temp['date'] = date("d M, Y", strtotime($row['OrderDate']));
					$temp['flag'] = $row['StatusFlag'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Get receipt data
		 */
		function getReceiptData($sid){
			$response['info'] = array();
			$response['items'] = array();
			$response['total'] = array();
			$response['paid'] = array();
			$response['bal'] = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						salesorder.SalesOrderNumber, 
						salesorder.CustomerID, 
						salesorder.OrderDate, 
						salesorder.ModifiedDate, 
						salesorder.StatusFlag, 
						customer.PersonID, 
						customer.AccountNumber, 
						person.FirstName, 
						person.LastName, 
						address.Contact, 
						address.EmailAddress  
					FROM salesorder 
					INNER JOIN customer ON(salesorder.CustomerID = customer.CustomerID) 
					INNER JOIN person ON(customer.PersonID = person.PersonID) 
					INNER JOIN address ON(person.PersonID = address.PersonID) 
					WHERE salesorder.SalesOrderID = :sid
				');
				$stmt->execute(array(':sid' => $sid));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['order'] = $row['SalesOrderNumber'];
					$temp['cname'] = $row['FirstName'].' '.$row['LastName'];
					$temp['accn'] = 'Cust: '.$row['AccountNumber'];
					$temp['odt'] = 'Date: '.date('d M, Y', strtotime($row['OrderDate']));
					$temp['rdt'] = 'Ready On: '.date('d M, Y', strtotime($row['ModifiedDate']));
					$temp['tel'] = 'Tel: '.$row['Contact'];
					$temp['cashier'] = 'Emp: '.$this->getCashier($sid);
					$temp['flag'] = $row['StatusFlag'];
					$temp['email'] = $row['EmailAddress'];
					array_push($response['info'], $temp);
				}
				$items = $this->getLineItems($sid);
				$total = 0;
				foreach($items as $value){
					$temp = array();
					$temp['pdt'] = $value['pdt'];
					$temp['qty'] = $value['qty'];
					$temp['price'] = number_format($value['price'], 0);
					$temp['amt'] = number_format($value['amt'], 0);
					$total += $value['amt'];
					$temp['desc'] = $value['desc'];
					array_push($response['items'], $temp);
				}
				array_push($response['total'], array('total' => number_format($total, 0)));
				array_push($response['paid'], array('paid' => number_format($this->getAmountPaid($sid), 0)));
				array_push($response['bal'], array('bal' => number_format($total - $this->getAmountPaid($sid), 0)));
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Get employee who worked on a particular order
		 */
		function getCashier($sid){
			$response = '';
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						salesorder.EmployeeID, 
						employee.PersonID, 
						person.FirstName 
					FROM salesorder  
					INNER JOIN employee ON(salesorder.EmployeeID = employee.EmployeeID) 
					INNER JOIN person ON(employee.PersonID = person.PersonID) 
					WHERE salesorder.SalesOrderID = :sid
				');
				$stmt->execute(array(':sid' => $sid));
				$row = $stmt->fetch();
				$response = $row['FirstName'];
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
		 * Get line itemms 
		 */
		function getLineItems($sid){
			$response = array();
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.ProductID, 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						(orderdetail.Quantity * orderdetail.UnitPrice) AS amount, 
						orderdetail.Description, 
						product.ProductName 
					FROM orderdetail 
					INNER JOIN product ON(orderdetail.ProductID = product.ProductID) 
					WHERE orderdetail.SalesOrderID = :sid						
				');
				$stmt->execute(array(':sid' => $sid));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['pdt'] = $row['ProductName'];
					$temp['qty'] = $row['Quantity'];
					$temp['price'] = $row['UnitPrice'];
					$temp['amt'] = $row['amount'];
					$temp['desc'] = $row['Description'];
					array_push($response, $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Debt payment
		 */
		function debtPayment($sid, $amount){
			$response = array();
			try{
				$stmt = $this->pdo->prepare('
					INSERT INTO payment(SalesOrderID, AmountPaid, DatePaid, note) VALUES(:sid, :amt, :date, :nt)
				');
				$stmt->execute(array(
					':sid' => $sid,
					':amt' => $amount,
					':date' => $this->today,
					':nt' => 'debt'
				));
				$response['success'] = 1;
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Get pending orders
		 */
		function getPendingOrders($flag, $page){
			$response['info'] = array();
			$limit = 20;
			$offset = ($page - 1) * $limit;
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.SalesOrderID, 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						SUM(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						salesorder.SalesOrderNumber, 
						salesorder.CustomerID, 
						salesorder.OrderDate, 
						salesorder.StatusFlag, 
						customer.PersonID, 
						person.FirstName, 
						person.LastName 
					FROM orderdetail 
					INNER JOIN salesorder ON(orderdetail.SalesOrderID = salesorder.SalesOrderID) 
					INNER JOIN customer ON(salesorder.CustomerID = customer.CustomerID) 
					INNER JOIN person ON(customer.PersonID = person.PersonID) 
					WHERE salesorder.StatusFlag = :flag 
					GROUP BY salesorder.SalesOrderNumber 
					ORDER BY salesorder.OrderDate DESC 
					LIMIT :offset, :limit
				');
				$stmt->execute(array(
					':flag' => $flag,
					':offset' => $offset,
					':limit' => $limit
				));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['sid'] = $row['SalesOrderID'];
					$temp['name'] = $row['FirstName'].' '.$row['LastName'];
					$temp['order'] = $row['SalesOrderNumber'];
					$temp['amt'] = "UGX ".number_format($row['total'], 0);
					$temp['date'] = date("d M, Y", strtotime($row['OrderDate']));
					$temp['flag'] = $row['StatusFlag'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Get ready orders
		 */
		function getReadyOrders($flag, $page){
			$response['info'] = array();
			$limit = 20;
			$offset = ($page - 1) * $limit;
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.SalesOrderID, 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						SUM(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						salesorder.SalesOrderNumber, 
						salesorder.CustomerID, 
						salesorder.OrderDate, 
						salesorder.StatusFlag, 
						customer.PersonID, 
						person.FirstName, 
						person.LastName 
					FROM orderdetail 
					INNER JOIN salesorder ON(orderdetail.SalesOrderID = salesorder.SalesOrderID) 
					INNER JOIN customer ON(salesorder.CustomerID = customer.CustomerID) 
					INNER JOIN person ON(customer.PersonID = person.PersonID) 
					WHERE salesorder.StatusFlag = :flag 
					GROUP BY salesorder.SalesOrderNumber 
					ORDER BY salesorder.OrderDate DESC 
					LIMIT :offset, :limit
				');
				$stmt->execute(array(
					':flag' => $flag,
					':offset' => $offset,
					':limit' => $limit
				));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['sid'] = $row['SalesOrderID'];
					$temp['name'] = $row['FirstName'].' '.$row['LastName'];
					$temp['order'] = $row['SalesOrderNumber'];
					$temp['amt'] = "UGX ".number_format($row['total'], 0);
					$temp['date'] = date("d M, Y", strtotime($row['OrderDate']));
					$temp['flag'] = $row['StatusFlag'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}

		/*
		 * Get delivered orders
		 */
		function getDeliveredOrders($flag, $page){
			$response['info'] = array();
			$limit = 20;
			$offset = ($page - 1) * $limit;
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						orderdetail.SalesOrderID, 
						orderdetail.Quantity, 
						orderdetail.UnitPrice, 
						SUM(orderdetail.Quantity * orderdetail.UnitPrice) AS total, 
						salesorder.SalesOrderNumber, 
						salesorder.CustomerID, 
						salesorder.OrderDate, 
						salesorder.StatusFlag, 
						customer.PersonID, 
						person.FirstName, 
						person.LastName 
					FROM orderdetail 
					INNER JOIN salesorder ON(orderdetail.SalesOrderID = salesorder.SalesOrderID) 
					INNER JOIN customer ON(salesorder.CustomerID = customer.CustomerID) 
					INNER JOIN person ON(customer.PersonID = person.PersonID) 
					WHERE salesorder.StatusFlag = :flag 
					GROUP BY salesorder.SalesOrderNumber 
					ORDER BY salesorder.OrderDate DESC 
					LIMIT :offset, :limit
				');
				$stmt->execute(array(
					':flag' => $flag,
					':offset' => $offset,
					':limit' => $limit
				));
				while($row = $stmt->fetch()){
					$temp = array();
					$temp['sid'] = $row['SalesOrderID'];
					$temp['name'] = $row['FirstName'].' '.$row['LastName'];
					$temp['order'] = $row['SalesOrderNumber'];
					$temp['amt'] = "UGX ".number_format($row['total'], 0);
					$temp['date'] = date("d M, Y", strtotime($row['OrderDate']));
					$temp['flag'] = $row['StatusFlag'];
					array_push($response['info'], $temp);
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}

		function daysBtn($date1, $date2){
			$dt1 = date_create($date1);
			$dt2 = date_create($date2);
			$diff = date_diff($dt1, $dt2);
			return $diff->format('%d');
		}

		/*
		 * Update order status
		 */
		function updateOrderStatus($sid, $flag){
			$response['info'] = array();
			$data = $this->getReceiptData($sid);
			try{
				$stmt = $this->pdo->prepare('UPDATE salesorder SET StatusFlag = :flag WHERE SalesOrderID = :sid');
				if($this->sendEmail($data['info'], $data['items']) == TRUE){
					$stmt->execute(array(
						':flag' => $flag,
						':sid' => $sid
					));
					$response['success'] = 1;
				}else{
					$response['success'] = 0;
				}
			}catch(PDOException $e){
				
			}
			return $response;
		}
		
		/*
		 * Update ready orders
		 */
		function updateReadyOrders(){
			try{
				$stmt = $this->pdo->prepare('
					SELECT 
						SalesOrderID, 
						OrderPeriod, 
						OrderDate, 
						ModifiedDate 
					FROM salesorder 
					WHERE StatusFlag = :flag AND ModifiedDate <= :dt
				');
				$stmt->execute(array(
					':flag' => 1,
					':dt' => $this->today
				));
				while($row = $stmt->fetch()){
					if($this->daysBtn($row['OrderDate'], $this->today) > $row['OrderPeriod']){
						$this->updateOrderStatus($row['SalesOrderID'], 2);
					}
				}
			}catch(PDOException $e){
				echo $e->getMessage();
			}
		}
		
		/*
		 * Send email notification to the client
		 */
		function sendEmail($constants, $data){
			$curdate = date('d M, Y', strtotime('now'));
			$subject = 'Marble Dry Cleaners: Delivery Notification';
			$headers = "MIME-Version: 1.0"."\r\n";
			$headers .= "Content-type: text/html; charset=UTF-8"."\r\n";
			$headers .= "From: sales@marbledrycleaners.com"."\r\n";
			foreach($constants as $value){
				$order = $value['order'];
				$date = $value['odt'];
				$accn = $value['accn'];
				$to = $value['email'];
			}
			$body = "
				<table width=100% style=font-family:Arial,Helvetica,sans-serif;>
					<tr>
						<td colspan=4 style=font-weight:bold;>";
							$body .= "Delivery Notification for Order No. ";
							$body .= $order;
							$body .= "
						</td>
					</tr>";
				$body .= "
					<tr>
						<td colspan=4 style=border-bottom:solid thin #000000;>";
							$body .= "Delivered for  ";
							$body .= $curdate;
							$body .= "
						</td>
					</tr>";
				$body .= "
					<tr>
						<td colspan=4>";
							$body .= "Order Date: ";
							$body .= $date;
							$body .= "
						</td>
					</tr>";
				$body .= "
					<tr>
						<td colspan=4>";
							$body .= "Delivered On: ";
							$body .= $curdate;
							$body .= "
						</td>
					</tr>";
				$body .= "
					<tr>
						<td colspan=4>";
							$body .= "Cust. Ref: ";
							$body .= $accn;
							$body .= "
						</td>
					</tr>";
				$body .= "
					<tr>
						<td colspan=4>";
							$body .= "Description: ";
							$body .= "
						</td>
					</tr>";
				$body .= "
					<tr style=background:#000000;color:#ffffff;>
						<td>";
							$body .= "Item";
							$body .= "
						</td>
						<td>";
							$body .= "Qty";
							$body .= "
						</td>
						<td>";
							$body .= "Price";
							$body .= "
						</td>
						<td>";
							$body .= "Amount";
							$body .= "
						</td>
					</tr>";
			foreach($data as $value){
				$body .= "
					<tr>
						<td>";
							$body .= $value['pdt'];
							$body .= "
						</td>
						<td>";
							$body .= $value['qty'];
							$body .= "
						</td>
						<td>";
							$body .= $value['price'];
							$body .= "
						</td>
						<td>";
							$body .= $value['amt'];
							$body .= "
						</td>
					</tr>";
				$body .= "
					<tr>
						<td colspan=4 style=border-bottom:solid thin #000000;>";	
							$body .= $value['desc'];
							$body .= "
						</td>
					</tr>";				
			}
				$body .= "
					<tr>
						<td colspan=4 style=font-style:italic;text-align:center;>";
							$body .= "We extend life to your garments";
							$body .= "
						</td>
					</tr>";
				$body .= "
					<tr>
						<td colspan=4 style=padding-top:50px;>";
							$body .= "Thank You!!! Please Come Again...";
							$body .= "
						</td>
					</tr>
				</table>";	
			try{
				mail($to, $subject, $body, $headers);
				return TRUE;
			}catch(Exception $e){
				
			}
			return FALSE;
		}
	}
?>











