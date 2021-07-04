<?php
declare(strict_types = 1);

namespace MayMeow\IMAP;

class MailBox {

    protected MailServer $mailServer;

    public function __construct(MailServer $mailServer)
    {
        $this->mailServer = $mailServer;
    }

    public function getList()
    {
        return imap_list($this->mailServer->getStream(), $this->mailServer->getServerName(), '*');
    }

    public function getMailbox(string $mailbox)
    {
        return $this->mailServer->getStream($mailbox);
    }

    public function appendMessage()
    {
        // todo
    }

    public function closeMailbox()
    {
        // todo
    }
}