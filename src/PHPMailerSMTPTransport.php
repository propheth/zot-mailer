<?php
namespace zot\mailer\transport;

require __DIR__.'/ext/PHPMailer/src/Exception.php';
require __DIR__.'/ext/PHPMailer/src/PHPMailer.php';
require __DIR__.'/ext/PHPMailer/src/SMTP.php';

use zot\mailer\MailTransport;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PHPMailerSMTPTransport implements MailTransport {
	private $timeoutSecs = 60; // null value will use default_socket_timeout, negative means infinite.
	private $sock;
	private $host;
	private $port;
	private $startTlsOn;
	private $username;
	private $password;
	private $mailer;

	public function __construct($host, $port, $username=null, $password=null) {
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
	}

    public function send($mail) {
		$mails = is_array($mail) ? $mail : array($mail);

		//Create an instance; passing `true` enables exceptions
		$mailer = new PHPMailer(true);	

		try {
			//Server settings
			$mailer->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;                      //Enable verbose debug output
			$mailer->isSMTP();                                            //Send using SMTP
			$mailer->Host       = $this->host;                     //Set the SMTP server to send through
			$mailer->SMTPAuth   = true;                                   //Enable SMTP authentication
			$mailer->Username   = $this->username;                     //SMTP username
			$mailer->Password   = $this->password;                     //SMTP password
			$mailer->Port       = $this->port;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

			if($this->port === 465) {
				$mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;		//Enable implicit TLS encryption
			}
			else if($this->port === 587) {
				$mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			}

			foreach($mails as $mail1) {
				//Recipients
				$mailer->setFrom($mail1->fromEmail);
				$mailer->addAddress($mail1->toEmail);     //Add a recipient
				//$mailer->addReplyTo('info@example.com', 'Information');
				//$mailer->addCC('cc@example.com');
				//$mailer->addBCC('bcc@example.com');
				
				//Attachments
				/*
				$mailer->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
				$mailer->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
*/

				//Content
				$mailer->isHTML(true);                                  //Set email format to HTML
				$mailer->Subject = $mail1->subject;
				$mailer->Body    = $mail1->bodyHtml;
				$mailer->AltBody = $mail1->bodyPlain;
				$mailer->send();
			}
			
		} catch (\PHPMailer\PHPMailer\Exception $ex) {
			error_log("Message could not be sent. Mailer Error: {$mailer->ErrorInfo}");
		}
	}
	
}

?>