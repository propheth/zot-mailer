<?php

/*
(new SmtpTransport('127.0.0.1', 25, true))
	->send((new \PostalService\Mail('dev1@localhost.net', 'test smtp', 'hello world'))
		->setFrom('test@localhost')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt'));
*/
/*
(new SmtpTransport('smtp.gmail.com', 587, true, true))
	->send((new \PostalService\Mail('test@interniaga.net', 'test smtp from gmail', 'hello world'))
		->setFrom('test@interniaga.net')
		//->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt')
);
*/
/*
$transport = new SmtpTransport('mail.interniaga.com', 587, 'test@interniaga.net', 'mqddUyES5b7n');
$transport
	->send((new \PostalService\Mail('test@interniaga.net', 'test smtp from gmail', 'hello world'))
		->setFrom('test@interniaga.net')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt'));
$transport
	->send((new \PostalService\Mail('test@interniaga.net', 'test smtp from gmail', 'hello world'))
		->setFrom('test@interniaga.net')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt'));
*/
/*
$transport = new SmtpTransport('mail.interniaga.net', 587, 'test@interniaga.net', 'mqddUyES5b7n');
$transport
	->send([
		(new \PostalService\Mail('test@interniaga.net', 'test smtp from gmail', 'hello world'))
		->setFrom('test@interniaga.net')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt'),
		(new \PostalService\Mail('test@interniaga.net', 'test smtp from gmail', 'hello world'))
		->setFrom('test@interniaga.net')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt')
	]);
*/

?>