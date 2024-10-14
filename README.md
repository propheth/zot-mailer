# README

Zot Mailer is a modern PHP mailer library that enables the sending of email over different underlying mail transports.

We needed a light-weight, performant simple but secure library with a maintainable codebase.

- SMTP and the PHP mail() function transport support
- Automatically chooses the best available method to send SMTP mail.
- STARTTLS support, PLAIN and LOGIN authentication protocols.
- MIME multipart message support
- Attachments, inline.

Shared hosters are beginning to disable sending email using the PHP mail() function. To complicate things further, there are new transport and authentication protocols being introduced by every td&h.

Codebase quality separates it from the other mailer libraries out there.

- Decoupled message model and build
- Separation between message and transport

## Usage

Fast autoloader included. Just require the ```autoloader.php``` file.

Example:
```
require_once(__DIR__.'/zot-mailer/src/autoloader.php');
```

You can also call your own autoloader or manually require files.