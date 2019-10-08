<?php
	include_once('config.php');
	include_once('global_functions.php');
	include_once('order_ops.php');
	include_once('employee_ops.php');
	include_once('product_ops.php');
	include_once('reporting_ops.php');
	include_once('expense_ops.php');
	
	$conn = new Connection();
	$pdo = $conn->connectToDB();
	
	$global = new GlobalFunction($pdo);
	$order = new Order($pdo);
	$employee = new Employee($pdo);
	$product = new Product($pdo);
	$report = new Report($pdo);
	$expense = new Expense($pdo);
	
	$function = $_POST['func'];
	
	switch($function){
		case 'userLogin':
			echo json_encode($global->userLogin($_POST['username'], $_POST['password']));
			break;
		case 'registerDevice':
			echo json_encode($global->registerDevice($_POST['dName'], $_POST['imei']));
			break;
		case 'isDeviceExist':
			$response = array();
			$result = $global->isDeviceExist($_POST['imei']);
			if($result == TRUE){
				$response['success'] = 1;
			}else{
				$response['success'] = 0;
			}
			echo json_encode($response);
			break;
		case 'getItems':
			echo json_encode($order->getItems());
			break;
		case 'AddToCart':
			echo json_encode($order->AddToCart($_POST['pid'], $_POST['qty'], $_POST['price'], $_POST['desc'], $_POST['imei']));
			break;
		case 'getCustomerMenu':
			echo json_encode($employee->customer->getCustomerMenu());
			break;
		case 'getCartItems':
			echo json_encode($order->getCartItems($_POST['imei']));
			break;
		case 'deleteCartItem':
			$order->deleteCartItem($_POST['cid']);
			break;
		case 'addSalesOrder':
			echo json_encode($order->addSalesOrder(
				$_POST['cid'], 
				$_POST['eid'], 
				$_POST['imei'], 
				$_POST['odt'], 
				$_POST['rdt'], 
				$_POST['amt']
			));
			break;
		case 'getCustomerOrders':
			echo json_encode($order->getCustomerOrders($_POST['cid'], $_POST['imei']));
			break;
		case 'getReceiptData':
			echo json_encode($order->getReceiptData($_POST['sid']));
			break;
		case 'addCustomer':
			echo json_encode($employee->customer->addCustomer(
				$_POST['fname'], 
				$_POST['lname'], 
				$_POST['addr'], 
				$_POST['tel'], 
				$_POST['ptype'],
				$_POST['email']
			));
			break;
		case 'getCustomerList':
			echo json_encode($employee->customer->getCustomerList());
			break;
		case 'addProduct':
			echo json_encode($product->addProduct($_POST['pname'], $_POST['price']));
			break;
		case 'getProducts':
			echo json_encode($product->getProducts());
			break;
		case 'editProduct':
			echo json_encode($product->editProduct($_POST['pid'], $_POST['pname'], $_POST['price']));
			break;
		case 'deleteProduct':
			echo json_encode($product->deleteProduct($_POST['pid']));
			break;
		case 'getSalary':
			echo json_encode($employee->getSalary());
			break;
		case 'addEmployee':
			echo json_encode($employee->addEmployee(
				$_POST['fname'], 
				$_POST['lname'], 
				$_POST['gender'], 
				$_POST['addr'], 
				$_POST['tel'], 
				$_POST['title'], 
				$_POST['sid'], 
				$_POST['dob'], 
				$_POST['hdt'], 
				$_POST['ptype']
			));
			break;
		case 'getEmployees':
			echo json_encode($employee->getEmployees());
			break;
		case 'getSingleEmployee':
			echo json_encode($employee->getSingleEmployee($_POST['eid']));
			break;
		case 'deleteEmployee':
			echo json_encode($employee->deleteEmployee($_POST['eid']));
			break;
		case 'getCashDrawer':
			echo json_encode($report->getCashDrawer($_POST['from'], $_POST['to']));
			break;
		case 'getDevices':
			echo json_encode($global->getDevices());
			break;
		case 'getSalesReport':
			echo json_encode($report->getSalesReport($_POST['from'], $_POST['to'], $_POST['option']));
			break;
		case 'getConsolidatedSalesReport':
			echo json_encode($report->getConsolidatedSalesReport($_POST['from'], $_POST['to'], $_POST['option']));
			break;
		case 'getSalesItemReport':
			echo json_encode($report->getSalesItemReport($_POST['from'], $_POST['to'], $_POST['option']));
			break;
		case 'getConsolidatedSalesItemReport':
			echo json_encode($report->getConsolidatedSalesItemReport($_POST['from'], $_POST['to'], $_POST['option']));
			break;
		case 'debtPayment':
			echo json_encode($order->debtPayment($_POST['sid'], $_POST['amt']));
			break;
		case 'getDebtTotals':
			echo json_encode($employee->customer->getDebtTotals());
			break;
		case 'debtPaymentA':
			echo json_encode($employee->customer->debtPaymentA($_POST['cid'], $_POST['amt']));
			break;
		case 'addUser':
			echo json_encode($global->addUser($_POST['pid'], $_POST['uname'], $_POST['pass'], $_POST['level']));
			break;
		case 'getEmployeeMenu':
			echo json_encode($employee->getEmployeeMenu());
			break;
		case 'getReadyOrders':
			echo json_encode($order->getReadyOrders($_POST['flag'], $_POST['page']));
			break;
		case 'getPendingOrders':
			echo json_encode($order->getPendingOrders($_POST['flag'], $_POST['page']));
			break;
		case 'getDeliveredOrders':
			echo json_encode($order->getDeliveredOrders($_POST['flag'], $_POST['page']));
			break;
		case 'updateReadyOrders':
			$order->updateReadyOrders();
			break;
		case 'updateOrderStatus':
			echo json_encode($order->updateOrderStatus($_POST['sid'], $_POST['flag']));
			break;
		case 'addExpense':
			echo json_encode($expense->addExpense($_POST['date'], $_POST['amt'], $_POST['desc'], $_POST['imei']));
			break;
		case 'getDeviceExpenses':
			echo json_encode($expense->getDeviceExpenses($_POST['from'], $_POST['to'], $_POST['imei']));
			break;
		case 'getExpenses':
			echo json_encode($expense->getExpenses($_POST['from'], $_POST['to']));
			break;
		case 'getUsers':
			echo json_encode($global->getUsers());
			break;
		case 'changePassword':
			echo json_encode($global->changePassword($_POST['uid'], $_POST['pass'], $_POST['name']));
			break;
		case 'deleteUser':
			echo json_encode($global->deleteUser($_POST['uid'], $_POST['name']));
			break;
		case 'isDeviceExistA':
			echo json_encode($global->isDeviceExistA($_POST['imei'], $_POST['eid']));
			break;
		case 'editEmployeeDetail':
			echo json_encode($employee->editEmployeeDetail(
				$_POST['eid'], 
				$_POST['pid'], 
				$_POST['fname'], 
				$_POST['lname'], 
				$_POST['title'],  
				$_POST['sid'], 
				$_POST['tel'], 
				$_POST['addr']
			));
			break;
                  case 'editCustomerDetails':
			echo json_encode($employee->customer->editCustomerDetails(
				$_POST['fname'], 
				$_POST['lname'], 
				$_POST['addr'], 
				$_POST['phone'], 
				$_POST['email'], 
				$_POST['pid']
			));
			break;
                case 'getIndividualDebts':
			echo json_encode($employee->customer->getIndividualDebts($_POST['cid']));
			break;
		default:
			break;
	}
?>









