<?php
namespace RtxLabs\LiquibaseBundle\Runner;
use Symfony\Component\HttpKernel\Util\Filesystem;
use Symfony\Component\Process\Process;

class LiquibaseRunner
{
    private $filesystem;
    private $dbConnection;

    public function __construct(Filesystem $filesystem, $dbConnection)
    {
        $this->filesystem = $filesystem;
        $this->dbConnection = $dbConnection;
    }

    public function runAppUpdate(\Symfony\Component\HttpKernel\KernelInterface $kernel, $dryRun)
    {
        $this->runUpdate($kernel->getRootDir().'/Resources/liquibase/changelog-master.xml', $dryRun);
    }

    public function runBundleUpdate(\Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle, $dryRun)
    {
        $this->runUpdate($bundle->getPath().'/Resources/liquibase/changelog-master.xml', $dryRun);
    }

    public function runAppSync(\Symfony\Component\HttpKernel\KernelInterface $kernel, $dryRun)
    {
        $this->runSync($kernel->getRootDir().'/Resources/liquibase/changelog-master.xml', $dryRun);
    }

    public function runBundleSync(\Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle, $dryRun)
    {
        $this->runSync($bundle->getPath().'/Resources/liquibase/changelog-master.xml', $dryRun);
    }

    private function runUpdate($changelogFile, $dryRun)
    {
        $command = $this->getBaseCommand();
        $command .= ' --changeLogFile='.$changelogFile;
        $command .= $dryRun?" updateSQL":" update";

        $this->run($command);
    }

    private function runSync($changelogFile, $dryRun)
    {
        $command = $this->getBaseCommand();
        $command .= ' --changeLogFile='.$changelogFile;
        $command .= $dryRun?" changelogSyncSQL":" changelogSync";

        $this->run($command);
    }


    public function runRollback($bundle)
    {

    }

    public function runDiff($bundle)
    {

    }

    protected function run($command)
    {
        $output = "";
        exec($command, $output);

        echo $command."\n";
        print_r($output);
    }

    protected function getBaseCommand()
    {
        $dbalParams = $this->dbConnection->getParams();

        $command = 'java -jar '.__DIR__.'/../Resources/vendor/liquibase.jar '.
                    ' --driver='.$this->getJdbcDriverName($dbalParams['driver']).
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
            case 'pdo_pgsql':
            case 'postgresql':
            case 'postgres':
            case 'pgsql':   $driver = "org.postgresql.Driver"; break;
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
            case 'pdo_pgsql':
            case 'postgresql':
            case 'postgres':
            case 'pgsql':   $dir .= "postgresql-9.4.1212.jre6.jar"; break;
            default: throw new \RuntimeException("No JDBC-Driver found for $dbalDriver");
        }

        return $dir;
    }

    protected function getJdbcDsn($dbalParams)
    {
        switch($dbalParams['driver']) {
            case 'pdo_mysql':
            case 'mysql': return $this->getStandardJdbcDsn($dbalParams, 'mysql');
            case 'pdo_pgsql':
            case 'postgresql':
            case 'postgres':
            case 'pgsql': return $this->getStandardJdbcDsn($dbalParams, 'postgresql');
            default: throw new \RuntimeException("Database not supported");
        }
    }

    protected function getStandardJdbcDsn($dbalParams, $jdbcPrefix)
    {
        $dsn = 'jdbc:' . $jdbcPrefix . '://';
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
