<?php
require 'phpmailer/class.phpmailer.php';
class CGmail extends PHPMailer
{
	public function __construct($username, $password)
	{
		$this->IsSMTP();
		$this->SMTPAuth   = true;                  // enable SMTP authentication
		$this->SMTPSecure = "ssl";                 // sets the prefix to the servier
		$this->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
        $this->Port       = 465;                  // set the SMTP port for the GMAIL server
		$this->Username   = $username;  // GMAIL username
		$this->Password   = $password;            // GMAIL password
		$this->SetFrom($username, 'تخفیف صد در صد');
		$this->AddReplyTo($username,"تخفیف صد در صد");
		$this->CharSet = 'UTF-8';
		$this->WordWrap   = 80; // set word wrap
		$this->IsHTML(true); // send as HTML
	}
	
}
