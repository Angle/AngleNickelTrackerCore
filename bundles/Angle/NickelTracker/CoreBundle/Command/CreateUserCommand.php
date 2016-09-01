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
            ->setName('nt:create:admin')
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

        // Load security encoder
        $factory = $this->getContainer()->get('security.encoder_factory');

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setFullName($name);
        $user->setRole('ROLE_NT_USER');
        $em->persist($user);

        /* @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
        $encoder = $factory->getEncoder($user);
        $encodedPassword = $encoder->encodePassword($password, $user->getSalt());
        $user->setPassword($encodedPassword);

        // Create base accounts
        $account = new Account();
        $account->setType(Account::TYPE_CASH);
        $account->setName('Cash');
        $account->setUserId($user);
        $em->persist($account);

        $account = new Account();
        $account->setType(Account::TYPE_DEBIT);
        $account->setName('Debit (Bank)');
        $account->setUserId($user);
        $em->persist($account);

        $account = new Account();
        $account->setType(Account::TYPE_CREDIT);
        $account->setName('Credit Card');
        $account->setUserId($user);
        $em->persist($account);

        // Create base categories
        $categories = array('Groceries', 'Restaurants', 'Gas', 'Entertainment', 'Party', 'Services');
        foreach ($categories as $c) {
            $category = new Category();
            $category->setName($c);
            $category->setUserId($user);
            $em->persist($category);
        }

        // Flush to database
        $em->flush();

        $output->writeln("Created SUPER_NT_USER user <info>" . $email . "</info> successfully!");
    }
}