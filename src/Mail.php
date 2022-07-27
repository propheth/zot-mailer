<?php
namespace zot\mailer;

use InvalidArgumentException;

require_once(dirname(__FILE__).'/MultipartBuilder.php');

class Mail {
	public $from;
	public $replyTo;
	public $to;
	public $cc;
	public $bcc;

	public $fromEmail;
	public $replyToEmail;
	public $toEmail;
	public $ccEmail;
	public $bccEmail;

	public $subject;
	public $bodyPlain;
	public $bodyHtml;
	public $attachments;

	public function __construct($to, $subject, $bodyPlain, $bodyHtml=null) {
		if(($toEmail = self::parseEmail($to)) === false) { 
			throw new InvalidArgumentException("Invalid address To: $to"); }

		$this->to = $to;
		$this->toEmail = $toEmail;
		$this->subject = $subject;
		$this->bodyPlain = $bodyPlain;
		$this->bodyHtml = $bodyHtml;
		$this->attachments = array();
	}

	public function setTo($to) {
		if(($email = self::parseEmail($to)) === false) { 
			throw new InvalidArgumentException("Invalid address To: $to"); }
		$this->to = $to;
		$this->toEmail = $email;
		return $this;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
	}

	public function setFrom($from) {
		if(($email = self::parseEmail($from)) === false) { 
			throw new InvalidArgumentException("Invalid address From: $from"); }
		$this->from = $from;
		$this->fromEmail = $email;
		return $this;
	}

	public function setReplyTo($replyTo) {
		if(($email = self::parseEmail($replyTo)) === false) { 
			throw new InvalidArgumentException("Invalid address Reply-To: $replyTo"); }
		$this->replyTo = $replyTo;
		$this->replyTo = $email;
		return $this;
	}

	public function setCc($cc) {
		if(($email = self::parseEmail($cc)) === false) { 
			throw new InvalidArgumentException("Invalid address Cc: $cc"); }
		$this->cc = $cc;
		$this->ccEmail = $email;
		return $this;
	}

	public function setBcc($bcc) {
		if(($email = self::parseEmail($bcc)) === false) { 
			throw new InvalidArgumentException("Invalid address Bcc: $bcc"); }
		$this->bcc = $bcc;
		$this->bccEmail = $email;
		return $this;
	}

	public function setBodyPlain($bodyPlain) {
		$this->bodyPlain = $bodyPlain;
	}

	public function setBodyHtml($bodyHtml) {
		$this->bodyHtml = $bodyHtml;
	}

	public function attach($filePath, $contentType, $fileName=null) {
		if(!is_readable($filePath)) {
			throw new \Exception("File $filePath not found!"); }

		$this->attachments[] = array(
			"filePath" => $filePath,
			"contentType" => $contentType,
			"fileName" => $fileName
		);

		return $this;
	}

	public static function parseEmail($mailbox) {
		$invalidChars = '\\s<>@,|()!#$%:;"`\\[\\]\\\\';
		$patterns = array(
			'/([^<>,]*?)\\s+<([^'.$invalidChars.']+@[^'.$invalidChars.']+?)>/',
			'/^([^'.$invalidChars.']+@[^'.$invalidChars.']+)$/'
		);
		
		if(preg_match($patterns[0], $mailbox, $matches) > 0) {
			return $matches[2];
		}
		else if(preg_match($patterns[1], $mailbox, $matches) > 0) {
			return $matches[1];
		}

		return false;
	}

	public function writeMultipart($outStream) {
		$multipart = MultipartBuilder::newMixed();
		
		$alternative = MultipartBuilder::newAlternative()
			->addPart(MultipartBuilder::newBodyPart("text/plain; charset=utf-8", $this->bodyPlain));

		if($this->bodyHtml) {
			$alternative->addPart(MultipartBuilder::newBodyPart("text/html; charset=utf-8", $this->bodyHtml));
		}

		$multipart->addPart($alternative);

		if($this->from) {
			$multipart->addHeader("From", $this->from);
			if($this->replyTo) {
				$multipart->addHeader("Reply-To", $this->replyTo);
			}
			else {
				$multipart->addHeader("Reply-To", $this->from);
			}
		}

		if($this->to) {
			$multipart->addHeader("To", $this->to); }

		if($this->cc) {
			$multipart->addHeader("Cc", $this->cc); }

		if($this->bcc) {
			$multipart->addHeader("Bcc", $this->bcc); }

		if($this->subject) {
			$multipart->addHeader("Subject", $this->subject, MultipartBuilder::CHARSET_UTF8); }

		foreach($this->attachments as $attachment) {
			$multipart->addPart(MultipartBuilder::newFilePart(
				$attachment['contentType'], 
				MultipartBuilder::CONTENT_DISPOSITION_ATTACHMENT, 
				$attachment["filePath"], 
				$attachment['fileName'] ?? basename($attachment['filePath'])));
		}

		$multipart->build($outStream);
	}
}

/*
var_dump(Mail::parseEmail('name1@domain.local'));
var_dump(Mail::parseEmail('name1@domain. local'));
var_dump(Mail::parseEmail('name1@domain.[local]'));
var_dump(Mail::parseEmail('name1@domain\.local'));
var_dump(Mail::parseEmail('name1@domain(.local)'));
*/

?>