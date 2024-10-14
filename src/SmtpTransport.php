<?php
namespace zot\mailer\transport;

//require_once(dirname(__FILE__).'/../../zot/core/lib/Http.php');

use Exception;
use zot\mailer\MailTransport;

class SmtpTransport implements MailTransport {
	const DEBUG = 0;
	
	const EXT_STARTTLS = 'STARTTLS';
	const EXT_AUTH = 'AUTH';

	const AUTH_PLAIN = 'PLAIN';
	const AUTH_LOGIN = 'LOGIN';

	const SUPPORTED_AUTH_PROTOCOLS = array('PLAIN', 'LOGIN');
	const SUPPORTED_EXTENSIONS = array('STARTTLS', 'AUTH');

	private $timeoutSecs = 60; // null value will use default_socket_timeout, negative means infinite.
	private $sock;
	private $host;
	private $port;
	private $startTlsOn;
	private $username;
	private $password;

	public function __construct($host, $port, $username=null, $password=null) {
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
	}

    public function send($mail) { 
		$mails = is_array($mail) ? $mail : array($mail);

		$this->smtpStart($this->host, $this->port);
		if($this->extensionSupported(SmtpTransport::EXT_AUTH)) {
			$this->smtpAuth(); }
		
		foreach($mails as $mail1) {
			$this->smtpMailFrom($mail1->fromEmail);
			$this->smtpRcptTo($mail1->toEmail);
			$this->smtpData($mail1);
		}

		$this->smtpQuit();	
		$this->smtpDisconnect();
	}
	
	// Default to most secure available extensions
	private function smtpStart($host, $port, $skipStartTls=null) {
		$this->smtpConnect($host, $port);

		if($skipStartTls) {
			$this->smtpHelo();
		}
		else {
			if($this->extensionSupported(SmtpTransport::EXT_STARTTLS)) {
				$this->smtpStartTls();
				$this->smtpEhlo(); // Do an EHLO after STARTTLS to prevent error.
			}
			else {
				// Fallback no STARTTLS
				$this->smtpHelo();
			}
		}
	}

	private function smtpConnect($host, $port) {
		if($this->smtpConnected()) { 
			$this->smtpDisconnect(); }
		$this->sock = stream_socket_client("tcp://$host:$port", $errCode, $errMessage, $this->timeoutSecs);
		$data = $this->readServer(220);
	}

	private function smtpConnected() {
		return $this->sock !== null && is_resource($this->sock);
	}

	private function smtpDisconnect() {
		if($this->sock !== null) {
			if(is_resource($this->sock)) {
				fclose($this->sock); }
			$this->sock = null;
		}
	}

	private function smtpHelo() {
		$data = $this->sendClient("HELO ".gethostname(), 250);
	}

	private function smtpEof($data) {
		return $data !== false && 
			strlen($data) >= 4 && 
			preg_match('/^[0-9][0-9][0-9](?: |\r\n)/', $data) === 1;
			// Officially 3 digit response code<SP> but some incompliant servers 3 digit response code<CRLF>
	}

	// https://datatracker.ietf.org/doc/html/rfc5321#section-2.3.7
	private function isReplyCode($replyLine, $replyCode) {
		return $replyLine !== false && 
			strlen($replyLine) >= 4 && 
			substr($replyLine, 0, 3) == $replyCode;
	}

	private function smtpEhlo() {
		return $this->sendClient("EHLO ".gethostname(), 250);
	}

	private function smtpStartTls() {
		if(!$this->extensionSupported(SmtpTransport::EXT_STARTTLS)) {
			throw new UnsupportedException(SmtpTransport::EXT_STARTTLS); }

		$data = $this->sendClient("STARTTLS", 220);
		if(stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT) !== true) {
			throw new Exception('STARTTLS failed'); }
		$this->startTlsOn = true;
	}

	private function availableAuthProtocols() {
		if(($data = $this->hasLine($this->smtpEhlo(), SmtpTransport::EXT_AUTH, 250)) === false) {
			throw new UnsupportedException(SmtpTransport::EXT_AUTH); }
		$authProtocols = explode(' ', $data);
		return $authProtocols;
	}

	private function authProtocolAvailable($authProtocol) {
		$availableAuthProtocols = $this->availableAuthProtocols();
		return in_array($authProtocol, $availableAuthProtocols);
	}

	private function smtpAuthPlain($username, $password) {
		$this->sendClient('AUTH PLAIN', 334);
		$data = $this->sendClient(base64_encode("\0".$username."\0".$password), 235);
	}

	private function smtpAuthLogin($username, $password) {
		$this->sendClient('AUTH LOGIN', 334);
		$data = $this->sendClient(base64_encode($username), 334);
		$data = $this->sendClient(base64_encode($password), 235);
	}

	private function smtpAuth() {
		if(!$this->extensionSupported(SmtpTransport::EXT_AUTH)) {
			throw new UnsupportedException(SmtpTransport::EXT_AUTH); }

		if($this->username === null || 
			$this->password === null) { return false; }

		$availableAuthProtocols = $this->availableAuthProtocols();
		foreach(self::SUPPORTED_AUTH_PROTOCOLS as $supportedAuthProtocol) {
			if(in_array($supportedAuthProtocol, $availableAuthProtocols)) {
				switch($supportedAuthProtocol) {
					case SmtpTransport::AUTH_PLAIN:
						$this->smtpAuthPlain($this->username, $this->password);
						break 2;

					case SmtpTransport::AUTH_LOGIN:
						$this->smtpAuthLogin($this->username, $this->password);
						break 2;

					default:
						throw new \Exception("$supportedAuthProtocol auth not supported yet by the library.");
						break 2;
				}
			}
		}
	}
