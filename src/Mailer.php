<?php
namespace zot\mailer;

use zot\mailer\transport\SmtpTransport;
use zot\mailer\transport\MailFunction;
use zot\mailer\transport\PHPMailerSMTPTransport;

class Mailer {
	private $mailTransport;

	static public function smtp($host, $port, $username, $password) {
		return new Mailer(new SmtpTransport($host, $port, $username, $password));
	}

	static public function phpMail() {
		return new Mailer(new MailFunction());
	}

	static public function smtpPHPMailer($host, $port, $username, $password) {
		return new Mailer(new PHPMailerSMTPTransport($host, $port, $username, $password));
	}

	public function __construct(MailTransport $mailTransport) {
		$this->mailTransport = $mailTransport;
	}

	public function send($mail) {
		$this->mailTransport->send($mail);
	}
}

?>