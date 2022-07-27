<?php
namespace zot\mailer\transports;

require_once(dirname(__FILE__).'/MailTransport.php');

use zot\mailer\MailTransport;

class SmtpTransport implements MailTransport {
	const DEBUG = 0;
	const SUPPORTED_AUTH_PROTOCOLS = array('PLAIN', 'LOGIN');
	private $timeoutSecs = 60; // null value will use default_socket_timeout, negative means infinite.
	private $sock;
	private $host;
	private $port;
	private $startTlsOn;

	public function __construct($host, $port, $username=null, $password=null) {
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
	}

    public function send($mail) { 
		$mails = is_array($mail) ? $mail : array($mail);

		$this->smtpStart($this->host, $this->port, true);

		$authSupported = null;
		try {
			$this->extensionSupported('AUTH');
			$authSupported = true;
		}
		catch(\Exception $ex) {
			$authSupported = false;
		}
		
		if($authSupported) {
			$this->smtpAuth();
		}
		
		foreach($mails as $mail1) {
			$this->smtpMailFrom($mail1->fromEmail);
			$this->smtpRcptTo($mail1->toEmail);
			$this->smtpData($mail1);
		}

		$this->smtpQuit();	
		$this->smtpDisconnect();
	}
	
	private function smtpStart($host, $port, $autoStartTls=null) {
		$this->smtpConnect($host, $port);

		if($autoStartTls) {
			try {
				$this->extensionSupported('STARTTLS');
				$this->smtpStartTls();
				$this->smtpEhlo(); // Do an EHLO after STARTTLS to prevent error.
			}
			catch(\Exception $ex) {

			}
		}

		if(!$this->startTlsOn) {
			$this->smtpHelo();
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
		return strlen($data) >= 4 && preg_match('/^[0-9][0-9][0-9] /', $data) === 1;
	}

	private function smtpEhlo() {
		return $this->sendClient("EHLO ".gethostname(), 250);
	}

	private function smtpStartTls() {
		$this->extensionSupported('STARTTLS');
		$data = $this->sendClient("STARTTLS", 220);
		if(stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT) === true) {
			$this->startTlsOn = true;
		}
	}

	private function availableAuthProtocols() {
		$data = $this->extensionSupported('AUTH');
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
		if($this->username === null || 
			$this->password === null) { return false; }

		$availableAuthProtocols = $this->availableAuthProtocols();
		foreach(self::SUPPORTED_AUTH_PROTOCOLS as $supportedAuthProtocol) {
			if(in_array($supportedAuthProtocol, $availableAuthProtocols)) {
				switch($supportedAuthProtocol) {
					case 'PLAIN':
						$this->smtpAuthPlain($this->username, $this->password);
						break 2;

					case 'LOGIN':
						$this->smtpAuthLogin($this->username, $this->password);
						break 2;

					default:
						throw new \Exception("$supportedAuthProtocol auth not supported yet by the library.");
						break 2;
				}
			}
		}
	}

	private function smtpMailFrom($fromEmailAddr) {
		$data = $this->sendClient("MAIL FROM:<$fromEmailAddr>", 250);
	}

	private function smtpRcptTo($toEmailAddr) {
		$data = $this->sendClient("RCPT TO:<$toEmailAddr>", 250);
	}

	private function smtpData($mail) {
		$data = $this->sendClient("DATA", 354);
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
		
		if($this->smtpConnected()) {
			do {
				$line = fgets($this->sock);
				if(strlen($line) >= 4 && 
						($smtpCode = substr($line, 0, 3)) && 
						(int)$smtpCode === $okCode) {
					
				}
				else {
					$err = true;
					$errCode = $smtpCode;
					$errLine = $line;
				}
				
				$data .= $line;
			} while(!$this->smtpEof($line));
		}
		else {
			throw new \ErrorException("SMTP not connected.");
		}

		if($err) {
			throw new \Exception($errLine, $errCode);
		}

		$this->printDebug($data);

		return $data;
	}

	private function extensionSupported($ext) {
		$data = $this->smtpEhlo();
		if(($data = $this->hasLine($data, $ext, 250)) !== false) {
			return $data;
		}
		throw new \Exception("$ext not supported");
	}

	private function hasLine($serverData, $cmd, $code) {
		$pattern = "/^{$code}[ -]$cmd(.*?)$/m";
		//$this->printDebug($pattern);
		if(preg_match($pattern, $serverData, $matches)) {
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

?>