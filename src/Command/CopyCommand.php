<?php
declare(strict_types = 1);

namespace MayMeow\IMAP\Command;

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

        $io->info($input->getOption('password_a'), $input->getOption('password_a'));

        // Source Server
        $sourceServer = new \MayMeow\IMAP\MailServer($sourceHost, $input->getOption('user_a'), $input->getOption('password_a'), 993, true);
        $sourceStream = $sourceServer->getStream();

        // Destination server
        $destinationServer = new \MayMeow\IMAP\MailServer($destinationHost, $input->getOption('user_b'), $input->getOption('password_b'), 993, true);
        $destinationStream = $destinationServer->getStream();

        if ($sourceStream && $destinationStream) {
            $list = $sourceServer->getMailboxList($sourceStream);
            $destinationList = $destinationServer->getMailboxList($destinationStream);
        
            if (is_array($list)) {
                foreach($list as $mailbox) {
                    // Get folders
                    $pos = strpos($mailbox,"}");
                    $mailbox = substr($mailbox,$pos+1);
                    //var_dump($mailbox);
        
                    // create same folders if they are not on destination server
                    $sourceMailbox = $sourceServer->getStream($mailbox);
        
                    if(!array_search($destinationServer->getServerName() . $mailbox, $destinationList)) {
                        $ds = $destinationServer->getServerName();
                        $io->caution("Creating mailbox $mailbox on $ds \n");
        
                        $destinationServer->createMailbox($destinationStream, $mailbox);
                    }
        
                    // open destination mailbox
                    $destinationmailbox = $destinationServer->getStream($mailbox);
        
                    if ($destinationmailbox) {
                        $headers = $sourceServer->getMailHeaders($sourceServer->getStream($mailbox));
                        $total = count($headers);
                        $n = 1;
                        if ($total) {
                            $io->info("Total $total message in $mailbox");
                            $io->progressStart($total);

                            // IF there are some messages copy them
                            if($headers) {
                                //$io->info("Copying $n of $total ...");
                                foreach ($headers as $key => $thisHeaders) {
                                    //echo "Copying $n of $total ... \n";
                                    $header = imap_headerinfo($sourceMailbox, $key+1);
                                    //var_dump($header);
                                    $is_unseen = $header->Unseen;
                                    //echo "is_unseen = $is_unseen \n";
            
                                    $messageHeader = imap_fetchheader($sourceMailbox, $key+1);
                                    $body = imap_body($sourceMailbox, $key+1);
            
                                    if (imap_append($destinationmailbox,$destinationServer->getServerName() . $mailbox,$messageHeader."\r\n".$body)) {
                                        //echo "OK \n";
                                        //$io->info("OK");
            
                                        if ($is_unseen != "U") {
                                            $sequence = $key+1;
                                            imap_setflag_full($destinationmailbox, (string)$sequence, "\\Seen");
                                        }
                                    } else {
                                        //echo "Error \n";
                                        //$io->info("Error");
                                    }
                                    $io->progressAdvance();
                                    $n++;
                                }
                                $io->progressFinish();
                            }
                            
                        } else {
                            $io->info("Skipped $mailbox");
                        }
                    }
                    imap_close($destinationmailbox);
                }
                imap_close($sourceMailbox);
            }
            
        }
        
        imap_close($sourceStream);
        imap_close($destinationStream);

        return Command::SUCCESS;
    }
}