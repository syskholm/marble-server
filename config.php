<?php
	class Connection{
    protected $host ="mysql:host=your-database-host.com;dbname=marble";
		protected $username ="";
		protected $password ="";
		protected $pdo;
		
		public function connectToDB(){
			try{
				$this->pdo = new PDO($this->host, $this->username, $this->password);
				$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
				$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
			}catch(PDOException $e){
				echo "Failed to connect to database: ".$e->getMessage();
				die();
			}
			return $this->pdo;
		}
		
		public function closeConnection(){
			$this->pdo = null;
			return $this->pdo;
		}
	}
?>