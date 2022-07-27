<?php
namespace zot\mailer;

require_once(dirname(__FILE__).'/MailFunction.php');
require_once(dirname(__FILE__).'/SmtpTransport.php');

use zot\mailer\transports\MailFunction;
use zot\mailer\transports\SmtpTransport;

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