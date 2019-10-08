<?php
	class EventLogger{
		
		/*
		 * Create password changed record
		 */
		function changePassLog($file, $msg){
			$logfile = fopen($file, 'a+') or die('Unable to open file');
			fwrite($logfile, $msg."\n");
			fclose($logfile);
		}
	}
?>