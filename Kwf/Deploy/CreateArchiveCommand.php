<?php
namespace Kwf\Deploy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Kwf\Deploy\ExcludeFinder;

class CreateArchiveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('create-archive')
            ->setDescription('Create tar.gz archive containing all files required to run application')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!file_exists(('bootstrap.php'))) {
            throw new \Exception("Run this script in the application root directory");
            exit(1);
        }

        $excludes = ExcludeFinder::findExcludes('.');
        $excludeArgs = '';
        foreach ($excludes as $i) {
            $excludeArgs .= " -x ".escapeshellarg('./'.$i.'*');
        }
        $cmd = "zip deploy.zip . --quiet -r $excludeArgs";
        $output->writeln("creating deploy.zip archive...");
        $this->_systemCheckRet($cmd, $input, $output);

        $output->writeln("deploy.zip successfully created.");
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
