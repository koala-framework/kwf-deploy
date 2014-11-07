<?php
namespace Kwf\Deploy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serverSection = $input->getOption('server');

        $config = parse_ini_file('config.ini', true);
        if (!isset($config[$serverSection])) {
            throw new \Exception("Invalid server: '$serverSection' section not found in config.ini");
        }
        var_dump($config[$serverSection]['server.user']);
        var_dump($config[$serverSection]['server.host']);
        var_dump($config[$serverSection]['server.port']);
        var_dump($config[$serverSection]['server.dir']);

//         if ($input->getOption('yell')) {

        $excludes = ExcludeFinder::findExcludes('.');
        $excludeArgs = '';
        foreach ($excludes as $i) {
            $excludeArgs .= " --exclude=".escapeshellarg($i);
        }
        $cmd = "rsync -avpz $excludeArgs . ../deploy ";
        $this->_systemCheckRet($cmd, $input, $output);

        $output->writeln($cmd);
    }

    private function _systemCheckRet($cmd, InputInterface $input, OutputInterface $output)
    {
        $ret = null;
        if ($output->isDebug()) {
            $output->writeln($cmd);
        }
        //passthru($cmd, $ret);
        if ($ret != 0) {
            throw new \Exception("command failed");
        }
    }
}