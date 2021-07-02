<?php
declare(strict_types = 1);

require_once 'vendor/autoload.php';

use Symfony\Component\Console\Application;
use MayMeow\IMAP\Command\CopyCommand;

$application = new Application("IMAP Copier", "v0.0.1");

$application->add(new CopyCommand());

$application->run();