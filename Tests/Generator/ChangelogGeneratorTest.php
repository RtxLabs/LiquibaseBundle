<?php

namespace RtxLabs\LiquibaseBundle\Tests\Generator;

use Symfony\Component\Filesystem\Filesystem;
use RtxLabs\LiquibaseBundle\Generator\ChangelogGenerator;

class ChangelogGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $filesystem;
    protected $tmpDir;

    public function setUp()
    {
        $this->tmpDir = sys_get_temp_dir().'/lbb/';
        $this->filesystem = new Filesystem();
        $this->filesystem->remove($this->tmpDir);

        $this->generator = new ChangelogGenerator($this->filesystem, __DIR__.'/../../Resources/skeleton');

        $this->bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\Bundle', array('getPath'));
        $this->bundle->expects($this->any())
                        ->method('getPath')
                        ->will($this->returnValue($this->tmpDir.'Foo/BarBundle'));
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testGenerateEmptyBundleChangelog()
    {
        $this->generator->generate($this->bundle, 'foo-changelog', false);

        $this->assertTrue(file_exists($this->tmpDir.'/Foo/BarBundle/Resources/liquibase/changelogs/foo-changelog.xml'));
        $this->assertTrue(file_exists($this->tmpDir.'/Foo/BarBundle/Resources/liquibase/changelog-master.xml'));

        // check changelog file
        $changelog = @simplexml_load_file($this->tmpDir.'/Foo/BarBundle/Resources/liquibase/changelogs/foo-changelog.xml');
        $this->assertTrue($changelog !== false);

        $this->assertEquals("databaseChangeLog", $changelog->getName());
        $this->assertTrue(count($changelog->children()) == 0);

        // check master file
        $master = @simplexml_load_file($this->tmpDir.'/Foo/BarBundle/Resources/liquibase/changelog-master.xml');
        $this->assertTrue($master !== false);

        $this->assertEquals("databaseChangeLog", $master->getName());

        $this->assertTrue(count($master->children()) > 0);
        foreach ($master->children() as $include) {
            $this->assertEquals("includeAll", $include->getName());
        }

    }

    public function testGenerateWithChangeset()
    {
        $this->generator->generate($this->bundle, 'foo-changelog', true);

        $this->assertTrue(file_exists($this->tmpDir.'/Foo/BarBundle/Resources/liquibase/changelogs/foo-changelog.xml'));
        $this->assertTrue(file_exists($this->tmpDir.'/Foo/BarBundle/Resources/liquibase/changelog-master.xml'));

        // check changelog file
        $changelog = @simplexml_load_file($this->tmpDir.'/Foo/BarBundle/Resources/liquibase/changelogs/foo-changelog.xml');
        $this->assertTrue($changelog !== false);

        $this->assertEquals("databaseChangeLog", $changelog->getName());
        $this->assertTrue(count($changelog->children()) == 1);

        foreach ($changelog->children() as $include) {
            $this->assertEquals("changeSet", $include->getName());
        }
    }

    public function testGenerateWithAuthor()
    {
        $this->generator->generate($this->bundle, 'foo-changelog', true, "john@doe.com");

        $changelog = @simplexml_load_file($this->tmpDir.'/Foo/BarBundle/Resources/liquibase/changelogs/foo-changelog.xml');
        $this->assertTrue($changelog !== false);

        $this->assertEquals("databaseChangeLog", $changelog->getName());
        $this->assertTrue(count($changelog->children()) == 1);

        foreach ($changelog->children() as $changeset) {
            $attributes = $changeset->attributes();
            $this->assertEquals("john@doe.com", $attributes['author']);
        }

        $this->assertTrue(file_exists($this->tmpDir.'/Foo/BarBundle/Resources/liquibase/changelogs/foo-changelog.xml'));

    }

    /**
     * @expectedException RuntimeException
     */
    public function testChangelogExistException()
    {
        $this->generator->generate($this->bundle, 'foo-changelog', true);
        $this->generator->generate($this->bundle, 'foo-changelog', true);
    }
}