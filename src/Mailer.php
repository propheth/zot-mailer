<?php
namespace zot\mailer;

use zot\mailer\transport\SmtpTransport;
use zot\mailer\transport\MailFunction;

class Mailer {
	private $mailTransport;

	static public function smtp($host, $port, $username, $password) {
		return new Mailer(new SmtpTransport($host, $port, $username, $password));
	}

	static public function phpMail() {
		return new Mailer(new MailFunction());
	}

	public function __construct(MailTransport $mailTransport) {
		$this->mailTransport = $mailTransport;
	}

	public function send($mail) {
		$this->mailTransport->send($mail);
	}
}

?>