<?php
namespace Kwf\Deploy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShellCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('shell')
            ->setDescription('Connect to server defined in config.ini using ssh')
            ->addOption(
               'server',
               's',
               InputOption::VALUE_REQUIRED,
                'Server (section) to deploy to',
                'production'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serverSection = $input->getOption('server');

        $config = new \Zend_Config_Ini('config.ini', $serverSection);
        $server = $config->server;

        if (!isset($server->host)) {
            throw new \Exception("Invalid server settings for section '$serverSection': host is required");
        }

        if (isset($server->user)) {
            $host = $server->user.'@'.$server->host;
        } else {
            $host = $server->host;
        }
        if (isset($server->port)) {
            $host .= ' -p '.$server->port;
        }
        if (isset($server->dir)) {
            $cmd = "cd ".$server->dir.';';
        }

        $gitEnvVars = "GIT_AUTHOR_NAME=".escapeshellarg(isset($_ENV['GIT_AUTHOR_NAME']) ? $_ENV['GIT_AUTHOR_NAME'] : trim(`git config user.name`)).
            " GIT_AUTHOR_EMAIL=".escapeshellarg(isset($_ENV['GIT_AUTHOR_EMAIL']) ? $_ENV['GIT_AUTHOR_EMAIL'] : trim(`git config user.email`)).
            " GIT_COMMITTER_NAME=".escapeshellarg(isset($_ENV['GIT_COMITTER_NAME']) ? $_ENV['GIT_COMITTER_NAME'] : trim(`git config user.name`)).
            " GIT_COMMITTER_EMAIL=".escapeshellarg(isset($_ENV['GIT_COMITTER_EMAIL']) ? $_ENV['GIT_COMITTER_EMAIL'] : trim(`git config user.email`));

        $cmd .= $gitEnvVars.' ';
        $cmd .= "exec bash";
        $cmd = "ssh -t $host ".escapeshellarg($cmd);
        if ($output->isDebug()) {
            $output->writeln($cmd);
        }
        $ret = null;
        passthru($cmd, $ret);
        exit($ret);
    }
}