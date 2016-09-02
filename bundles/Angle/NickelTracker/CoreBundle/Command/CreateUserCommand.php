<?php

namespace Angle\NickelTracker\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Angle\NickelTracker\CoreBundle\Entity\User;
use Angle\NickelTracker\CoreBundle\Entity\Account;
use Angle\NickelTracker\CoreBundle\Entity\Category;


class CreateUserCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('nt:create:user')
            ->setDescription("Create a new Super Admin user")
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'User email'
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'User full name'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'User password'
            )
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        # Initialize Doctrine
        $doctrine = $this->getContainer()->get('doctrine');
        /* @var \Doctrine\ORM\EntityManager $em */
        $em = $doctrine->getManager();

        // Load command arguments
        $email = $input->getArgument('email');
        $name = $input->getArgument('name');
        $password = $input->getArgument('password');

        // Check email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) { // email is invalid
            $output->writeln('<error>Invalid email provided</error>');
            return false;
        }

        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->getContainer()->get('angle.nickeltracker');
        $nt->enableAdminMode(true);

        $r = $nt->createUser($email, $name, $password);

        if (!$r) {
            $output->writeln("<error>Error creating new user " . $email . "</error>");
            $output->writeln('Details: ' . $nt->getError()['message']);
        } else {
            $output->writeln("Created SUPER_NT_USER user <info>" . $email . "</info> successfully!");
        }
    }
}
