<?php
declare(strict_types = 1);

namespace MayMeow\IMAP;

class MailBox {

    protected MailServer $mailServer;

    protected $openedMailbox;

    protected string $mailboxName;

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
        if ($this->openedMailbox == null) {
            $this->openedMailbox = $this->mailServer->getStream($mailbox);
            $this->mailboxName = $mailbox;
        }

        //  todo throws error when wants open anoter mailbox before close first one

        return $this;
    }

    public function appendMessage(string $message)
    {
        // todo throws error when mailbox is not opened
        return  imap_append($this->openedMailbox, $this->mailServer->getServerName() . $this->mailboxName, $message);
    }

    public function closeMailbox()
    {
        // todo throws error when mailbox is not opened

        $this->mailboxName = "";
        
        return imap_close($this->openedMailbox);
    }

    public function getMessageHeaders()
    {
        // todo throws error when no mailbox is opened

        return imap_headers($this->openedMailbox);
    }
}