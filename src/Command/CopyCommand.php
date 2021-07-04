<?php
declare(strict_types = 1);

namespace MayMeow\IMAP\Command;

use MayMeow\IMAP\MailBox;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CopyCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'email:copy';

    protected function configure(): void
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Copy emails from one account to second')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command allow you to coppy emails between accounts')

        ->addOption('server_a', null, InputOption::VALUE_REQUIRED, 'Source server')
        ->addOption('server_b', null, InputOption::VALUE_OPTIONAL, 'Destination Server, if not userd, serverA is Used')
        ->addOption('user_a', null, InputOption::VALUE_REQUIRED, 'User on source server')
        ->addOption('password_a', null, InputOption::VALUE_REQUIRED, 'Password on source server')
        ->addOption('user_b', null, InputOption::VALUE_REQUIRED, 'User on destination server')
        ->addOption('password_b', null, InputOption::VALUE_REQUIRED, 'Password on destination server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // options
        $io = new SymfonyStyle($input, $output);
        
        $sourceHost = $input->getOption('server_a');
        if($input->getOption('server_b') == null) {
            $destinationHost = $sourceHost;
        } else {
            $destinationHost = $input->getOption('server_b');
        }

        // Source Server
        $sourceServer = new \MayMeow\IMAP\MailServer($sourceHost, $input->getOption('user_a'), $input->getOption('password_a'), 993, true);
        $sourceStream = $sourceServer->getStream();

        // Destination server
        $destinationServer = new \MayMeow\IMAP\MailServer($destinationHost, $input->getOption('user_b'), $input->getOption('password_b'), 993, true);
        $destinationStream = $destinationServer->getStream();

        if ($sourceStream && $destinationStream) {
            $smbox = new MailBox($sourceServer);
            $dmbox = new MailBox($destinationServer);

            //$list = $sourceServer->getMailboxList($sourceStream);
            $list = $smbox->getList();
            //$destinationList = $destinationServer->getMailboxList($destinationStream);
            $destinationList = $dmbox->getList();
        
            if (is_array($list)) {
                foreach($list as $mailbox) {
                    // Get folders
                    $pos = strpos($mailbox,"}");
                    $mailbox = substr($mailbox,$pos+1);
                    //var_dump($mailbox);
        
                    // create same folders if they are not on destination server
                    //$sourceMailbox = $sourceServer->getStream($mailbox);
                    $sourceMailbox = $smbox->getMailbox($mailbox);
        
                    if($mailbox != "" && !array_search($destinationServer->getServerName() . $mailbox, $destinationList)) {
                        $ds = $destinationServer->getServerName();
                        $io->caution("Creating new mailbox $mailbox on $ds");
        
                        $destinationServer->createMailbox($destinationStream, $mailbox);
                    }
        
                    // open destination mailbox
                    //$destinationmailbox = $destinationServer->getStream($mailbox);
                    $destinationmailbox = $dmbox->getMailbox($mailbox);
        
                    if ($destinationmailbox) {
                        $headers = $sourceServer->getMailHeaders($sourceServer->getStream($mailbox));
                        $total = count($headers);
                        $n = 1;
                        if ($total) {
                            $io->text("Total $total message in $mailbox");
                            $io->progressStart($total);

                            // IF there are some messages copy them
                            if($headers) {
                              
                                foreach ($headers as $key => $thisHeaders) {
                                    $header = imap_headerinfo($sourceMailbox, $key+1);
                                    $is_unseen = $header->Unseen;
            
                                    $messageHeader = imap_fetchheader($sourceMailbox, $key+1);
                                    $body = imap_body($sourceMailbox, $key+1);
            
                                    //if (imap_append($destinationmailbox,$destinationServer->getServerName() . $mailbox,$messageHeader."\r\n".$body)) {
                                    if ($destinationServer->append($mailbox, $messageHeader."\r\n".$body)) {
            
                                        if ($is_unseen != "U") {
                                            $sequence = $key+1;
                                            imap_setflag_full($destinationmailbox, (string)$sequence, "\\Seen");
                                        }
                                    } else {
                                        $io->error("Error");
                                    }
                                    $io->progressAdvance();
                                    $n++;
                                }
                                $io->progressFinish();
                            }
                            
                        } else {
                            $io->text("Skipped $mailbox");
                        }
                    }
                    imap_close($destinationmailbox);
                }
                imap_close($sourceMailbox);
            }
            
        }
        
        imap_close($sourceStream);
        imap_close($destinationStream);

        $io->info('Everything is DONE');

        return Command::SUCCESS;
    }
}