<?php
namespace RtxLabs\LiquibaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RtxLabs\LiquibaseBundle\Generator\ChangelogGenerator;

class CreateChangelogCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('liquibase:generate:changelog')
            ->setDescription('Generate a new Liquibase changelog file skeleton')
            ->addArgument('changelog', InputArgument::OPTIONAL,
                          'The name of the generated changelog file. Default value is the current timestamp.',
                          date('YmdHis'))
            ->addOption('bundle', 'b', InputArgument::OPTIONAL, 'Bundle where the changelog will be generated.')
            ->addOption('with-changeset', 'c', InputOption::VALUE_NONE, 'Add a <changeSet> tag to the changelog')
            ->addOption('author', 'a', InputOption::VALUE_OPTIONAL, 'Author to use for generation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generator = new ChangelogGenerator($this->getContainer()->get('filesystem'), __DIR__.'/../Resources/skeleton');

        list($bundle, $name) = $this->parseShortcutNotation($input->getArgument('changelog'));
        if ($input->getOption('bundle')) {
            $bundle = $input->getOption('bundle');
        }

        if ($bundle) {
            $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);
        }
        
        $generator->generate($bundle, $name, $input->getOption('with-changeset'), $input->getOption('author'));
    }

    protected function parseShortcutNotation($shortcut)
    {
        $pos = strpos($shortcut, ':');
        if ($pos === false) {
            return array(null, $shortcut);
        }

        return array(substr($shortcut, 0, $pos), substr($shortcut, $pos + 1));
    }
}