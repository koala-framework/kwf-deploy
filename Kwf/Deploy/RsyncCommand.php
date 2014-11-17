<?php
namespace Kwf\Deploy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kwf\Deploy\ExcludeFinder;

class RsyncCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('rsync')
            ->setDescription('Deploy application using rsync to server defined in config.ini')
            ->addOption(
               'server',
               's',
               InputOption::VALUE_OPTIONAL,
                'Server (section) to deploy to',
                'production'
            )
            ->addOption(
               'dry-run',
               null,
               InputOption::VALUE_NONE,
                'Server (section) to deploy to'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serverSection = $input->getOption('server');

        $config = new \Zend_Config_Ini('config.ini', $serverSection);
        $server = $config->server;

        $remote = ($server->port ? '-e "ssh -p '.$server->port.'" ':'') . $server->user.'@'.$server->host.':'.$server->dir;

        if (!$input->getOption('dry-run')) {
            $output->writeln("This will upload the current working copy to $remote.");
            $output->writeln("All files will be overwritten, any changes will be lost. Online directories you don't have locally not matching a .gitignore pattern will be deleted.");
            $output->writeln("Make sure your local application is stable and build is up to date.");
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this action? [Y/n]', true);
            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }
        $excludes = ExcludeFinder::findExcludes('.');
        $excludeArgs = '';
        foreach ($excludes as $i) {
            $excludeArgs .= " --exclude=".escapeshellarg($i);
        }
        $cmd = "rsync -apz --delete --progress --verbose ";
        if ($input->getOption('dry-run')) {
            $cmd .= "--dry-run ";
        }
        $cmd .= "$excludeArgs . $remote";
        $this->_systemCheckRet($cmd, $input, $output);

        if (!$input->getOption('dry-run')) {
            $output->writeln("Upload finished. Now executing update scripts and clearing caches.");
            $cmd = "cd $server->dir && ".($server->phpCli ? $server->phpCli : 'php')." bootstrap.php update";
            $remote = ($server->port ? '-p '.$server->port.' ':'') . $server->user.'@'.$server->host;
            $cmd = "ssh $remote ".escapeshellarg($cmd);
            $this->_systemCheckRet($cmd, $input, $output);
        }
    }

    private function _systemCheckRet($cmd, InputInterface $input, OutputInterface $output)
    {
        $ret = null;
        if ($output->isDebug()) {
            $output->writeln($cmd);
        }
        passthru($cmd, $ret);
        if ($ret != 0) {
            throw new \Exception("command failed");
        }
    }
}