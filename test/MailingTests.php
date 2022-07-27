<?php
namespace zot\mailer\test;

use zot\mailer\transports\SmtpTransport;
use zot\mailer\Mail;
use zot\test\Test;

require_once(__DIR__.'/Test.php');
require_once(__DIR__.'/../src/Mail.php');
require_once(__DIR__.'/../src/SmtpTransport.php');

class MailingTests extends Test {
	private $plainMail;
	private $mailWithAttachment;
	private $smtpTransport;

	public function __construct() {
		$this->plainMail = (new Mail('dev1@localhost.net', 'test smtp', 'hello world'))
			->setFrom("test@localhost");

		$this->mailWithAttachment = (new Mail('dev1@localhost.net', 'test smtp', 'hello world'))
			->setFrom('test@localhost')
			->attach(__FILE__, "text/plain", 'test.txt');

		$this->mailWithAttachments = (new Mail('dev1@localhost.net', 'test smtp', 'hello world'))
			->setFrom('test@localhost')
			->attach(__FILE__, "text/plain", 'test1.txt')
			->attach(__FILE__, "text/plain", 'test2.txt');

		$this->smtpTransport = new SmtpTransport('127.0.0.1', 25, true);
	}

	public function testPlainMail() {
		try {
			$this->smtpTransport->send($this->plainMail);
			$this->assertTrue(true);
		}
		catch(\ErrorException $ex) {
			$this->assertTrue(false, $ex->getMessage());
		}
	}

	public function testPlainMailWithAttachment() {
		try {
			$this->smtpTransport->send($this->mailWithAttachment);
			$this->assertTrue(true);
		}
		catch(\ErrorException $ex) {
			$this->assertTrue(false, $ex->getMessage());
		}
	}

	public function testPlainMailWithAttachments() {
		try {
			$this->smtpTransport->send($this->mailWithAttachments);
			$this->assertTrue(true);
		}
		catch(\ErrorException $ex) {
			$this->assertTrue(false, $ex->getMessage());
		}
	}
}

(new MailingTests())->testPlainMail();
(new MailingTests())->testPlainMailWithAttachment();
(new MailingTests())->testPlainMailWithAttachments();

?>