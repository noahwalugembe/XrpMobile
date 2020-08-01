<?php

//getting the dboperation class
	require_once 'includes/DbOperation.php';
/* Simple sample USSD registration application
 * USSD gateway that is being used is Africa's Talking USSD gateway
 */

// Print the response as plain text so that the gateway can read it
//header('Content-type: text/plain');

/* local db configuration */
$dsn = 'mysql:dbname=old_vision_archive;host=localhost;'; //database name
$user = 'root'; // your mysql user 
$password = 'vision@2020'; // your mysql password

//  Create a PDO instance that will allow you to access your database
try {
    $dbh = new PDO($dsn, $user, $password);
}
catch(PDOException $e) {
    //var_dump($e);
    echo("PDO error occurred");
}
catch(Exception $e) {
    //var_dump($e);
    echo("Error occurred");
}

// Get the parameters provided by Africa's Talking USSD gateway
$phone = $_POST['phoneNumber'];
$session_id = $_POST['sessionId'];
$service_code = $_POST['serviceCode'];
$ussd_string= $_POST['text'];

//set default level to zero
$level = 0;

/* Split text input based on asteriks(*)
 * Africa's talking appends asteriks for after every menu level or input
 * One needs to split the response from Africa's Talking in order to determine
 * the menu level and input for each level
 * */
//$ussd_string_exploded = explode ("*",$ussd_string);

//Explode the text to get the value of the latest interaction - think 1*1
$ussd_string_exploded = explode ("*",$ussd_string);
$userResponse=trim(end($ussd_string_exploded));


//check the level of user from the db and retain to zero if none is found ".$session_id." 
$sth = $dbh->prepare("select level from session_levels where session_id ='".$session_id."'");  
$sth->execute();


if($sth->errorCode() == 0){
	$result = $sth->fetch(PDO::FETCH_OBJ);
    $level = $result->level;
	
	if ($level == NULL) { 
	   $level=0;
	   //$ussd_text = $level." your registration was successful. Your email is ".$level." and phone number is ".$phone_number;
       //ussd_proceed($ussd_text);
	}
	
	# close the pdo connection  
    //$dbh = null;
}else{
   $errors = $sth->errorInfo();
   ussd_proceed($errors);
   
    # close the pdo connection  
   //$dbh = null;	
  }
//=======================check if user/subscriber is in the database====================================================

//check the level of user from the db and retain to zero if none is found ".$session_id." 
$sthh = $dbh->prepare("SELECT * FROM xrpaccount WHERE phoneNumber LIKE '%".$phone."%' LIMIT 1");  
$sthh->execute();


