# PHP Imap Copy

Simple php app to copy email from one account to another

## Usage

```bash
php app.php help mail:copy                                                                                                                                                      Description:
  Copy emails from one account to second

Usage:
  email:copy [options]

Options:
      --server_a=SERVER_A      Source server
      --server_b[=SERVER_B]    Destination Server, if not userd, serverA is Used
      --user_a=USER_A          User on source server
      --password_a=PASSWORD_A  Password on source server
      --user_b=USER_B          User on destination server
      --password_b=PASSWORD_B  Password on destination server
  -h, --help                   Display help for the given command. When no command is given display help for the list command
  -q, --quiet                  Do not output any message
  -V, --version                Display this application version
      --ansi|--no-ansi         Force (or disable --no-ansi) ANSI output
  -n, --no-interaction         Do not ask any interactive question
  -v|vv|vvv, --verbose         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  This command allow you to coppy emails between accounts
```

Email copying

```bash
php app.php email:copy --server_a imap.server1.tld --user_a mail@server1.tld --password_a "Y0urPa\$\$W0rd" --server_b imap.server2.tld --user_b mail@server2.tld--password_b "Y0urPaSSw0rd"
```

## What this app know

* Copy Emails from one to another account
* Create folders on 2nd server

## What it doesn't know

* Does not supporting batch accounts (form jsson or csv)
* Does not checking for existing emails or folders on 2nd server, Its just copying everything from 1st to 2nd server.
* Support only IMAP on  993 port via SSL (for NOW)