/*
	private function smtpAuthXoauth2($username, $password, $clientId, $clientSecret) {
		$authUrl = "https://accounts.google.com/o/oauth2/auth";
		//$authUrl = "https://accounts.google.com/o/oauth2/token";

		$resp = \Http::request2($authUrl, 'POST', "Content-Type: application/x-www-form-urlencoded", "response_type=code&client_id=780151539280-e9d77hhpab3c6u6152b7f7c032070liv.apps.googleusercontent.com&scope=https://www.googleapis.com/auth/gmail.send&redirect_uri=http://localhost");
		var_dump($resp);die;

		$basicUserPass = base64_encode($clientId.':'.$clientSecret);
		$resp = \Http::request2($authUrl, 'POST', "Authorization: Basic $basicUserPass\r\nContent-Length: 18", "response_type=code");
		var_dump($resp);die;
		
		$supportedAuths = $this->availableAuthProtocols();
		if(in_array('XOAUTH2', $supportedAuths)) {
			$this->sendClient('AUTH XOAUTH2', 334);
			$data = $this->sendClient(base64_encode("user=".$username."\cAauth=Bearer ".$password."\cA\cA"), 235);
		}
		else {
			throw new Exception('AUTH XOAUTH2 not supported');
		}
	}
*/
	private function smtpMailFrom($fromEmailAddr) {
		$data = $this->sendClient("MAIL FROM:<$fromEmailAddr>", 250);
	}

	private function smtpRcptTo($toEmailAddr) {
		$data = $this->sendClient("RCPT TO:<$toEmailAddr>", 250);
	}

	// https://datatracker.ietf.org/doc/html/rfc5321#section-4.5.2
	private function smtpData($mail) {
		$data = $this->sendClient("DATA", 354);
		/*
		$maxMemory = 5*1024*1024; // 5MB
		$tempStream = fopen("php://temp/maxmemory:$maxMemory", 'r+');
		$mail->writeMultipart($tempStream);
		rewind($tempStream);
		*/
		//$data = stream_get_contents($tempStream);
		//$mail->writeMultipart($this->sock);
/*
$data = <<<EOS
Date: Fri, 11 Oct 2024 15:39:30 +0200
To: dev@prophetiq.com
From: commerceos@interniaga.net
Subject: Forgot password
Message-ID: <v21sl81Igp0efDtBxJDlFtqdUhq1Fdu78dlBtvFRcE@commerceos.local>

Hello Alice.
This is a test message with 5 header fields and 4 lines in the message body.
Your friend,
Bob
EOS;
*/
/*
		// Data is folded in the multipart message as there could be many nested messages.
		$data = explode("\r\n", $data);
		foreach($data as $line) {
			fwrite($this->sock, $line."\r\n"); }

		stream_copy_to_stream($tempStream, $this->sock);
		*/
		$mail->writeMultipart($this->sock);
		fwrite($this->sock, "\r\n.\r\n");
		$data = $this->readServer(250);
	}

	private function smtpQuit() {
		$data = $this->sendClient("QUIT", 221);
	}

	private function sendClient($cmd, $okCode) {
		$data = null;
		
		if($this->smtpConnected()) {
			fwrite($this->sock, $cmd."\r\n");
			$this->printDebug($cmd);
			$data = $this->readServer($okCode);
		}
		else {
			throw new \ErrorException("SMTP not connected.");
		}

		return $data;
	}

	private function readServer($okCode) {
		$data = '';
		$err = false;
		$errCode = null;
		$errLine = null;
		
		if(!$this->smtpConnected()) {
			throw new \ErrorException("SMTP not connected."); }

		do {
			$line = fgets($this->sock);
			if($line === false) { 
				$this->smtpDisconnect();
				throw new \ErrorException("Sock error");
			}

			if(!$this->isReplyCode($line, $okCode)) {
				$err = true;
				$errCode = substr($line, 0, 3);
				$errLine = $line;
			}
			
			$data .= $line;
		} while(!$this->smtpEof($line));

		if($err) {
			throw new \Exception($errLine, $errCode); }

		$this->printDebug($data);

		return $data;
	}

	private function extensionSupported($ext) {
		return $this->hasLine($this->smtpEhlo(), $ext, 250) !== false;
	}

	private function hasLine($reply, $cmd, $code) {
		$pattern = "/^{$code}[ -]$cmd(.*?)$/m";
		//$this->printDebug($pattern);
		if(preg_match($pattern, $reply, $matches)) {
			$line = $matches[0];
			$line = trim($matches[1]);
			return $line;
		}
		return false;
	}

	private function printDebug($text) {
		if(self::DEBUG) { print $text.PHP_EOL; }
	}
}

class UnsupportedException extends \Exception {
	public function __construct($extension) {
		parent::__construct("{$extension} not supported", crc32($extension));
	}
}

?>