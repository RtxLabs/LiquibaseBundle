<?php
namespace RtxLabs\LiquibaseBundle\Runner;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class LiquibaseRunner
{

    /** @var ContainerInterface */
    private $container;

    /** @var Filesystem */
    private $filesystem;

    /** @var Connection */
    private $dbConnection;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->filesystem = $container->get('filesystem');
        $this->dbConnection = $container->get('doctrine.dbal.default_connection');
    }

    public function runAppUpdate(KernelInterface $kernel, $dryRun, OutputInterface $output = null)
    {
        $this->runUpdate($kernel->getRootDir().'/Resources/liquibase/changelog-master.xml', $dryRun, $output);
    }

    public function runBundleUpdate(BundleInterface $bundle, $dryRun, OutputInterface $output = null)
    {
        $this->runUpdate($bundle->getPath().'/Resources/liquibase/changelog-master.xml', $dryRun, $output);
    }

    public function runAppSync(KernelInterface $kernel, $dryRun, OutputInterface $output = null)
    {
        $this->runSync($kernel->getRootDir().'/Resources/liquibase/changelog-master.xml', $dryRun, $output);
    }

    public function runBundleSync(BundleInterface $bundle, $dryRun, OutputInterface $output = null)
    {
        $this->runSync($bundle->getPath().'/Resources/liquibase/changelog-master.xml', $dryRun, $output);
    }

    private function runUpdate($changelogFile, $dryRun, OutputInterface $output = null)
    {
        $command = $this->getBaseCommand();
        $command .= ' --changeLogFile='.$changelogFile;
        $command .= $dryRun?" updateSQL":" update";

        $this->run($command, $output);
    }

    private function runSync($changelogFile, $dryRun, OutputInterface $output = null)
    {
        $command = $this->getBaseCommand();
        $command .= ' --changeLogFile='.$changelogFile;
        $command .= $dryRun?" changelogSyncSQL":" changelogSync";

        $this->run($command, $output);
    }


    public function runRollback($bundle)
    {

    }

    public function runDiff($bundle)
    {

    }

    protected function run($command, OutputInterface $output = null) {
        if ($output == null) {
            $output = new ConsoleOutput();
        }

        $output->writeln("Running Liquibase command.");
        $output->writeln($command);

        $out = array();
        exec($command, $out);

        foreach ($out as $outLine) {
            $output->writeln($outLine);
        }
    }

    private function getParameter($name) {
        return $this->container->getParameter('rtx_labs_liquibase.' . $name);
    }

    protected function getBaseCommand()
    {
        $dbalParams = $this->dbConnection->getParams();

        $command = $this->getParameter('command');
        $command .= ' --driver='.$this->getJdbcDriverName($dbalParams['driver']).
                    ' --url='.$this->getJdbcDsn($dbalParams);

        if ($dbalParams['user'] != "") {
            $command .= ' --username='.$dbalParams['user'];
        }

        if ($dbalParams['password'] != "") {
            $command .= ' --password='.$dbalParams['password'];
        }

        $command .= ' --classpath='.$this->getJdbcDriverClassPath($dbalParams['driver']);

        return $command;
    }

    protected function getJdbcDriverName($dbalDriver)
    {
        switch($dbalDriver) {
            case 'pdo_mysql':
            case 'mysql':   $driver = "com.mysql.jdbc.Driver"; break;
            default: throw new \RuntimeException("No JDBC-Driver found for $dbalDriver");
        }

        return $driver;
    }

    protected function getJdbcDriverClassPath($dbalDriver)
    {
        $dir = dirname(__FILE__)."/../Resources/vendor/jdbc/";

        switch($dbalDriver) {
            case 'pdo_mysql':
            case 'mysql':   $dir .= "mysql-connector-java-5.1.18-bin.jar"; break;
            default: throw new \RuntimeException("No JDBC-Driver found for $dbalDriver");
        }

        return $dir;
    }

    protected function getJdbcDsn($dbalParams)
    {
        switch($dbalParams['driver']) {
            case 'pdo_mysql': return $this->getMysqlJdbcDsn($dbalParams); break;
            default: throw new \RuntimeException("Database not supported");
        }
    }

    protected function getMysqlJdbcDsn($dbalParams)
    {
        $dsn = "jdbc:mysql://";
        if ($dbalParams['host'] != "") {
            $dsn .= $dbalParams['host'];
        }
        else {
            $dsn .= 'localhost';
        }

        if ($dbalParams['port'] != "") {
            $dsn .= ":".$dbalParams['port'];
        }

        $dsn .= "/".$dbalParams['dbname'];

        return $dsn;
    }
}
