<?php

namespace Angle\NickelTracker\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Angle\NickelTracker\CoreBundle\Entity\User;
use Angle\NickelTracker\CoreBundle\Entity\Account;
use Angle\NickelTracker\CoreBundle\Entity\Category;


class TestNickelTrackerServiceCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('nt:test:service')
            ->setDescription("Test the capabilities of the NickelTracker service");
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nt = $this->getContainer()->get('angle.nickeltracker');

        $output->writeln("NickelTracker service initialized correctly!");
    }
}
