<?php
namespace zot\mailer;

require_once(dirname(__FILE__).'/Mail.php');

interface MailTransport {
	public function send($mail);
}

?>