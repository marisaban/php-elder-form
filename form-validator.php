<?php
class FormValidator{
	
	function FormValidator(){
		$this->resetErrorList();
		$this->setPostStatus();
	}

/****** Validation functions *************/

	/**
	 * Tests whether or not a particular form field contains a value. Returns true if a non-whitespace value
	 * exists and false otherwise.  If there is no value, an item is added to the error list.
	 * param $field		name of form field to be checked
	 * param $msg		error message to be displayed if field is not filled in
	 * returns			true if field has been filled, false otherwise
	 */
	function validateRequired($field, $msg){
		$value = $this->getValue($field);
		if (is_string($value)){
			$value = trim($value);	
		}
		if (is_array($value))
		{
			if(count($value) > 0)
			return true;
		}
		if ($value != ""){
			return true;
		}
		$this->appendError($field, $value, $msg);
		return false;
	}
	
	/**
	 * Tests a form field to see if its value (if it exists) looks like an email address. Returns true
	 * if email-like or empty, false otherwise.  If false, an item is added to the error list.
	 * param $field		name of form field to be checked
	 * param $msg		error message to be displayed if field is not filled in
	 * returns			true if value is email-like or empty, false otherwise
	 */
	function validateEmail($field, $msg){
		$value = $this->getValue($field);
		$pattern = "/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/ ";
		if(trim($value) == "" || preg_match($pattern, $value)){
			return true;
		}
		$this->appendError($field, $value, $msg);
		return false;
	}
	/**
	 * Tests a form field to see if its value (if it exists) looks like a Camosun email address. Returns true
	 * if email-like or empty, false otherwise.  If false, an item is added to the error list.
	 * param $field		name of form field to be checked
	 * param $msg		error message to be displayed if field is not filled in
	 * returns			true if value is email-like or empty, false otherwise
	 */
	function validateEmailCamosun($field, $msg){
		$value = strtolower($this->getValue($field));
		$pattern = "/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@(camosun)(\.[a-zA-Z0-9_.-]+)+/ ";
		if(trim($value) == "" || preg_match($pattern, $value)){
			return true;
		}
		$this->appendError($field, $value, $msg);
		return false;
	}
	
	function validateDateISO($field, $msg){
		$value = $this->getValue($field);
		$pattern = "#^((19|20)\d\d)[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$#";
		preg_match($pattern, $value, $match); // I DON'T UNDERSTAND WHY THIS IS RETURNING YYYY *plus* 19|20. Whatever.
		if(checkdate($match[3], $match[4], $match[1]))
		{
			return true;
		}
		$this->appendError($field, $value, $msg);
		return false;
	}
	
	/**
	 * Calls reCAPTCHA validation libraries to check correctness of a reCAPTCHA response.  Returns true
	 * if response if correct, false otherwise. If false, an item is added to the error list.
	 * param $response	name of field containing user's guess at the captcha value
	 * param $challenge name of field displaying reCAPTCHA image
	 * param $msg		error message to be displayed if reCAPTCHA does not validate
	 * returns 			true if response matches challenge, false otherwise.
	 */
	function validateRecaptcha($response, $challenge, $msg){
		$responseValue = $this->getValue($response);
		$challengeValue = $this->getValue($challenge);
		require_once($_SERVER['DOCUMENT_ROOT'].'/_include/recaptcha/recaptchalib.php');
		$privatekey = "6LdG-QkAAAAAAFjUAkms3wKIjqo1Pq7x96zX17SF";
		$resp = recaptcha_check_answer ($privatekey,
									$_SERVER["REMOTE_ADDR"],
									$challengeValue,
									$responseValue);
		if ($resp->is_valid){
			return true;
		}
		//echo $resp->error;
		$this->appendError($response, $responseValue, $msg);
		return false;
	}
	
		
	function validateNocaptcha($response, $msg){
		$captcha = $this->getValue($response);
	  require_once($_SERVER['DOCUMENT_ROOT'].'/template/lib/recaptcha/nocaptcha/recaptchalib.php');
		$secretkey = "6Lc0mwETAAAAALiFO8mv7GvFr2KwFtSqZBGvpAI8"; //6LczGdYSAAAAAGGxXdy6tZpBK35UCsKaBOQ224Ve
		// The response from reCAPTCHA
		$resp = null;
		// The error code from reCAPTCHA, if any
		$error = null;
		$reCaptcha = new ReCaptcha($secretkey);
		// Was there a reCAPTCHA response?
		if(isset($captcha)) {
			$resp = $reCaptcha->verifyResponse(
				$_SERVER["REMOTE_ADDR"],
				$captcha
			);
		 }  
//    Use these lines to process as an array instead of object
//	  $response=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$secretkey."&response=".$captcha."&remoteip=".$_SERVER['REMOTE_ADDR']);
//	  $resp = json_decode($response, true);

		if($resp->success==true){
		  return true;
		}
		$this->appendError($response,$resp->errorCodes, $msg." Error ".$resp->errorCodes);
		return false;
	}

