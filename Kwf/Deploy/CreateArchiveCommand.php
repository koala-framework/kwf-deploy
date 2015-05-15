<?php
namespace Kwf\Deploy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Kwf\Deploy\ExcludeFinder;
use Kwf\Deploy\Db\Dump as DbDump;

class CreateArchiveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('create-archive')
            ->setDescription('Create tar.gz archive containing all files required to run application')
            ->addOption(
               'include-initial-dump',
               null,
               InputOption::VALUE_NONE,
                'Include a dump of the current (local) database which will be used for initial setup'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!file_exists(('bootstrap.php'))) {
            throw new \Exception("Run this script in the application root directory");
        }
        \Kwf_Setup::setUp();

        if (file_exists('setup/initial/dump.sql')) {
            unlink('setup/initial/dump.sql');
        }
        if (file_exists('setup/initial/uploads')) {
            foreach (glob('setup/initial/uploads/*') as $f) {
                unlink($f);
            }
        }

        if ($input->getOption('include-initial-dump')) {
            $output->writeln("checking for pending updates...");
            $pendingUpdatesCount = \Kwf_Util_Update_Helper::countPendingUpdates();
            if ($pendingUpdatesCount) {
                throw new \Exception("$pendingUpdatesCount Updates have not been executed. Run update first.");
            }
            $output->writeln("creating database dump...");
            $dump = DbDump::dump();
            if (!file_exists('setup/initial')) {
                mkdir('setup/initial', 0777, true);
                $ignore = "";
                if (file_exists('setup/.gitignore')) {
                    $ignore = file_get_contents('setup/.gitignore');
                }
                if (!preg_match('#^initial$#m', $ignore)) {
                    $ignore = rtrim($ignore);
                    if ($ignore) $ignore .= "\n";
                    $ignore .= "initial\n";
                }
                file_put_contents('setup/.gitignore', $ignore);
            }
            file_put_contents('setup/initial/dump.sql', $dump);

            $output->writeln("copying uploads...");
            if (!file_exists('setup/initial/uploads')) {
                mkdir('setup/initial/uploads');
            }
            $model = \Kwf_Model_Abstract::getInstance('Kwf_Uploads_Model');
            $select = new \Kwf_Model_Select();
            $it = new \Kwf_Model_Iterator_Packages(
                new \Kwf_Model_Iterator_Rows($model, $select)
            );
            foreach ($it as $row) {
                $fileSource = $row->getFileSource();
                copy($fileSource, 'setup/initial/uploads/'.basename($fileSource));
            }
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
