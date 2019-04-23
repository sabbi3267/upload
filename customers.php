<?php

session_start();


require "database.php";


require "customer.class.php";
$cust = new Customer();



if(isset($_GET["fun"])) {
	$fun = $_GET["fun"];
	if (!isset($_SESSION["user_id"])) { 
		if (!($fun == "display_create_form" ||  $fun == "insert_db_record" )) { 
			$fun="display_login_view";
		}
	}
}
else $fun = "display_login"; 

switch ($fun) {
	case "logout":				$cust->logout();
		break;
	case "check_login":			$cust->check_login();
		break;
	case "display_login_view":  		$cust->login_view();
		break;
    	case "display_list":        		$cust->list_records();
		break;
	case "list_pics":			$cust->list_pics();
		break;
	case "logout":				$cust->logout();
		break;
	case "check_login":			$cust->check_login();
		break;
	case "display_login":  			$cust->login_view();
		break;
    	case "display_list":        		$cust->list_records();
        	break;
    	case "display_create_form": 		$cust->create_record();
        	break;
    	case "display_read_form":   		$cust->read_record($id); 
       		 break;
    	case "display_update_form": 		$cust->update_record($id);
        	break;
    	case "display_delete_form": 		$cust->delete_record($id); 
        	break;
    	case "insert_db_record":    		$cust->insert_db_record();
        	break;
    	case "update_db_record":    		$cust->update_db_record($id);
        	break;
    	case "delete_db_record":    		$cust->delete_db_record($id);
       		 break;
    	default: 
        echo " Invalid function call ";
        exit();
        break;
}
