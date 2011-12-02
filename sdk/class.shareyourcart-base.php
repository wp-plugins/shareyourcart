<?php

/**
 * 	CLASS: Share Your Cart Base
 * 	AUTHOR: Barandi Solutions
 * 	COUNTRY: Romania
 * 	EMAIL: catalin.paun@barandisolutions.ro
 * 	VERSION : 1.0
 * 	DESCRIPTION: This class is used as a base class for every PHP plugin we create.
 * *    Copyright (C) 2011 Barandi Solutions
 */
require_once(dirname(__FILE__) ."/class.shareyourcart-api.php");

if(!class_exists('ShareYourCartBase',false)){

abstract class ShareYourCartBase extends ShareYourCartAPI {

	//this array is used to hold function calls between different instances of this class
	private static $_SINGLE_FUNCTIONS_CALLS = array();
	protected static $_DB_VERSION = '1.1';

	/**
	 * Execute NonQuery SQL
	 * @param string action
	 * @param string extra
	 * @return boolean
	 */
	protected abstract function executeNonQuery($sql);

	/**
	 *
	 * Get the row returned from the SQL
	 *
	 * @return an associative array containing the data of the row OR NULL
	 *         if there is none
	 */
	protected abstract function getRow($sql);

	/**
	 * Abstract getTableName
	 * @param string key
	 *
	 */
	protected abstract function getTableName($key);

	/**
	 * Abstract setConfigValue
	 * @param string option
	 * @param string value
	 * @return boolean
	 */
	protected abstract function setConfigValue($field, $value);

	/**
	 * Abstract setConfigValue
	 * @param string option
	 * @param string value
	 * @return string
	 */
	protected abstract function getConfigValue($field);

	/**
	 * 	Description: This returns an array with the details of the product
	 *               displayed on this page. If it is not a product, it
	 *               will return FALSE
	 * @remarks  this will be mostly used by LITE integrations, so no need to make it mandatory
	 * @param null
	 * @return boolean / array
	 */
	protected function getCurrentProductDetails(){
		
		return FALSE;
	}
	
	/**
	*
	* Return TRUE if this page describes a single product, otherwise FALSE
	*
	*/
	protected abstract function isSingleProduct();

	/**
	 *
	 * Return the URL to be called when the button is pressed
	 *
	 */
	protected abstract function getButtonCallbackURL();

	/*
	 *
	 * Create url for the specified file. The file must be specified in relative path
	 * to the base of the plugin
	 */
	protected abstract function createUrl($file);

	/**
	 *
	 * Called after the session has been reloaded
	 *
	 */
	protected abstract function loadSessionData();

	/**
	 *
	 * Returns the plugin's secret key. This should be overwritten only by the actual plugin
	 *
	 */
	protected abstract function getSecretKey();

	/**
	 *
	 * Insert a row in the table
	 * @param string tableName
	 * @param array data
	 *
	 */
	protected abstract function insertRow($tableName, $data);

	/**
	 *
	 * Apply the coupon code
	 *
	 */
	protected abstract function applyCoupon($coupon_code);

	/**
	 * install the plugin
	 * @param null
	 * @return boolean
	 */
	public function install(&$message = null) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		//create the tokens table
		$this->createTable($this->getTableName('shareyourcart_tokens'), array(
            'id' => 'int(11)',
            'token' => 'varchar(255)',
            'session_id' => 'varchar(255)',
		), 'id');

		//create the coupon log table
		$this->createTable($this->getTableName('shareyourcart_coupons'), array(
            'id' => 'int(11)',
            'token' => 'varchar(255)',
            'coupon_id' => 'varchar(255)',
		), 'id');

		//save the DB version, for later use
		$this->setConfigValue('db_version', self::$_DB_VERSION);

		//if we have credentials in the DB, try to activate the plugin
		//with them
		$activated = false;
		$appKey = $this->getAppKey();
		$clientId = $this->getClientId();
		if(!empty($appKey) && !empty($clientId)){
		
			$activated = $this->activate($message);
		}
		
		//if activation did not work, try to get the proper account credentials
		if(!$activated){
			//register or recover the account credentials
			$this->getAccountCredentials($message);
		}

		return true;
	}

	/**
	 * uninstall the plugin
	 * @param null
	 * @return boolean
	 */
	public function uninstall(&$message = null) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		//first, make sure we deactivate the plugin
		$this->deactivate($message);

		//remove the tables
		$this->dropTable($this->getTableName('shareyourcart_tokens'));
		$this->dropTable($this->getTableName('shareyourcart_coupons'));

		//remove the db version
		$this->setConfigValue('db_version', null);

		return true;
	}

	/**
	 * activate the plugin
	 * @param null
	 * @return boolean
	 */
	public function activate(&$message = null) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		//active the API
		if ($this->setAccountStatus($this->getSecretKey(), $this->getClientID(), $this->getAppKey(), true, $message) === TRUE) {

			$this->setConfigValue("account_status", "active");
			return true;
		} else {

			$this->setConfigValue("account_status", "inactive");
			return false;
		}
	}

	/**
	 * deactivate the plugin
	 * @param null
	 * @return boolean
	 */
	public function deactivate(&$message = null) {

		//send the notification to the API
		$this->setAccountStatus($this->getSecretKey(), $this->getClientID(), $this->getAppKey(), false, $message);

		//no matter what the API says, disable this plugin
		$this->setConfigValue("account_status", "inactive");

		return true;
	}

	/**
	 * getAppKey
	 * @param null
	 * @return appKey
	 */
	public function getAppKey() {

		return $this->getConfigValue('appKey');
	}

	/**
	 * getClientID
	 * @param null
	 * @return clientID
	 */
	public function getClientId() {
		return $this->getConfigValue('clientId');
	}

	/**
	 * Check if the plugin is active, or not
	 * @param null
	 * @return boolean
	 */
	public function isActive() {

		return ($this->getConfigValue('account_status') == "active");
	}

	/**
	 * startSession
	 * @param string $params
	 * @return boolean
	 */
	public function startSession($params) {

		//make sure the params contain the required entries
		if (!isset($params['app_key']))
		$params['app_key'] = $this->getAppKey();

		if (!isset($params['client_id']))
		$params['client_id'] = $this->getClientId();

		//create a new session
		$data = parent::startSession($params);

		//save session details
		$this->insertRow($this->getTableName('shareyourcart_tokens'), $data);

		return true;
	}

	/**
	 * ensureCouponIsValid
	 * @param string $params
	 * @return boolean
	 */
	public function assertCouponIsValid($token, $coupon_code, $coupon_value, $coupon_type) {

		//first call the parent function
		parent::assertCouponIsValid($token, $coupon_code, $coupon_value, $coupon_type);

		//get the session_id associated with the token
		$session_id = $this->getSessionId($token);

		//make sure the session is valid
		if ($session_id === null) {
			throw new Exception('Token not found');
		}

		//resume the session
		session_destroy();
		session_id($session_id);
		session_start();

		$this->loadSessionData();
	}

	/**
	 * getAccountCredentials
	 * @param null
	 * @return string OR FALSE
	 */
	public function getAccountCredentials(&$message = null,$alternative_email = null) {

		$email_for_recover = isset($alternative_email) ? $alternative_email : $this->getAdminEmail();
                
		// It will test to see if the recovery option works
		if (($recover = $this->recover($this->getSecretKey(), $this->getDomain(), $email_for_recover, $message)) == true) {

			return true;
		}

		// It will test to see if the register option will work
		if (!(($register = $this->register($this->getSecretKey(), $this->getDomain(), $this->getAdminEmail(),$message)) === false)) {

			$this->setConfigValue('appKey', @$register['app_key']);
			$this->setConfigValue('clientId', @$register['client_id']);
                        
                        $this->setConfigValue("account_status", "active");

			return true;
		}

		// It will inform the user that the domain is already registered, and that recovery failed
		return FALSE;
	}

	/**
	 * TODO: remove this function
	 * getAccountCredentialsAJAX
	 * @return JSON
	 */
	public function getAccountCredentialsAJAX() {

		//set output format
		header("Content-Type: application/json");

		$message = '';
		$result = $this->getAccountCredentials($message);

		if (!($result === FALSE)) {
			//send the actual message
			echo json_encode($message);
		} else { //the process failed, so try with another email
			ob_start();
			include(dirname(__FILE__) . '/views/accountCredentialsPartial.php');
			echo json_encode(ob_get_clean());
		}
	}

	/**
	 * simply show the button
	 * @param null
	 * @return boolean
	 */
	public function showButton() {

		echo $this->getButton();
	}

	/**
	 *
	 * get the button code
	 *
	 */
	public function getButton() {

		//make sure the API is active
		if(!$this->isActive()) return;

		return $this->renderButton($this->getButtonCallbackURL());
	}

	/**
	 * renderButton
	 * @param null
	 * @return boolean
	 */
	protected function renderButton($callback_url) {
		ob_start();

		$current_button_type = $this->getConfigValue("button_type");
		$button_html = $this->getConfigValue("button_html");
		
		$button_img = $this->getConfigValue("btn-img");
		$button_img_width = $this->getConfigValue("btn-img-width");
		$button_img_height = $this->getConfigValue("btn-img-height");
		
		$button_img_hover = $this->getConfigValue("btn-img-h");
		$button_img_hover_width = $this->getConfigValue("btn-img-h-width");
		$button_img_hover_height = $this->getConfigValue("btn-img-h-height");

		switch ($current_button_type)
		{
			case '1':
				include(dirname(__FILE__) . '/views/button.php');
				break;
			case '2':
				include(dirname(__FILE__) . '/views/button-img.php');
				break;
			case '3':
				include(dirname(__FILE__) . '/views/button-custom.php');
				break;
			default:
				include(dirname(__FILE__) . '/views/button.php');
				break;
		}


		return ob_get_clean();
	}

	/**
	 *
	 * show the button on a product page
	 *
	 */
	public function showProductButton() {

		echo $this->getProductButton();
	}

	/**
	 *
	 * get the button for a product page
	 *
	 */
	public function getProductButton() {

		if($this->isSingleProduct() && !$this->getConfigValue('hide_on_product')){

			return	$this->getButton();
		}

		//else return nothing
		return null;
	}

	/**
	 *
	 * show the button on a cart page
	 *
	 */
	public function showCartButton() {
		
		echo $this->getCartButton();
	}

	/**
	 *
	 * get the button for the cart page
	 *
	 */
	public function getCartButton() {

		if(!$this->getConfigValue('hide_on_checkout')){

			return $this->getButton();
		}

		//return nothing
		return null;
	}

	/**
	 *
	 * Simply show the page header
	 *
	 */
	public function showPageHeader() {

		echo $this->getPageHeader();
	}

	/**
	 * Get the page header code
	 * @param null
	 * @return boolean
	 */
	public function getPageHeader() {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		$data = $this->getCurrentProductDetails();

		ob_start();
		include(dirname(__FILE__) . '/views/page-header.php');
		return ob_get_clean();
	}

	/**
	 * Show the admin header code
	 * @param null
	 * @return boolean
	 */
	public function showAdminHeader() {

		echo $this->getAdminHeader();
	}

	/**
	 * Get the admin header code
	 * @param null
	 * @return boolean
	 */
	public function getAdminHeader() {
			
		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		ob_start();
		include(dirname(__FILE__) . '/views/admin-header.php');
		return ob_get_clean();
	}

	/**
	 * show the admin page
	 * @param null
	 * @return boolean
	 */
	public function showAdminPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		echo $this->getAdminPage($html,$show_header,$show_footer);
	}

	/**
	 *
	 * get the admin page
	 *
	 */
	public function getAdminPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		$status_message = '';
		
		//check if this is a post for this particular page
		if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
		!empty($_POST['syc-account-form'])) {

			$this->setConfigValue('appKey', $_POST['app_key']);
			$this->setConfigValue('clientId', $_POST['client_id']);

			//it is vital that we call the activation API here, to make sure, that the account is ACTIVE
			//call the account status function
			$message = '';
			if ($this->activate($message) == true) {

				$status_message = 'Account settings successfully saved';
			} else {

				//the account did not activate, so show the error
				$status_message = $message;
			}
		}
		//the user decided to disable the API
		else if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
		!empty($_POST['disable-API'])){
			
			$this->deactivate($status_message);
		}
		//the user decided to activate the API
		else if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
		!empty($_POST['enable-API'])){
			
			$this->activate($status_message);
		}  
		else if (!empty($_REQUEST['syc-get-account'])){

			//the Get Your's Now button was pressed
			$message = '';
			$result = $this->getAccountCredentials($message,@$_REQUEST['new-email']);

			if (!($result === FALSE)) {
				//send the actual message
				$status_message = $message;
			} else { //the process failed, so try with another email
				ob_start();
				include(dirname(__FILE__) . '/views/account-credentials-partial.php');
				$status_message = ob_get_clean();
			}
		}

		// Display the view
		ob_start();
		include(dirname(__FILE__) . '/views/admin-page.php');
		return ob_get_clean();
	}

	/**
	 *
	 * show page to customize the button
	 *
	 */
	public function showButtonCustomizationPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		echo $this->getButtonCustomizationPage($html,$show_header,$show_footer);
	}

	/**
	 *
	 * get the page to customize the button
	 *
	 */
	public function getButtonCustomizationPage($html='',$show_header=TRUE,$show_footer=TRUE){
		//if visual settings are submitted
		if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
		!empty($_POST['syc-visual-form'])) {

			//set the button declaration
			$this->setConfigValue("button_type", $_POST['button_type']);

			//set the button skin
			$this->setConfigValue("button_skin", $_POST['button_skin']);

			//set the button position
			$this->setConfigValue("button_position", $_POST['button_position']);

			//set the button html
			$this->setConfigValue("button_html", urldecode($_POST['button_html']));

			//set the show
			$this->setConfigValue("hide_on_product", empty($_POST['show_on_product']));

			//set the show'
			$this->setConfigValue("hide_on_checkout", empty($_POST['show_on_checkout']));

			if($_FILES["button-img"]["name"]!='') {

				$target_path = dirname(__FILE__). "/img/";

				$target_path = $target_path . 'button-img.png';

				if(file_exists($target_path)) unlink($target_path);
				
				list($width, $height, $type, $attr) = getimagesize($_FILES['button-img']['tmp_name']);
				
				if (move_uploaded_file($_FILES['button-img']['tmp_name'], $target_path))
				{
					//set the button img
					$this->setConfigValue("btn-img", $this->createUrl($target_path));
					$this->setConfigValue("btn-img-width", $width);
					$this->setConfigValue("btn-img-height", $height);
				}
			}

			if($_FILES["button-img-hover"]["name"]!='') {
				$target_path = dirname(__FILE__). "/img/";

				$target_path = $target_path . 'btn-img-hover.png';

				if(file_exists($target_path)) unlink($target_path);
				
				list($width, $height, $type, $attr) = getimagesize($_FILES['button-img-hover']['tmp_name']);

				if(move_uploaded_file($_FILES['button-img-hover']['tmp_name'], $target_path))
				{
					//set the show'
					$this->setConfigValue("btn-img-h", $this->createUrl($target_path));
					$this->setConfigValue("btn-img-h-width", $width);
					$this->setConfigValue("btn-img-h-height", $height);
				}
			}

			$status_message = 'Button settings successfully updated.';
		}

		$current_button_type = $this->getConfigValue("button_type");
		$current_skin = $this->getConfigValue("button_skin");
		$current_position = $this->getConfigValue("button_position");
		$show_on_checkout = !$this->getConfigValue("hide_on_checkout");
		$show_on_product = !$this->getConfigValue("hide_on_product");

		$button_html = $this->getConfigValue("button_html");
		$button_img = $this->getConfigValue("btn-img");
		$button_img_hover = $this->getConfigValue("btn-img-h");

		//render the view
		ob_start();
		include(dirname(__FILE__) . '/views/button-settings-page.php');
		return ob_get_clean();
	}

	/**
	 * showDocumentation
	 * @param null
	 * @return boolean
	 */
	public function showDocumentationPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		echo $this->getDocumentationPage($html,$show_header,$show_footer);
	}

	/**
	 * get the documentation page
	 * @param null
	 * @return boolean
	 */
	public function getDocumentationPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;
			
		$action_url = $this->getButtonCallbackURL();

		//render the view
		ob_start();
		include(dirname(__FILE__) . '/views/documentation.php');
		return ob_get_clean();
	}

	/*
	 *
	 * Called when a new coupon is generated
	 *
	 */

	public function couponCallback() {

		try {
			/*             * ********* Check input parameters ******************************* */
			if (!isset($_POST['token'], $_POST['coupon_code'], $_POST['coupon_value'], $_POST['coupon_type'])) {
				throw new Exception('At least one of the parameters is missing. Received: ' . print_r($_POST, true));
			}

			//make sure the coupon is valid
			$this->assertCouponIsValid($_POST['token'], $_POST['coupon_code'], $_POST['coupon_value'], $_POST['coupon_type']);

			//save the coupon
			$this->saveCoupon($_POST['token'], $_POST['coupon_code'], $_POST['coupon_value'], $_POST['coupon_type']);

			//check if the coupon is intended to be applied to the current cart
			if (empty($_POST['save_only'])) {
				$this->applyCoupon($_POST['coupon_code']);
			}
		} catch (Exception $e) {

			header("HTTP/1.0 403");
			echo $e->getMessage();
		}
	}

	/**
	 * Save the coupon
	 * @param null
	 * @return boolean
	 */
	protected function saveCoupon($token, $coupon_code, $coupon_value, $coupon_type) {

		//add the coupon id in shareyourcart coupons table
		$data = array(
            'token' => $token,
            'coupon_id' => $coupon_code,
		);

		$this->insertRow($this->getTableName('shareyourcart_coupons'), $data);
	}

	/**
	 *
	 * Based on the token, get the session_id
	 *
	 */
	protected function getSessionId($token) {

		$result = $this->getRow("SELECT session_id FROM " . $this->getTableName('shareyourcart_tokens') . " WHERE token='$token'");

		return isset($result) ? $result['session_id'] : null;
	}

	/**
	 *
	 * Abstract createTable
	 * @param table
	 * @param array columns
	 * @param string @option
	 */
	protected function createTable($tableName, $columns, $primaryKey, $options=NULL) {

		$sql = "CREATE TABLE IF NOT EXISTS $tableName (\n ";

		foreach ($columns as $name => $type) {

			$sql .= "$name $type";

			if ($name == $primaryKey) {
				$sql .= " NOT NULL AUTO_INCREMENT";
			}

			$sql .= ",\n ";
		}

		$sql .= "PRIMARY KEY ($primaryKey));";

		$this->executeNonQuery($sql);
	}

	/**
	 * Drop the specified table
	 *
	 * @param string tableName
	 */
	protected function dropTable($tableName) {

		$this->executeNonQuery("DROP TABLE $tableName");
	}

	/*
	 *
	 * Call to see if the function was called once, or not
	 *
	 */

	protected static function isFirstCall($functionName) {

		if (array_key_exists($functionName, self::$_SINGLE_FUNCTIONS_CALLS))
		return false;

		self::$_SINGLE_FUNCTIONS_CALLS[$functionName] = true;
		return true;
	}
}

