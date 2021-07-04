<?php
declare(strict_types = 1);

namespace MayMeow\IMAP;

class MailServer
{
    protected string $host;

    protected int $port;

    protected bool $isSSL;

    protected string $username;

    protected string $password;

    private static $instance = null;

    private $server;

    private $stream;

    public function __construct(string $host, string $username, string $password, int $port = 993, bool $isSSL = true)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->isSSL = $isSSL;
    }

    public static function getInstance(string $host, string $username, string $password, int $port = 993, bool $isSSL = true)
    {
        if (self::$instance == null) {
            self::$instance = new MailServer($host, $username, $password, $port, $isSSL);
        }

        return self::$instance;
    }

    // create server name
    private function _getServerName() : string
    {
        if ($this->isSSL) {
            $serverName = $this->host . ':' . $this->port . '/imap/ssl';
        } else {
            $serverName = $this->host . ':' . $this->port;
        }

        return '{'. $serverName . '}';
    }

    public function getServerName()
    {
        return $this->_getServerName();
    }

    // returns mailbox stream
    public function getStream(string $mailbox = null)
    {
        if ($mailbox == null) {
            return imap_open($this->_getServerName(), $this->username, $this->password);
        } else {
            return imap_open($this->_getServerName() . $mailbox, $this->username, $this->password);
        }
    }

    // returns array of mailboxes / folders
    public function getMailboxList($sourceStream)
    {
        return imap_list($sourceStream, $this->_getServerName(), '*');
    }

    public function getMailHeaders($sourceStream)
    {
        return imap_headers($sourceStream);
    }

    public function createMailbox($sourceStream, string $mailbox) : bool
    {
        if (imap_createmailbox($sourceStream, imap_utf7_encode($this->_getServerName().$mailbox))) {
            return true;
        }

        return false;
    }

    public function append(string $mailbox, string $message)
    {
        return imap_append($this->getStream($mailbox), $this->_getServerName() . $mailbox, $message);
    }

    public function isSeen($mailbox, string $sequence, bool $default = true)
    {
        return imap_setflag_full($this->getStream($mailbox), $sequence, "\\Seen");
    }
}