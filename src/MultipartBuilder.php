<?php
namespace zot\mailer;

//https://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
class MultipartBuilder {
	const MIME_VERSION_1_0 = '1.0';
	const CONTENT_TYPE_MIXED = 'multipart/mixed';
	const CONTENT_TYPE_ALTERNATIVE = 'multipart/alternative';
	const CONTENT_TRANSFER_ENCODING_BASE64 = 'base64';
	const CONTENT_TRANSFER_ENCODING_7BIT = '7bit';
	const CONTENT_TRANSFER_ENCODING_8BIT = '8bit';
	const CONTENT_TRANSFER_ENCODING_BINARY = 'binary';
	const CONTENT_DISPOSITION_INLINE = 'inline';
	const CONTENT_DISPOSITION_ATTACHMENT = 'attachment'; // requires user action to display

	const CHARSET_UTF8 = 'utf-8';
	const CRLF = "\r\n";
	const LWSP = "\t";

	// https://tools.ietf.org/html/rfc5321#section-4.5.3.1.6
	// https://www.w3.org/Protocols/rfc822/3_Lexical.html
	// 3.4.8. FOLDING LONG HEADER FIELDS
	// "Long" is commonly interpreted to mean greater than 65 or 72 characters
	// LWSP can be SPACE or HTAB
	// Not including <CRLF><LWSP>
	const MAX_LINE_LENGTH_HEADER = 70;
	// Not including <CRLF>
	const MAX_LINE_LENGTH_BODY = 76;

	private $headers;
	private $version;
	private $contentType;
	private $contentTransferEncoding;
	private $contentDisposition;
	private $boundary;
	private $body;
	private $filePath;
	private $parts;

	public function __construct($contentType=null, $version=null) {
		$this->headers = array();
		$this->parts = array();
		$this->setVersion($version);
		$this->setContentType($contentType);
	}

	static public function newMixed() {
		$message = new MultipartBuilder(self::CONTENT_TYPE_MIXED, self::MIME_VERSION_1_0);
		$message->setBoundary(md5(date('r', time()).rand()));
		return $message;
	}

	static public function newAlternative() {
		$message = new MultipartBuilder(self::CONTENT_TYPE_ALTERNATIVE, self::MIME_VERSION_1_0);
		$message->setBoundary(md5(date('r', time()).rand()));
		return $message;
	}

	static public function newBodyPart($contentType, $body) {
		$message = new MultipartBuilder($contentType);
		$message->setBody($body);
		return $message;
	}

	static public function newFilePart($contentType, $contentDisposition, $filePath, $filename=null) {
		if(!is_readable($filePath)) {
			throw new \Exception("File $filePath not found!"); }

		$message = new MultipartBuilder($contentType);
		$message->filePath = $filePath;
		$message->setContentDisposition($contentDisposition, $filename);
		$message->setContentTransferEncoding(self::CONTENT_TRANSFER_ENCODING_BASE64);
		return $message;
	}

	public function setVersion($version) {
		$this->version = $version;
	}

	public function addHeader($name, $value, $charset=null) {
		$this->headers[$name] = $charset === null ? $value : $this->encodedWord($value, $charset);
		return $this;
	}

	public function setContentType($contentType) {
		$this->contentType = $contentType;
	}

	public function setContentTransferEncoding($contentTransferEncoding) {
		$this->contentTransferEncoding = $contentTransferEncoding;
	}

	public function setContentDisposition($contentDisposition, $filename=null) {
		$this->contentDisposition = array(
			'type' => $contentDisposition,
			'filename' => $filename
		);
	}

	public function setBoundary($boundary) {
		// encapsulate in "" if contains space
		$this->boundary = $boundary;
	}

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function addPart(MultipartBuilder $part) {
		if($part->contentType !== self::CONTENT_TYPE_MIXED && 
					$part->contentType !== self::CONTENT_TYPE_ALTERNATIVE) {
			$part->setBoundary($this->boundary);
		}
		$this->parts[] = $part;
		return $this;
	}

	private function writeHeader($outStream, $line, $foldWidth=self::MAX_LINE_LENGTH_HEADER) {
		if($foldWidth !== null && isset($line[$foldWidth])) {
			// Remove final separator
			$line = substr(chunk_split($line, $foldWidth, self::CRLF.self::LWSP), 0, -3); }
		fwrite($outStream, $line.self::CRLF);
	}

