<?php

namespace RtxLabs\LiquibaseBundle\Generator;

use Symfony\Component\HttpKernel\Util\Filesystem;
use Symfony\Component\DependencyInjection\Container;

class ChangelogGenerator extends \Sensio\Bundle\GeneratorBundle\Generator\Generator
{
    private $filesystem;
    private $skeletonDir;

    public function __construct(Filesystem $filesystem, $skeletonDir)
    {
        $this->filesystem = $filesystem;
        $this->skeletonDir = $skeletonDir;
    }

    public function generate($bundle, $name, $withChangeset = true, $author = "")
    {
        $parameters = array(
            'author'=>$author,
            'withChangeset'=>$withChangeset
        );

        if ($bundle == null) {
            $changelogPath = 'app/Resources/liquibase/';
        }
        else {
            $changelogPath = $bundle->getPath().'/Resources/liquibase/';
        }

        $changelogFile = $changelogPath.'changelogs/'.$name.'.xml';
        $changelogMasterFile = $changelogPath.'changelog-master.xml';

        if (file_exists($changelogFile)) {
            throw new \RuntimeException(sprintf('Changelog "%s" already exists.', $changelogFile));
        }
        
        $this->renderFile($this->skeletonDir, 'changelog.xml', $changelogFile, $parameters);

        if (!file_exists($changelogMasterFile)) {
            $parameter = array('path'=>$changelogPath.'changelogs/');
            $this->renderFile($this->skeletonDir, 'changelog-master.xml', $changelogMasterFile, $parameter);
        }
    }
}