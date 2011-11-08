<?php
/**
 *	CLASS: Share Your Cart API
 *	AUTHOR: Barandi Solutions
 *	COUNTRY: Romania
 *	EMAIL: catalin.paun@barandisolutions.ro
 *	VERSION : 1.0
 *	DESCRIPTION: This class is used as a wrapper to communicate to the ShareYourCart API.
 * *    Copyright (C) 2011 Barandi Solutions
 */

if(!class_exists('ShareYourCartAPI',false)){
 
class ShareYourCartAPI {
    
    protected     $SHAREYOURCART_URL = "www.shareyourcart.com";
    protected     $SHAREYOURCART_API;
    protected     $SHAREYOURCART_API_REGISTER;
    protected     $SHAREYOURCART_API_RECOVER;
    protected     $SHAREYOURCART_API_ACTIVATE;
    protected     $SHAREYOURCART_API_DEACTIVATE;
    protected     $SHAREYOURCART_API_CREATE;
    protected     $SHAREYOURCART_API_VALIDATE;
    protected     $SHAREYOURCART_CONFIGURE;
    protected     $SHAREYOURCART_BUTTON_JS;
    
    /**
    * Constructor
    * @param null
    */
    function __construct() {
        
        $this->SHAREYOURCART_API            = (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'],'on') ? 'https://' : 'http://') . $this->SHAREYOURCART_URL;
        $this->SHAREYOURCART_REGISTER       = $this->SHAREYOURCART_API.'/account/create';
        $this->SHAREYOURCART_API_REGISTER   = $this->SHAREYOURCART_API.'/account/create';
        $this->SHAREYOURCART_API_RECOVER    = $this->SHAREYOURCART_API.'/account/recover';
        $this->SHAREYOURCART_API_ACTIVATE   = $this->SHAREYOURCART_API.'/account/activate';
        $this->SHAREYOURCART_API_DEACTIVATE = $this->SHAREYOURCART_API.'/account/deactivate';
        $this->SHAREYOURCART_API_CREATE     = $this->SHAREYOURCART_API.'/session/create';
        $this->SHAREYOURCART_API_VALIDATE   = $this->SHAREYOURCART_API.'/session/validate';
        $this->SHAREYOURCART_CONFIGURE      = $this->SHAREYOURCART_API.'/configure';   
	$this->SHAREYOURCART_BUTTON_JS      = $this->SHAREYOURCART_API.'/js/button.js';
    }
    
    /**
    * startSession
    * @param array $params
    * @return array $data  
    */
    public function startSession($params) {
	//make sure the session is started
	if(session_id() == '')
            session_start();
        
        $session = curl_init($this->SHAREYOURCART_API_CREATE);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        // If the operation was not succesfull, print the error
        if($httpCode != 200) 
            throw new Exception($response);
        
        // Decode the result
        $results = json_decode($response, true);
        
        // Find the token
        if(isset($results['token'])) {
            
            // Link the token with the current cart ( held in session id )
            $data = array(
                'token' => $results['token'],
                'session_id' => session_id(),
            );
                
            // A token was obtained, so redirect the browser
            header("Location: $results[session_url]", true, 302);
            return $data;
                
        }
        
        //show the raw response received ( for debug purposes )
        throw new Exception($response);
    }
    
    /**
    * make sure the coupon is valid
    * @param null 
    */
    public function assertCouponIsValid($token, $coupon_code, $coupon_value, $coupon_type) {
        
        // Verifies POST information
        if(!isset($token, $coupon_code, $coupon_value, $coupon_type)) {
               
            throw new Exception("At least one of the parameters is missing.");
        }
        
        // Urlencode and concatenate the POST arguments
        $params = array(
            'token' => $token,
            'coupon_code' => $coupon_code,
            'coupon_value' => $coupon_value,
            'coupon_type'=> $coupon_type,
        );

        // Make the API call
        $session = curl_init($this->SHAREYOURCART_API_VALIDATE);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        //if the operation was not succesfull, print the error
		if($httpCode != 200) 
            throw new Exception("Coupon Invalid: " . $response);
        
        $results = json_decode($response, true);    

        //if the result is not valid, print it
        if(!isset($results['valid']) || !($results['valid'] === true)) 
            throw new Exception("Coupon Invalid: " . $response);
    }
    
    /**
    * register
    * @param string $secretKey
    * @param string $domain
    * @param string $email
    * @param string $message  
    * @return array json_decode  
    */
    public function register($secretKey, $domain, $email, &$message = null) {
        
        // Urlencode and concatenate the POST arguments
        $params = array(
                'secret_key' => $secretKey,
                'domain' => $domain,
                'email' => $email,
        );

        // Make the API call
        $session = curl_init($this->SHAREYOURCART_API_REGISTER);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        // If the operation was not succesfull, return FALSE
        if($httpCode != 200) {
            if(isset($message)) $message = $response;
			
			return false;     
        }
        
        // Return the response after decoding it
        return json_decode($response, true);    
        
    }
    
    /**
    * recover
    * @param string $secretKey
    * @param string $domain
    * @param string $email
    * @param string $message  
    * @return boolean
    */
    public function recover($secretKey, $domain, $email, &$message = null) {
        
        // Urlencode and concatenate the POST arguments
        $params = array(
            'secret_key' => $secretKey,
            'domain' => $domain,
            'email' => $email,
        );

        // Make the API call
        $session = curl_init($this->SHAREYOURCART_API_RECOVER);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        //if the operation was not succesfull, return FALSE
        if($httpCode != 200) {
            if(isset($message)) $message = $response;
			
			return false;
        }
        
        return true;
        
    }
    
    /**
    * setAccountStatus
    * @param string $secretKey
    * @param string $clientId
    * @param string $appKey
    * @param string $activate
    * @param string $message  
    * @return boolean
    */
    public function setAccountStatus($secretKey, $clientId, $appKey, $activate = true, &$message = null) {
        
        // Urlencode and concatenate the POST arguments
        $params = array(
            'secret_key' => $secretKey,
            'client_id' => $clientId,
            'app_key' => $appKey,
        );

        //make the API call
        $session = curl_init($activate ? $this->SHAREYOURCART_API_ACTIVATE : $this->SHAREYOURCART_API_DEACTIVATE);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        // Notify the caller
        if($httpCode != 200) {
            if(isset($message)) $message = $response;
			
			return false;
        }
        
        return true;
    }
}

} //END IF
?>