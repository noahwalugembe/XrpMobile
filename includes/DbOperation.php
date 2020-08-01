<?php
 
class DbOperation
{
    //Database connection link
    private $con;
 
    //Class constructor
    function __construct()
    {
        //Getting the DbConnect.php file
        require_once dirname(__FILE__) . '/DbConnect.php';
 
       
    }
	//ussd api 
	
	/*
	* The read operation
	* When this method is called it is returning all the existing record of the database
	*/
	function getBalance($Seed){
		
         $dataArray  = array('Seed'=> $Seed); 
         $url='http://xrpmobile.herokuapp.com/api/get_balance';		      
		      

         $ch = curl_init();
         $data = http_build_query($dataArray);
         $getUrl = $url."?".$data;

         //$getUrl=$url;
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
         curl_setopt($ch, CURLOPT_URL, $getUrl);
         curl_setopt($ch, CURLOPT_TIMEOUT, 80);
 
         $response = curl_exec($ch);
 
         if(curl_error($ch)){
	        return 'Request Error:' . curl_error($ch);
         }else{
            return $response;    
        }
	    curl_close($ch);
	
	}
	
	
	function getSendMoney($send_amount,$payId,$wallet_Seed){
		
         $dataArray  = array(
		           'send_amount'=> $send_amount,
		           'payId'=>$payId,
		           'wallet_Seed'=> $wallet_Seed
		 
		 ); 
		 
         $url='https://xrpmobile.herokuapp.com/api/get_send_money';		      
		 //$url='https://xrpmobile.herokuapp.com/api/get_send_money?send_amount=1&payId=alice$dev.payid.xpring.money&wallet_Seed=shFtJcbthmp81z1PqECgPLgGeW8Uk';    

         $ch = curl_init();
         $data = http_build_query($dataArray);
         $getUrl = $url."?".$data;

         //$getUrl=$url;
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
         curl_setopt($ch, CURLOPT_URL, $getUrl);
         curl_setopt($ch, CURLOPT_TIMEOUT, 80);
 
         $response = curl_exec($ch);
 
         if(curl_error($ch)){
	        return 'Request Error:' . curl_error($ch);
         }else{
            return $response;    
        }
	    curl_close($ch);
	
	}
	
	
	
	
	
}