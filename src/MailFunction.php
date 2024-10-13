<?php
namespace zot\mailer\transport;

use zot\mailer\MailTransport;

require_once(dirname(__FILE__).'/MailTransport.php');

class MailFunction implements MailTransport {
	
	public function send($mail) {
		$fh = fopen('php://memory', 'w+');
		$mail->writeMultipart($fh);

		rewind($fh);
		$message = stream_get_contents($fh);
		$data = explode("\r\n\r\n", $message, 2);

		//error_log($message);
		
		if(!@mail($mail->to, $mail->subject, $data[1], $data[0]."\r\n")) {
			$err = error_get_last();
			throw new \Exception(isset($err['message']) ? $err['message'] : 'Unknown mail() error occured.');
		}
	}
}

?>