if($sthh->errorCode() == 0){
	
	$userAvailable = $sthh->fetch(PDO::FETCH_OBJ);
    
	//Accessing usser variables
	$wallet_Seed = $userAvailable->wallet_Seed;
	$PayId = $userAvailable->PayId;
	$password = $userAvailable->password;
	$phoneNumber = $userAvailable->phoneNumber;
	
	if($id_number OR $phoneNumber!=NULL){
	   //Serve the Services Menu (if the user is fully registered, 
	   //level 0 and 1 serve the basic menus, while the rest allow for financial transactions)
       
	   
	   
	   if($level==""|| $level==0 ){
		   
		    //9b. Graduate user to next level & Serve Main Menu
			$stmt = $dbh->prepare("INSERT INTO `session_levels`(`session_id`,`phoneNumber`,`level`) VALUES('".$session_id."','".$phone."',1)");
			$stmt->execute();
            //Serve our services menu ".$PayId."
		    $response = "CON Welcome ".$PayId." \nSelect an option.\n";
			$response .= " 1. Account Balance\n";
		    $response .= " 2. Send Money\n";
		    $response .= " 3. Deposit Funds\n";						
			$response .= " 4. Mini Statement\n";
		    $response .= " 5. PIN Change\n";
																																					

			//Print the response onto the page so that our gateway can read it
			header('Content-type: text/plain');
 			echo $response;
		   
        }
		
		if ($level==1){
           if ($ussd_string_exploded[0] == "1"){
           
			  //downgrade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=0 where `session_id`='".$session_id."'");
              $stmt->execute();
			  
			  getAcountBalance($wallet_Seed);
          
		  
		  }elseif ($ussd_string_exploded[0] == "2"){
			  
			  //upgrade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=7 where `session_id`='".$session_id."'");
              $stmt->execute();
			  
			  $response = "CON XRP Amount to send in digits\n";
			  $response .= "Press 0 to go back to menue.\n";
					   
			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
               
          
		  
		  }elseif ($ussd_string_exploded[0] == "3"){
			  
			 //demote
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=0 where `session_id`='".$session_id."'");
              $stmt->execute();
			  
			  $response = 'CON Deposit Funds on payId : '.$PayId.'\n';
			  $response .= 'Press 0 to go back.\n';
					   
			  
			  
			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
               
          
		  
		  }elseif ($ussd_string_exploded[0] == "4"){
			  
			  /*
			  $response = "CON Mini Statement\n";

			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
			  */
			  
			  getMiniStatement($wallet_Seed,$dbh);
               
          
		  
		  }elseif ($ussd_string_exploded[0] == "5"){
			  
			  $response = "CON PIN Change\n this part is under construction";
					   
			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
               
          
		  
		  }elseif($ussd_string_exploded[0]!== "1" || $ussd_string_exploded[0]!== "2" || $ussd_string_exploded[0]!== "3" || $ussd_string_exploded[0]=! "4" || $ussd_string_exploded[0]=! "5" ){
			  
			  // Return user to Main Menu & Demote user's level
              $response = "CON You have to choose a service.\n";
              $response .= "Press 0 to go back.\n";
              //demote
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=0 where `session_id`='".$session_id."'");
              $stmt->execute();

              // Print the response onto the page so that our gateway can read it
              header('Content-type: text/plain');
              echo $response;
        
		
		  }
		}
		
		if ($level==7){
			
			  //upgrade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=8 where `session_id`='".$session_id."'");
              $stmt->execute();
			
			  $response = "CON Pleas enter recivers PayId";
					   
			  
			  //initalize transaction
			  $timestamp=date('Y-m-d H:i:s', time());
			  
			  $stmtt = $dbh->prepare("INSERT INTO `temporder`(`wallet_Seed`,`send_amount`,`session_id`,`phoneNumber`,`timestamp`) VALUES('".$wallet_Seed."','".$userResponse."','".$session_id."','".$phone."','".$timestamp."')");
			  $stmtt->execute();
             
			  
			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
			
			
		}
		
		if ($level==8){
			
			  //upgrade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=9 where `session_id`='".$session_id."'");
              $stmt->execute();
			
			  $response = "CON XrpMobile Pin or Password";
			  
			  //update payid
              $stmtt = $dbh->prepare("UPDATE `temporder` SET `PayId`='".$userResponse."' where  `session_id`='".$session_id."'");
              $stmtt->execute();
					   
			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
			
			
		}
		
		if ($level==9){
			
			   //down grade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=0 where `session_id`='".$session_id."'");
              $stmt->execute();
			  
			  //varify transaction
              $stmtt = $dbh->prepare("UPDATE `temporder` SET `pin`='".$userResponse."' where  `session_id`='".$session_id."'");
              $stmtt->execute();
			  
			 
			  postSendMoney($session_id,$password,$dbh);
			
		}

	}else{

       //youser input array 
	     $res = array();
	   
	   // If user is not registerd send them to the registration menu
        if($level==""|| $level==0 ){
		      //9b. crate new user session
			  $stmt = $dbh->prepare("INSERT INTO `session_levels`(`session_id`,`phoneNumber`,`level`) VALUES('".$session_id."','".$phone."',2)");
			  $stmt->execute();
			  
			  $response = "CON Youre account is not yet created \n";
			  $response .= "Get ready with your payID and XRP wallet Seed.\n";
			  $response .= "Press 0 to register.\n";
			  
			 
					   
			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
		}
		
		if ($level==2){
			
			  //upgrade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=3 where `session_id`='".$session_id."'");
              $stmt->execute();
			  
			  $response = "CON Pleas enter your wallet_Seed \n";
			  
              $res['wallet_Seed'] = $userResponse; 
			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
			  
			
			
		}
		
		if ($level==3){
			  //upgrade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=4 where `session_id`='".$session_id."'");
              $stmt->execute();
			  
			  $response = "CON Pleas enter your wallet PayId\n";
			  
			  
			  //insert walet seed
			  $stmtt = $dbh->prepare("INSERT INTO `tempaccount`(`wallet_Seed`,`phoneNumber`) VALUES('".$userResponse."','".$phone."')");
			  $stmtt->execute();
			  
			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
			
			
		}
		
		if ($level==4){
			
			  //upgrade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=5 where `session_id`='".$session_id."'");
              $stmt->execute();
			  
			  $response = "CON Pleas  enter your new password\n";
			  
			  
			  //update payid
              $stmtt = $dbh->prepare("UPDATE `tempaccount` SET `PayId`='".$userResponse."' where `phoneNumber`='".$phone."'");
              $stmtt->execute();
			  
			  
			  // Print the response onto the page so that our gateway can read it
			  header('Content-type: text/plain');
 			  echo $response;
			
			
		}
		
		if ($level==5){
			
			  //upgrade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=0 where `session_id`='".$session_id."'");
              $stmt->execute();
	
			  
			  //update payid
              $stmtt = $dbh->prepare("UPDATE `tempaccount` SET `password`='".$userResponse."' where `phoneNumber`='".$phone."'");
              $stmtt->execute();
			  
			  register($phone, $dbh);
			 
			
			
		}
		
		
		
		if ($level==6){
			
			  //upgrade
              $stmt = $dbh->prepare("UPDATE `session_levels` SET `level`=0 where `session_id`='".$session_id."'");
              $stmt->execute();
			
			  register($phone, $dbh);
			
			
		}
	   
	}

}else{
   $errors = $sthh->errorInfo();
   ussd_proceed($errors);
   
  	
  }