/**
 * htmlIndent
 * @param string $src
 * @return string
 */
function htmlIndent($src) {

	//replace all leading spaces with &nbsp;
	//Attention: this will render wrong html if you split a tag on more lines!
	return preg_replace_callback('/(^|\n)( +)/', create_function('$match', 'return str_repeat("&nbsp;", strlen($match[0]));'
	), $src);
}

/**
 * rel2Abs
 * @param string $src
 * @return string
 */
function rel2Abs($rel, $base) {

	/* return if already absolute URL */
	if (parse_url($rel, PHP_URL_SCHEME) != '')
	return $rel;

	/* queries and anchors */
	if ($rel[0] == '#' || $rel[0] == '?')
	return $base . $rel;

	/* parse base URL and convert to local variables:
	 $scheme, $host, $path */
	extract(parse_url($base));

	/* remove non-directory element from path */
	$path = preg_replace('#/[^/]*$#', '', @$path);

	/* destroy path if relative url points to root */
	if ($rel[0] == '/')
	$path = '';

	/* dirty absolute URL */
	$abs = "$host$path/$rel";

	/* replace '//' or '/./' or '/foo/../' with '/' */
	$re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
	for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {

	}

	/* absolute URL is ready! */
	return $scheme . '://' . $abs;
}

function relativepath($from, $to, $ps = '/' ,$ds = DIRECTORY_SEPARATOR)
{	
	$arFrom = explode($ds, rtrim($from, $ds));
	$arTo = explode($ds, rtrim($to, $ds));
	
	while(count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0]))
	{
		array_shift($arFrom);
		array_shift($arTo);
	}
	return str_pad("", count($arFrom) * 3, '..'.$ps).implode($ps, $arTo);
}

} //END IF
?>