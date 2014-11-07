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

        $config = parse_ini_file('config.ini', true);
        if (!isset($config[$serverSection])) {
            throw new \Exception("Invalid server: '$serverSection' section not found in config.ini");
        }
        $config = $config[$serverSection];

        if (!isset($config['server.host'])) {
            throw new \Exception("Invalid server settings for section '$serverSection': server.host is required");
        }

        if (isset($config['server.user'])) {
            $host = $config['server.user'].'@'.$config['server.host'];
        } else {
            $host = $config['server.host'];
        }
        if (isset($config['server.port'])) {
            $host .= ' -p '.$config['server.port'];
        }
        if (isset($config['server.dir'])) {
            $cmd = "cd ".$config['server.dir'].';';
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