/* The ussd_proceed function appends CON to the USSD response your application gives.
 * This informs Africa's Talking USSD gateway and consecuently Safaricom's
 * USSD gateway that the USSD session is till in session or should still continue
 * Use this when you want the application USSD session to continue
*/
function ussd_proceed($ussd_text){

	$response .= "CON $ussd_text \n";

	header('Content-type: text/plain');
	echo $response;
}

/* This ussd_stop function appends END to the USSD response your application gives.
 * This informs Africa's Talking USSD gateway and consecuently Safaricom's
 * USSD gateway that the USSD session should end.
 * Use this when you to want the application session to terminate/end the application
*/
function ussd_stop($ussd_text){
    echo "END $ussd_text";
}

//This is the home menu function
function display_menu()
{
    $ussd_text =    "1. Register \n 2. About \n"; // add \n so that the menu has new lines
    ussd_proceed($ussd_text);
}


// Function that hanldles About menu
function about($ussd_text)
{
    $ussd_text =    "This is a sample registration application";
    ussd_stop($ussd_text);
}

// Function that handles Registration menu
function register($phone, $dbh){

		//check the level of user from the db and retain to zero if none is found ".$session_id." 
        $sth = $dbh->prepare("select wallet_Seed,PayId,phoneNumber,password from tempaccount where phoneNumber ='".$phone."'");  
        $sth->execute();


        if($sth->errorCode() == 0){
	       $result = $sth->fetch(PDO::FETCH_OBJ);
           $wallet_Seed = $result->wallet_Seed;
		   $PayId = $result->PayId;
		   $phoneNumber = $result->phoneNumber;
		   $password = $result->password;

	      // build sql statement
           $sthh = $dbh->prepare("INSERT INTO  xrpaccount (wallet_Seed,PayId,phoneNumber,password) VALUES('$wallet_Seed','$PayId','$phoneNumber','$password')");
          //execute insert query   
           $sthh->execute();
           if($sthh->errorCode() == 0) {
                    $ussd_text = $PayId."  Press 0 to go Menue.\n your registration was successful. Your wallet Seed is ".$wallet_Seed." and Phone Number is ".$phoneNumber;
                    ussd_stop($ussd_text);
           } else {
               $errors = $sth->errorInfo();
               ussd_stop($errors);
		   }
            
        }else{
           $errors = $sth->errorInfo();
           ussd_stop($errors);
    
        }
				
}


