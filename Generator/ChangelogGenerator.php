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
            $changelogPath = 'app/Resources/changelogs/'.$name.'.xml';
        }
        else {
            $changelogPath = $bundle->getPath().'/Resources/changelogs/'.$name.'.xml';
        }

        if (file_exists($changelogPath)) {
            throw new \RuntimeException(sprintf('Changelog "%s" already exists.', $changelogPath));
        }
        
        $this->renderFile($this->skeletonDir, 'changelog.xml', $changelogPath, $parameters);
    }
}