	/**
	 * Tests a form field to see if the attached file is the allowed type, doc/image/other. Returns true
	 * if file extension is true or empty, false otherwise.  If false, an item is added to the error list.
	 * param $field		name of form field to be checked
	 * param $type		type of extension allowed in settings, null, 1, 0
	 * param $msg		error message to be displayed if field is not filled in
	 * param $errorMsg	error message to be displayed for failed extension test.
	 * returns			true if value is accepted extension or empty, false otherwise
	 */
	function validateFile($field,$type){
		//checks if there is even a file attached
		if($_FILES[$field]['error'] == 0 || $_FILES[$field]['error'] !== 4){
		//Get the uploaded file information
		$name_of_uploaded_file = basename($_FILES[$field]['name']);
		//get the file extension of the file
		$type_of_uploaded_file = substr($name_of_uploaded_file, strrpos($name_of_uploaded_file, '.') + 1);
		//Settings for file types
		if(!isset($type) || $type == 0) {$allowed_extensions = array("pdf", "doc", "docx", "rtf");}
		elseif ($type == 1) {$allowed_extensions = array("jpeg", "jpg", "png","gif");}
		else {
			$msg = "Type variablable not configured for file validation";
			$this->appendError($field, $value, $msg);
			return false;
		}
		//------ Validate the file extension -----
			$allowed_ext = false;
			for($i=0; $i<sizeof($allowed_extensions); $i++)
				{
					 if(strcasecmp($allowed_extensions[$i],$type_of_uploaded_file) == 0)
					{
					$allowed_ext = true;
					}
				}// end of for loop
	 		//failed test error report
			if(!$allowed_ext)
				{
				$msg = $field;
				$errorsMsg = "The file is not supported file type. "." Only the following file types are supported: ".implode(' , ',$allowed_extensions);
				$this->appendError($field, $value, $errorsMsg);
				return false;
				}
		}
	}// end of validatefile
	
	/**
	 * Tests a form field to see if the attached file is under max allowed size. Returns true
	 * if file size is true or empty, false otherwise.  If false, an item is added to the error list.
	 * param $field		name of form field to be checked
	 * param $size		max size file allowed to be attached, in KB
	 * param $errorMsg	error message to be displayed if file size exceeded.
	 * returns			true if size is accepted or empty, false otherwise
	 */	
	function validateFileSize($field, $size){
		$value = $this->getValue($field);
		$size_of_uploaded_file = $_FILES[$field]["size"]/1024;//size in KBs
		//Settings
		$max_allowed_file_size = "$size"; // size in KB
		//Validations
		if($size_of_uploaded_file > $max_allowed_file_size )
		{
			$msg = "size";
  			$errorMsg = "Size of file should be less than $max_allowed_file_size KB";
			$this->appendError($field, $value, $errorMsg);
			return false;
		}
	}// end of validateFileSize
	
/******* form value populating ************/

	/**
	 * populateText and repopulateText are intended to be used inside a value="" in text input
	 * or between tags of textarea. Sample usage (assuming FormValidator object $fv):
	 * <input type="text" name="city" value="<?php $fv->repopulateText('city') || $fv->populateText('Victoria'); ?>"
	 * Will display submitted value or "Victoria" into text field.  Leave off " || $fv->populateText('Victoria')"
	 * if no default value is desired.
	 * param $value		default value to be inserted into field
	 * param $field		field to draw posted value from
	 */
	function populateText($value){
		echo $value;
		return true;
	}
	