//Xrp net work operations

//get acount balance
function getAcountBalance($wallet_Seed){
	
    //$Seed = "shFtJcbthmp81z1PqECgPLgGeW8Uk";
	$Seed=$wallet_Seed;
    
	$db = new DbOperation();
	$result = $db->getBalance($Seed);
    
	// Option 1: through the use of an array.
    $jsonArray = json_decode($result,true);

    $key = "balance";

    $balance = $jsonArray[$key];
	
	//$balance=$result;
	
	$ussd_text = "Press 0 to go Menue.\n your account balance is ".$balance;
    ussd_stop($ussd_text);
	
	
}

function postSendMoney($session_id,$password,$dbh){
	//check the level of user from the db and retain to zero if none is found ".$session_id." 
    $sth = $dbh->prepare("select send_amount,payId,wallet_Seed,pin from temporder where `session_id`='".$session_id."'");  
    
	//$sth = $dbh->prepare("select send_amount,payId,wallet_Seed,pin from temporder where `session_id`='ATUid_cbc90023ab6bed5755e15c9e2a1fe43f'");  
    $sth->execute();
	
	
	if($sth->errorCode() == 0){
	       $result = $sth->fetch(PDO::FETCH_OBJ);
           $send_amount = $result->send_amount;
		   $payId = $result->payId;
		   $wallet_Seed = $result->wallet_Seed;
		   $pin = $result->pin;
		   
		   if($password==$pin){
			   
			   $db = new DbOperation();
	           $result = $db->getSendMoney($send_amount,$payId,$wallet_Seed);
    
	           // Option 1: through the use of an array.
               $jsonArray = json_decode($result,true);

               $key = "sucess";

               $sucess = $jsonArray[$key];
	
	           
			   //update transaction status
			   $stmt = $dbh->prepare("UPDATE `temporder` SET `status`='".$sucess."' where `session_id`='".$session_id."'");
               $stmt->execute();
			   
			   $ussd_text = "Your transaction is ".$sucess;
               ussd_stop($ussd_text);
			   
		      
			  
		   
		   }else{
			  $ussd_text ="Your have entered incorect pin ".$pin;
              ussd_stop($ussd_text); 
			   
		   }

        }else{
           $errors = $sth->errorInfo();
           ussd_stop($errors);
    
        }
    
	
}

function getMiniStatement($wallet_Seed,$dbh){
	//check the level of user from the db and retain to zero if none is found ".$session_id." 

	//$sth = $dbh->prepare("select timestamp,send_amount from temporder where `wallet_Seed`='".$wallet_Seed."'");  
    
	$sth = $dbh->prepare("select timestamp,send_amount from temporder where `wallet_Seed`='".$wallet_Seed."'");  
    
    $sth->execute();

	if($sth->errorCode() == 0){
	       $result = $sth->fetch(PDO::FETCH_OBJ);
           $send_amount = $result->send_amount;
	
		   $ussd_text = "your transactions are \n".$send_amount;
           ussd_stop($ussd_text);
		
        }else{
           $errors = $sth->errorInfo();
           ussd_stop($errors);
    
        }
    
	
}


# close the pdo connection  
$dbh = null;
?>