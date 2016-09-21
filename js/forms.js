
//allows us to insert custom-styled recaptcha elements
var RecaptchaOptions = {
	theme: 'custom'
};

//mark required fields - be sure to have class="required" in the labels
$(document).ready(function(){
	$("label.required").append(" *");
});