	public function build($outStream) {
		if($this->version !== null) {
			$this->writeHeader($outStream, "MIME-Version: $this->version"); }

		if($this->contentType !== null) {
			$contentTypeHeader = "Content-Type: $this->contentType";
			if($this->contentType === self::CONTENT_TYPE_MIXED || 
					$this->contentType === self::CONTENT_TYPE_ALTERNATIVE) {
				$contentTypeHeader .= "; boundary=$this->boundary";
			}
			$this->writeHeader($outStream, $contentTypeHeader);
		}

		if($this->contentDisposition !== null) {
			$contentDispositionHeader = "Content-Disposition: {$this->contentDisposition['type']}";
			if($this->contentDisposition['filename'] !== null) {
				$contentDispositionHeader .= "; filename=\"{$this->contentDisposition['filename']}\""; }
			$this->writeHeader($outStream, $contentDispositionHeader);
		}

		if($this->contentTransferEncoding !== null) {
			$this->writeHeader($outStream, "Content-Transfer-Encoding: $this->contentTransferEncoding");
		}

		foreach($this->headers as $name => $value) {
			$this->writeHeader($outStream, "$name: $value");
		}
		
		fwrite($outStream, "\r\n");

		// preamble if root
		if($this->body !== null) { $this->writeBody($outStream); }
		else if($this->filePath !== null) { $this->writeFilePathContents($outStream); }

		foreach($this->parts as $part) {
			fwrite($outStream, "\r\n--$this->boundary\r\n");
			$part->build($outStream);
		}

		if($this->contentType === self::CONTENT_TYPE_MIXED || 
					$this->contentType === self::CONTENT_TYPE_ALTERNATIVE) {
			fwrite($outStream, "\r\n--$this->boundary--");
			// epilogue
		}
	}

	private function writeFilePathContents($outStream) {
		if($this->contentTransferEncoding === self::CONTENT_TRANSFER_ENCODING_BASE64) {
			$fh = fopen($this->filePath, 'r');
			//https://www.w3.org/Protocols/rfc1341/5_Content-Transfer-Encoding.html
			$chunkSplit = array('line-length' => 76, 'line-break-chars' => "\r\n");
			stream_filter_append($fh, 'convert.base64-encode', STREAM_FILTER_READ, $chunkSplit);
			stream_copy_to_stream($fh, $outStream);
			fclose($fh);
		}
		else if($this->contentTransferEncoding === self::CONTENT_TRANSFER_ENCODING_BINARY || 
				$this->contentTransferEncoding === self::CONTENT_TRANSFER_ENCODING_7BIT || 
				$this->contentTransferEncoding === self::CONTENT_TRANSFER_ENCODING_8BIT) {
			$fh = fopen($this->filePath, 'r');
			stream_copy_to_stream($fh, $outStream);
			fclose($fh);
		}
		else {
			$fh = fopen($this->filePath, 'r');
			stream_copy_to_stream($fh, $outStream);
			fclose($fh);
		}
	}

	private function writeBody($outStream, $foldWidth=self::MAX_LINE_LENGTH_BODY) {
		if($this->contentTransferEncoding === self::CONTENT_TRANSFER_ENCODING_BASE64) {
			//https://www.w3.org/Protocols/rfc1341/5_Content-Transfer-Encoding.html
			fwrite($outStream, chunk_split(base64_encode($this->body)));
		}
		else if($this->contentTransferEncoding === self::CONTENT_TRANSFER_ENCODING_BINARY || 
				$this->contentTransferEncoding === self::CONTENT_TRANSFER_ENCODING_7BIT || 
				$this->contentTransferEncoding === self::CONTENT_TRANSFER_ENCODING_8BIT) {
			fwrite($outStream, $this->body);
		}
		else {
			$body = $this->body;
			if($foldWidth !== null && isset($body[$foldWidth])) {
				// Remove final separator
				$body = substr(chunk_split($body, $foldWidth, self::CRLF), 0, -2); }
			fwrite($outStream, $body);
		}
	}

	public function encodedWord($text, $charset=self::CHARSET_UTF8) {
		// Encoded-word more than 75 in length must be separated by CRLF SPACE 
		// into multiple encoded-words.
		// Header line can't be more than 76 if they contain an encoded-word.
		$metaLen = strlen("=?$charset?B??=");
		$textLen = strlen($text);
		
		if($textLen+$metaLen > 75) {
			return "=?$charset?B?".implode("?=\r\n =?$charset?B?", 
				array_map('base64_encode', explode("\r\n", rtrim(chunk_split($text, 50)))))."?=";
		}
		
		return "=?$charset?B?".base64_encode($text)."?=";
	}
}

//die(MimeMultipartPart::newMixed()->encodedWord("Encoded-word more than 75 in length must be separated by CRLF SPACE"));

/*
//$os = fopen("php://output", "w");
$os = fopen("e:\\propheth\\repo\\commerceos\\includes\\tmp\\multipart1.txt", "w");
MimeMultipartPart::newMixed()
	->setBody("Preamble. Hello, world.")
	->addPart(MimeMultipartPart::newAlternative()
		->addPart(MimeMultipartPart::newBodyPart("text/plain", "This is a plain text email message"))
		->addPart(MimeMultipartPart::newBodyPart("text/html", "This is a <em>HTML</em> email message"))
	)
	->addPart(MimeMultipartPart::newBodyPart("text/plain", "This is a plain text email message"))
	->addPart(MimeMultipartPart::newFilePart("text/plain", MimeMultipartPart::CONTENT_DISPOSITION_ATTACHMENT, "e:\\propheth\\repo\\commerceos\\includes\\data\\export.txt", 'test.txt'))
	->addHeader("From", "Dev <dev@interniaga.net>")
	->addHeader("To", "Test <test@interniaga.net>")
	->addHeader("Reply-To", "Dev <dev@interniaga.net>")
	->addHeader("Subject", "Crazy MIME Multipart Email Test")
	->build($os);

fclose($os);
*/
?>