	function repopulateText($field, $index=NULL){ //added $index variable for array handling, DEFAULT = NULL
		if ($this->isPosted()){
			//echo $this->getValueClean($field);   /* replaced this line with Array handling Sept 2013 */
			if(is_array($_POST[$field]))
			{ 
				if(!empty($_POST[$field][$index]))
				{
					echo htmlspecialchars($_POST[$field][$index]);
				}
			}
			else
			{
				echo $this->getValueClean($field);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * populateCheckbox and repopulateCheckbox are intended to be used inside of a checkbox or radio
	 * input tag. Sample usage (assuming FormValidator object $fv):
	 * <input name="clienttype" type="radio" value="external" <?php $fv->repopulateCheckbox('clienttype', 'external') || $fv->populateCheckbox();  ?> />
	 * Will cause field to be checked on initial display, or if checked by user. Leave off " || $fv->populateCheckbox()"
	 * if field should not be checked on initial display.
	 * param $field			name field to draw posted value from
	 * param $testValue 	value string associated with this particular checkbox/radio button
	 */
	function populateCheckbox(){
		echo ('checked="checked"');
		return true;
	}
	
	function repopulateCheckbox($field, $testValue){
		if ( $this->isPosted()){
			$value = $this->getValue($field);		
			if (is_array($value) && in_array($testValue, $value)){
				echo ('checked="checked"');
				return true;		
			}
			if ( $value == $testValue ){
				echo ('checked="checked"');
				return true;
			}
		}
		return false;
	}
	/**
	 * populateSelect and repopulateSelect are intended to be used inside of option tags.
	 * Sample usage (assuming FormValidator object $fv):
	 *  <option value="BC" <?php $fv->repopulateSelect("province", "BC") || $fv->populateSelect(); ?> >BC</option>
     * Will cause option to be selected on initial display, or if selected by user. Leave off " || $fv->populateSelect()"
	 * if option should not be selected on ititial display.
	 * param $field			name field to draw posted value from
	 * param $testValue 	value string associated with this particular option tag
	 */
	function populateSelect(){
		echo ('selected="selected"');
		return true;
	}
	
	function repopulateSelect($field, $testValue){
		if ( $this->isPosted()){
			$value = $this->getValue($field);		
			if (is_array($value) && in_array($testValue, $value)){
				echo ('selected="selected"');
				return true;		
			}
			if ( $value == $testValue ){
				echo ('selected="selected"');
				return true;
			}
		}
		return false;
	}

/****** Form value display ***********/
	/**
	 * returns the value of the given field, sanitized for html or email display
	 * param $field		name of field to acquire value from
	 * returns			sanitized value of field, or empty string if no value exists
	 */
	function getValueClean($field){
		
		return  htmlspecialchars($this->getValue($field),ENT_QUOTES);
	}
	
	/**
	 * returns the value of the given field
	 * param $field		name of field to acquire value from
	 * returns			value of field, or empty string if form has not been posted
	 */
	function getValue($field){
		if (isset($_POST[$field])){
			return $_POST[$field];
		}
		return "";
	}	

/****** Form post status **********/
	
	/** returns true if form has been posted, false otherwise */
	function isPosted(){
		return $this->postStatus;
	}
	
	private function setPostStatus(){
		$this->postStatus = ($_SERVER['REQUEST_METHOD'] == "POST");
	}
	
/****** Error recording *********/	

	
	/** returns true if any validation errors have been found, false otherwise */
	function errorExists(){
		if (sizeof($this->errorList) > 0){
			return true;
		}
		return false;
	}
	
	/** returns errorList, an array of (field, value, message) arrays. */
	function getErrorList(){
		return $this->errorList;
	}
	
	private function resetErrorList(){
		$this->errorList = array();
	}
	
	private function appendError($errorField, $errorValue, $errorMessage){
		$this->errorList[] = array("field" => $errorField, "value" => $errorValue, "message" => $errorMessage);
	}
	
/**** Private variables **********/

	private $errorList;
	private $postStatus;

}

?>