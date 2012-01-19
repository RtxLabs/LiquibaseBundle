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
            ->setDescription('Generating a Liquibase changelog file skeleton')
            ->addArgument('changelog', InputArgument::OPTIONAL,
                          'The name of the changelog (shortcut notation AcmeDemoBundle:CreateTable), default is a timestamp',
                          date('YmdHis'))
            ->addOption('with-changeset', 'c', InputOption::VALUE_NONE, 'adds a changeset tag to the changelog')
            ->addOption('author', 'a', InputOption::VALUE_OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generator = new ChangelogGenerator($this->getContainer()->get('filesystem'), __DIR__.'/../Resources/skeleton');

        $changelog = Validators::validateChangelogName($input->getArgument('changelog'));
        list($bundle, $name) = $this->parseShortcutNotation($changelog);

        if (strlen($bundle) > 0) {
            $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);
        }
        else {
            $bundle = null;
        }
        
        $generator->generate($bundle, $name, $input->getOption('with-changeset'), $input->getOption('author'));
    }

    protected function parseShortcutNotation($shortcut)
    {
        if (false === $pos = strpos($shortcut, ':')) {
            throw new \InvalidArgumentException(sprintf('The changelog name must contain a : ("%s" given, expecting something like AcmeBlogBundle:CreateUserTable or :CreateUserTable)', $shortcut));
        }

        return array(substr($shortcut, 0, $pos), substr($shortcut, $pos + 1));
    }
}