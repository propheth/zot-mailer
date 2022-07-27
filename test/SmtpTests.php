<?php

/*
(new SmtpTransport('127.0.0.1', 25, true))
	->send((new \PostalService\Mail('dev1@localhost.net', 'test smtp', 'hello world'))
		->setFrom('test@localhost')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt'));
*/
/*
(new SmtpTransport('smtp.gmail.com', 587, true, true))
	->send((new \PostalService\Mail('mahendra.maimaibali@dispostable.com', 'test smtp from gmail', 'hello world'))
		->setFrom('mahendra.maimaibali@gmail.com')
		//->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt')
);
*/
/*
$transport = new SmtpTransport('mail.getokdate.com', 587, 'hello@getokdate.com', 'mqddUyES5b7n');
$transport
	->send((new \PostalService\Mail('navyn@prophetiq.com', 'test smtp from gmail', 'hello world'))
		->setFrom('hello@getokdate.com')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt'));
$transport
	->send((new \PostalService\Mail('support@myphpauction.com', 'test smtp from gmail', 'hello world'))
		->setFrom('hello@getokdate.com')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt'));
*/
/*
$transport = new SmtpTransport('mail.getokdate.com', 587, 'hello@getokdate.com', 'mqddUyES5b7n');
$transport
	->send([
		(new \PostalService\Mail('navyn@prophetiq.com', 'test smtp from gmail', 'hello world'))
		->setFrom('hello@getokdate.com')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt'),
		(new \PostalService\Mail('support@myphpauction.com', 'test smtp from gmail', 'hello world'))
		->setFrom('hello@getokdate.com')
		->attach("e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", "text/plain", 'text.txt')
	]);
*/

?>