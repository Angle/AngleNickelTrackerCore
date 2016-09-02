<?php

namespace Angle\NickelTracker\CoreBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

use Angle\NickelTracker\CoreBundle\Entity\User;
use Angle\NickelTracker\CoreBundle\Entity\Account;
use Angle\NickelTracker\CoreBundle\Entity\Category;

class NickelTrackerService
{
    protected $doctrine;
    protected $em;

    protected $tokenStorage;
    protected $encoderFactory;

    /** @var User $user */
    protected $user;

    // Administrator Mode
    protected $adminMode = false;

    // Error Handling
    protected $errorType;
    protected $errorCode;
    protected $errorMessage;

    public function __construct(Doctrine $doctrine, TokenStorageInterface $tokenStorage, EncoderFactory $encoderFactory)
    {
        $this->doctrine = $doctrine;
        $this->em = $this->doctrine->getManager();

        // Attempt to load a session user
        $token = $tokenStorage->getToken();
        if ($token instanceof TokenInterface) {
            $this->user = $token->getUser();
        }

        $this->encoderFactory = $encoderFactory;
    }

    public function enableAdminMode($enable)
    {
        if ($enable) {
            // Destroy the session user and enable the flag
            $this->user = null;
            $this->adminMode = true;
        } else {
            $this->adminMode = false;
        }
    }

    /**
     * Get the last error that has occurred as an array. If no error has occurred, return null.
     * @return array|null
     */
    public function getError()
    {
        if ($this->errorType) {
            return array(
                'type' => $this->errorType,
                'code' => $this->errorCode,
                'message' => $this->errorMessage,
            );
        } else {
            return null;
        }
    }


    /**
     * Create a new User
     *
     * @param string $email User's valid email address
     * @param string $fullName User's full name
     * @param string $password User's un-hashed password
     * @return bool
     */
    public function createUser($email, $fullName, $password)
    {
        if (!$this->adminMode) {
            throw new \RuntimeException('Attempting to execute an Admin command without privileges');
        }

        // Check email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) { // email is invalid
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Invalid user email provided';
            return false;
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setRole('ROLE_NT_USER');
        $this->em->persist($user);

        /* @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
        $encoder = $this->encoderFactory->getEncoder($user);
        $encodedPassword = $encoder->encodePassword($password, $user->getSalt());
        $user->setPassword($encodedPassword);

        // Create base accounts
        $account = new Account();
        $account->setType(Account::TYPE_CASH);
        $account->setName('Cash');
        $account->setUserId($user);
        $this->em->persist($account);

        $account = new Account();
        $account->setType(Account::TYPE_DEBIT);
        $account->setName('Debit (Bank)');
        $account->setUserId($user);
        $this->em->persist($account);

        $account = new Account();
        $account->setType(Account::TYPE_CREDIT);
        $account->setName('Credit Card');
        $account->setUserId($user);
        $this->em->persist($account);

        // Create base categories
        $categories = array('Groceries', 'Restaurants', 'Gas', 'Entertainment', 'Party', 'Services');
        foreach ($categories as $c) {
            $category = new Category();
            $category->setName($c);
            $category->setUserId($user);
            $this->em->persist($category);
        }

        // Attempt to flush to the database
        try {
            $this->em->flush();
        } catch (DBALException $e) {
            $this->errorType = 'Doctrine';
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
            return false;
        }

        return true;
    }
}