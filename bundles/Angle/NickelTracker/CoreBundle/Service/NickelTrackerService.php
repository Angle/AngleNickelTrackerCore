<?php

namespace Angle\NickelTracker\CoreBundle\Service;


use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

use Angle\Common\UtilityBundle\Random\RandomUtility;

use Angle\NickelTracker\CoreBundle\Entity\User;
use Angle\NickelTracker\CoreBundle\Entity\Account;
use Angle\NickelTracker\CoreBundle\Entity\Category;
use Angle\NickelTracker\CoreBundle\Entity\Commerce;
use Angle\NickelTracker\CoreBundle\Entity\Transaction;
use Angle\NickelTracker\CoreBundle\Entity\ScheduledTransaction;

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

    /**
     * Operate on NickelTracker as an Admin with no user record attached.
     * @param bool $enable
     */
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
     * // Attempt to flush to the database
     * @return bool
     */
    public function flush()
    {
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

    public function flushAndUpdateBalances()
    {
        try {
            $this->em->flush();

            $updateBalanceSql = <<<ENDSQL
UPDATE Accounts as acc
INNER JOIN (
    SELECT
        a.accountId as `accountId`,
        a.type as `type`,
        a.name as `name`,
        (IFNULL(t1.amount,0) + IFNULL(t2.amount,0)) as `total`
    FROM Accounts as a
    LEFT JOIN (
        SELECT
            t.sourceAccountId as `accountId`,
            SUM(CASE
                WHEN t.type = 'I' THEN t.amount
                WHEN t.type = 'E' THEN t.amount*-1
                WHEN t.type = 'T' THEN t.amount*-1
                ELSE 0
                END) as `amount`
        FROM Transactions as t
        WHERE t.userId = :userId
        GROUP BY t.sourceAccountId
    ) as t1
        ON t1.accountId = a.accountId
    LEFT JOIN (
        SELECT
            t.destinationAccountId as `accountId`,
            SUM(CASE
                WHEN t.type = 'T' THEN t.amount
                ELSE 0
                END) as `amount`
        FROM Transactions as t
        WHERE t.userId = :userId
        AND t.destinationAccountId IS NOT NULL
        GROUP BY t.destinationAccountId
    ) as t2
        ON t2.accountId = a.accountId
    WHERE a.userId = :userId
    AND a.deleted = 0
) as calc ON acc.accountId = calc.accountId
SET acc.balance = calc.total
ENDSQL;

            $stmt = $this->em->getConnection()->prepare($updateBalanceSql);
            $stmt->bindValue('userId', $this->user->getUserId());
            $rows = $stmt->execute();

        } catch (DBALException $e) {
            $this->errorType = 'Doctrine';
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
            return false;
        }

        return true;
    }


    #####################################################################################################
    ###
    ###   USER METHODS (ADMIN MODE ONLY)
    ###

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

        if (!$this->flush()) {
            return false;
        } else {
            return $user->getUserId();
        }
    }


    public function overrideUserPassword(User $user, $newPassword)
    {
        if (!$this->adminMode) {
            throw new \RuntimeException('Attempting to execute an Admin command without privileges');
        }

        // Check if the user already exists in the database
        if (!$user->getUserId()) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Cannot operate on non-persisted objects';
            return false;
        }

        /* @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
        $encoder = $this->encoderFactory->getEncoder($user);

        // Update the password
        $user->refreshSalt();
        $encodedNewPassword = $encoder->encodePassword($newPassword, $user->getSalt());
        $user->setPassword($encodedNewPassword);

        $this->em->persist($user);

        return $this->flush();
    }

    public function disableUser(User $user)
    {
        if (!$this->adminMode) {
            throw new \RuntimeException('Attempting to execute an Admin command without privileges');
        }

        // Check if the user already exists in the database
        if (!$user->getUserId()) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Cannot operate on non-persisted objects';
            return false;
        }

        $user->setIsActive(false);
        $this->em->persist($user);
        return $this->flush();
    }

    public function enableUser(User $user)
    {
        if (!$this->adminMode) {
            throw new \RuntimeException('Attempting to execute an Admin command without privileges');
        }

        // Check if the user already exists in the database
        if (!$user->getUserId()) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Cannot operate on non-persisted objects';
            return false;
        }

        $user->setIsActive(true);
        $this->em->persist($user);
        return $this->flush();
    }


    #####################################################################################################
    ###
    ###   ACCOUNT METHODS
    ###

    /**
     * Return an ArrayCollection of the User's accounts
     * @return ArrayCollection
     */
    public function loadAccounts()
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Load all user accounts
        $repository = $this->doctrine->getRepository(Account::class);
        $accounts = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
            'deleted'   => false,
        ), array('name' => 'ASC')); // order by name

        return $accounts;
    }

    /**
     * Load a single account
     *
     * @param int $id Account ID
     * @return Account|false
     */
    public function loadAccount($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Account
        $repository = $this->doctrine->getRepository(Account::class);
        /** @var Account $account */
        $account = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'accountId' => $id,
            'deleted'   => false,
        ));

        if (!$account) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Account not found';
            return false;
        }

        return $account;
    }

    /**
     * Create a new account for the user
     *
     * @param string $type Account type
     * @param string $name Account name
     * @param float $creditLimit Account's credit limit (only used for Credit accounts)
     * @return int|false AccountID created
     */
    public function createAccount($type, $name, $creditLimit=null)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Check if the user already has an account with that name
        $repository = $this->doctrine->getRepository(Account::class);
        $accounts = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
            'name'      => $name,
            'deleted'   => false
        ));

        if (!empty($accounts)) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Cannot have two accounts with the same name';
            return false;
        }

        if (!array_key_exists($type, Account::getAvailableTypes())) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Invalid account type provided';
            return false;
        }

        $account = new Account();
        $account->setType($type);
        $account->setName($name);
        $account->setUserId($this->user);
        $account->setCreditLimit($creditLimit);
        $this->em->persist($account);

        if (!$this->flush()) {
            return false;
        } else {
            return $account->getAccountId();
        }
    }

    /**
     * Change the name of a user's account
     *
     * @param int $id Account ID
     * @param string $newName Desired new name for the account
     * @return bool
     */
    public function changeAccountName($id, $newName)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Check if the user already has an account with that name
        $repository = $this->doctrine->getRepository(Account::class);
        $accounts = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
            'name'      => $newName,
            'deleted'   => false
        ));

        if (!empty($accounts)) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Cannot have two accounts with the same name';
            return false;
        }

        // Attempt to load the Account
        /** @var Account $account */
        $account = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'accountId' => $id,
            'deleted'   => false,
        ));

        if (!$account) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Account not found';
            return false;
        }

        $account->setName($newName);

        return $this->flush();
    }

    /**
     * Change the Credit Limit for the account
     *
     * @param int $id Account ID
     * @param float $newLimit Desired new limit for the account
     * @return bool
     */
    public function changeAccountCreditLimit($id, $newLimit)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Account
        $repository = $this->doctrine->getRepository(Account::class);
        /** @var Account $account */
        $account = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'accountId' => $id,
            'deleted'   => false,
        ));

        if (!$account) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Account not found';
            return false;
        }

        if ($account->getType() != Account::TYPE_CREDIT) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Cannot set a Credit Limit for non-credit accounts';
            return false;
        }

        $account->setCreditLimit($newLimit);

        return $this->flush();
    }

    /**
     * Delete a user's account
     *
     * @param int $id Account ID
     * @return bool
     */
    public function deleteAccount($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Account
        $repository = $this->doctrine->getRepository(Account::class);
        /** @var Account $account */
        $account = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'accountId' => $id,
            'deleted'   => false,
        ));

        if (!$account) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Account not found';
            return false;
        }

        $account->setDeleted(true);

        // Also change the account name so that a new one can be created with the same name
        $account->setName($account->getName() . '_DELETED_' . RandomUtility::generateString(6));

        return $this->flush();
    }

    #####################################################################################################
    ###
    ###   CATEGORY METHODS
    ###

    /**
     * Return an ArrayCollection of the User's categories
     * @return ArrayCollection
     */
    public function loadCategories()
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Load all user categories
        $repository = $this->doctrine->getRepository(Category::class);
        $categories = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
        ), array('name' => 'ASC')); // order by name

        return $categories;
    }

    /**
     * Load a single category
     *
     * @param int $id Category ID
     * @return Category|false
     */
    public function loadCategory($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Category
        $repository = $this->doctrine->getRepository(Category::class);
        /** @var Category $category */
        $category = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'categoryId' => $id,
        ));

        if (!$category) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Category not found';
            return false;
        }

        return $category;
    }

    /**
     * Create a new category for the user
     *
     * @param string $name Category name
     * @param float $budget Category monthly budget
     * @return int|false Category ID created
     */
    public function createCategory($name, $budget)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Check if the user already has an category with that name
        $repository = $this->doctrine->getRepository(Category::class);
        $categories = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
            'name'      => $name,
        ));

        if (!empty($categories)) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Cannot have two categories with the same name';
            return false;
        }

        // Validate the budget
        if ($budget < 0) {
            $budget = 0;
        }

        $category = new Category();
        $category->setName($name);
        $category->setBudget($budget);
        $category->setUserId($this->user);
        $this->em->persist($category);

        if (!$this->flush()) {
            return false;
        } else {
            return $category->getCategoryId();
        }
    }

    /**
     * Change the name of a user's category
     *
     * @param int $id Category ID
     * @param string $newName Desired new name for the category
     * @return bool
     */
    public function changeCategoryName($id, $newName)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Check if the user already has an category with that name
        $repository = $this->doctrine->getRepository(Category::class);
        $categories = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
            'name'      => $newName,
        ));

        if (!empty($categories)) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Cannot have two categories with the same name';
            return false;
        }

        // Attempt to load the Category
        /** @var Category $category */
        $category = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'categoryId' => $id,
        ));

        if (!$category) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Category not found';
            return false;
        }

        $category->setName($newName);

        return $this->flush();
    }

    /**
     * Change the Budget for the category
     *
     * @param int $id Category ID
     * @param float $newBudget Desired new budget for the category
     * @return bool
     */
    public function changeCategoryBudget($id, $newBudget)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Category
        $repository = $this->doctrine->getRepository(Category::class);
        /** @var Category $category */
        $category = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'categoryId' => $id,
        ));

        if (!$category) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Category not found';
            return false;
        }

        // Validate the new budget
        if ($newBudget < 0) {
            $newBudget = 0;
        }

        $category->setBudget($newBudget);

        return $this->flush();
    }

    /**
     * Delete a user's category
     *
     * @param int $id Category ID
     * @return bool
     */
    public function deleteCategory($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Category
        $repository = $this->doctrine->getRepository(Category::class);
        /** @var Category $category */
        $category = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'categoryId' => $id,
        ));

        if (!$category) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Category not found';
            return false;
        }

        // Load all the user's transactions where the category was used
        $repository = $this->doctrine->getRepository(Transaction::class);
        $transactions = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
            'categoryId' => $id,
        ));

        // Remove the reference to the category from each transaction
        foreach ($transactions as $transaction) {
            /** @var Transaction $transaction */
            $transaction->setCategoryId(null);
        }

        // Now delete the category
        $this->em->remove($category);

        return $this->flush();
    }

    #####################################################################################################
    ###
    ###   COMMERCE METHODS
    ###

    /**
     * Return an ArrayCollection of the User's commerces
     * @return ArrayCollection
     */
    public function loadCommerces()
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Load all user categories
        $repository = $this->doctrine->getRepository(Commerce::class);
        $commerces = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
        ), array('name' => 'ASC')); // order by name

        return $commerces;
    }

    /**
     * Load a single commerce
     *
     * @param int $id Commerce ID
     * @return Commerce|false
     */
    public function loadCommerce($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Category
        $repository = $this->doctrine->getRepository(Commerce::class);
        /** @var Commerce $commerce */
        $commerce = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'commerceId' => $id,
        ));

        if (!$commerce) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Commerce not found';
            return false;
        }

        return $commerce;
    }

    /**
     * Change the name of a user's commerce
     *
     * @param int $id Commerce ID
     * @param string $newName Desired new name for the commerce
     * @return bool
     */
    public function changeCommerceName($id, $newName)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Check if the user already has an commerce with that name
        $repository = $this->doctrine->getRepository(Commerce::class);
        $commerces = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
            'name'      => $newName,
        ));

        if (!empty($commerces)) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Cannot have two commerces with the same name';
            return false;
        }

        // Attempt to load the Commerce
        /** @var Commerce $commerce */
        $commerce = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'commerceId' => $id,
        ));

        if (!$commerce) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Commerce not found';
            return false;
        }

        $commerce->setName($newName);

        return $this->flush();
    }

    /**
     * Delete a user's commerce
     *
     * @param int $id Commerce ID
     * @return bool
     */
    public function deleteCommerce($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Commerce
        $repository = $this->doctrine->getRepository(Commerce::class);
        /** @var Commerce $commerce */
        $commerce = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'commerceId' => $id,
        ));

        if (!$commerce) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Commerce not found';
            return false;
        }

        // Load all the user's transactions where the commerce was used
        $repository = $this->doctrine->getRepository(Transaction::class);
        $transactions = $repository->findBy(array(
            'userId'    => $this->user->getUserId(),
            'commerceId' => $id,
        ));

        // Remove the reference to the category from each transaction
        foreach ($transactions as $transaction) {
            /** @var Transaction $transaction */
            $transaction->setCommerceId(null);
        }

        // Now delete the category
        $this->em->remove($commerce);

        return $this->flush();
    }

    #####################################################################################################
    ###
    ###   TRANSACTION METHODS
    ###

    /**
     * Return an ArrayCollection of the User's transactions
     * @param array $filters
     * @return ArrayCollection
     */
    public function loadTransactions(array $filters = array())
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Load the user's transactions
        /* @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->doctrine->getRepository(Transaction::class);
        $query = $repository->createQueryBuilder('e')
            ->select('e AS transaction')
            ->addSelect('src.name AS sourceAccount')
            ->addSelect('dst.name AS destinationAccount')
            ->addSelect('cat.name AS category')
            ->addSelect('com.name AS commerce')
            ->leftJoin('e.sourceAccountId', 'src')
            ->leftJoin('e.destinationAccountId', 'dst')
            ->leftJoin('e.categoryId', 'cat')
            ->leftJoin('e.commerceId', 'com')
            ->where("e.userId = :userId")
            ->orderBy('e.date','DESC')
            ->setParameter('userId', $this->user->getUserId());

        // Apply filters
        if (array_key_exists('accountId', $filters) && $filters['accountId']) {
            $query->andWhere('e.sourceAccountId = :accountId OR e.destinationAccountId = :accountId')
                ->setParameter('accountId', $filters['accountId']);
        }
        if (array_key_exists('categoryId', $filters) && $filters['categoryId']) {
            $query->andWhere('e.categoryId = :categoryId')
                ->setParameter('categoryId', $filters['categoryId']);
        }
        if (array_key_exists('startDate', $filters) && $filters['startDate']) {
            $query->andWhere('e.date >= :startDate')
                ->setParameter('startDate', $filters['startDate']);
        }
        if (array_key_exists('endDate', $filters) && $filters['endDate']) {
            $query->andWhere('e.date <= :endDate')
                ->setParameter('endDate', $filters['endDate']);
        }

        // Search string
        if (array_key_exists('searchString', $filters) && $filters['searchString']) {
            $query->andWhere('com.name LIKE :searchString OR e.description LIKE :searchString OR e.details LIKE :searchString')
                ->setParameter('searchString', '%'.$filters['searchString'].'%');
        }

        $transactions = $query->getQuery()->getResult();

        return $transactions;
    }

    /**
     * Load a single transaction
     *
     * @param int $id Transaction ID
     * @return Transaction|false
     */
    public function loadTransaction($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Transaction
        $repository = $this->doctrine->getRepository(Transaction::class);
        /** @var Transaction $transaction */
        $transaction = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'transactionId' => $id,
        ));

        if (!$transaction) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Transaction not found';
            return false;
        }

        return $transaction;
    }

    /**
     * Create a new income transaction
     *
     * @param int $transactionId Transaction ID (if it exists, null if new)
     * @param int $sourceAccountId Source Account ID
     * @param string $description Transaction description
     * @param string|null $details Transaction details
     * @param float $amount Transaction amount
     * @param \DateTime $date Transaction date
     * @param array|null $flags Optionals transaction flags
     * @return int|false TransactionID created
     */
    public function processIncomeTransaction($transactionId, $sourceAccountId, $description, $details, $amount, \DateTime $date, $flags=array())
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Transaction
        $transaction = $this->doctrine->getRepository(Transaction::class)
            ->findOneBy(array(
                'userId'        => $this->user->getUserId(),
                'transactionId' => $transactionId
            ));

        // If the transaction was not found (invalid ID) then initialize one
        if (!$transaction) {
            $transaction = new Transaction();
        }

        // Attempt to load the Source Account
        $repository = $this->doctrine->getRepository(Account::class);
        /** @var Account $sourceAccount */
        $sourceAccount = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'accountId' => $sourceAccountId,
            'deleted'   => false,
        ));

        if (!$sourceAccountId) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Source Account not found';
            return false;
        }

        if (!$amount) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Amount cannot be zero';
            return false;
        }


        // Process the transaction
        $transaction->setType(Transaction::TYPE_INCOME);
        $transaction->setSourceAccountId($sourceAccount);
        $transaction->setDescription($description);
        $transaction->setDetails($details);
        $transaction->setAmount($amount);
        $transaction->setDate($date);

        // Transaction Flags
        if (array_key_exists('fiscal', $flags)) {
            $transaction->setFiscal($flags['fiscal']);
        } else {
            $transaction->setFiscal(false);
        }
        if (array_key_exists('extraordinary', $flags)) {
            $transaction->setExtraordinary($flags['extraordinary']);
        } else {
            $transaction->setExtraordinary(false);
        }

        // Set the User ID
        $transaction->setUserId($this->user);

        $this->em->persist($transaction);

        if (!$this->flushAndUpdateBalances()) {
            return false;
        } else {
            return $transaction->getTransactionId();
        }
    }

    /**
     * Create a new expense transaction
     *
     * @param int $transactionId Transaction ID (if it exists, null if new)
     * @param int $sourceAccountId Source Account ID
     * @param int|null $categoryId Category ID
     * @param string|null $commerceName Commerce name string
     * @param string $description Transaction description
     * @param string|null $details Transaction details
     * @param float $amount Transaction amount
     * @param \DateTime $date Transaction date
     * @param array|null $flags Optionals transaction flags
     * @return int|false TransactionID created
     */
    public function processExpenseTransaction($transactionId, $sourceAccountId, $categoryId, $commerceName, $description, $details, $amount, \DateTime $date, $flags=array())
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Transaction
        $transaction = $this->doctrine->getRepository(Transaction::class)
            ->findOneBy(array(
                'userId'        => $this->user->getUserId(),
                'transactionId' => $transactionId
            ));

        // If the transaction was not found (invalid ID) then initialize one
        if (!$transaction) {
            $transaction = new Transaction();
        }

        // Attempt to load the Source Account
        $repository = $this->doctrine->getRepository(Account::class);
        /** @var Account $sourceAccount */
        $sourceAccount = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'accountId' => $sourceAccountId,
            'deleted'   => false,
        ));

        if (!$sourceAccountId) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Source Account not found';
            return false;
        }

        if ($categoryId) {
            // Attempt to load the Category
            $repository = $this->doctrine->getRepository(Category::class);
            /** @var Category $category */
            $category = $repository->findOneBy(array(
                'userId'    => $this->user->getUserId(),
                'categoryId' => $categoryId,
            ));

            if (!$category) {
                $this->errorType = 'NickelTracker';
                $this->errorCode = 1;
                $this->errorMessage = 'Category not found';
                return false;
            }
        } else {
            $category = null;
        }

        if ($commerceName) {
            // Attempt to load the Commerce
            $repository = $this->doctrine->getRepository(Commerce::class);
            /** @var Commerce $commerce */
            $commerce = $repository->findOneBy(array(
                'userId'    => $this->user->getUserId(),
                'name'      => $commerceName,
            ));

            // If no commerce was found with the same name, create a new one
            if (!$commerce) {
                $commerce = new Commerce();
                $commerce->setUserId($this->user);
                $commerce->setName($commerceName);
                $this->em->persist($commerce);
            }
        } else {
            $commerce = null;
        }

        if (!$amount) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Amount cannot be zero';
            return false;
        }


        // Process the transaction
        $transaction->setType(Transaction::TYPE_EXPENSE);
        $transaction->setSourceAccountId($sourceAccount);
        $transaction->setCategoryId($category);
        $transaction->setCommerceId($commerce);
        $transaction->setDescription($description);
        $transaction->setDetails($details);
        $transaction->setAmount($amount);
        $transaction->setDate($date);

        // Transaction Flags
        if (array_key_exists('fiscal', $flags)) {
            $transaction->setFiscal($flags['fiscal']);
        } else {
            $transaction->setFiscal(false);
        }
        if (array_key_exists('extraordinary', $flags)) {
            $transaction->setExtraordinary($flags['extraordinary']);
        } else {
            $transaction->setExtraordinary(false);
        }

        // Set the User ID
        $transaction->setUserId($this->user);

        $this->em->persist($transaction);

        if (!$this->flushAndUpdateBalances()) {
            return false;
        } else {
            return $transaction->getTransactionId();
        }
    }

    /**
     * Create a new transfer transaction
     *
     * @param int $transactionId Transaction ID (if it exists, null if new)
     * @param int $sourceAccountId Source Account ID
     * @param int $destinationAccountId Destination Account ID
     * @param string $description Transaction description
     * @param string|null $details Transaction details
     * @param float $amount Transaction amount
     * @param \DateTime $date Transaction date
     * @param array|null $flags Optionals transaction flags
     * @return int|false TransactionID created
     */
    public function processTransferTransaction($transactionId, $sourceAccountId, $destinationAccountId, $description, $details, $amount, \DateTime $date, $flags=array())
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Transaction
        $transaction = $this->doctrine->getRepository(Transaction::class)
            ->findOneBy(array(
                'userId'        => $this->user->getUserId(),
                'transactionId' => $transactionId
            ));

        // If the transaction was not found (invalid ID) then initialize one
        if (!$transaction) {
            $transaction = new Transaction();
        }

        // Attempt to load the Source Account
        $repository = $this->doctrine->getRepository(Account::class);
        /** @var Account $sourceAccount */
        $sourceAccount = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'accountId' => $sourceAccountId,
            'deleted'   => false,
        ));

        if (!$sourceAccountId) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Source Account not found';
            return false;
        }

        // Attempt to load the Destination Account
        /** @var Account $destinationAccount */
        $destinationAccount = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'accountId' => $destinationAccountId,
            'deleted'   => false,
        ));

        if (!$destinationAccount) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Destination Account not found';
            return false;
        }

        if (!$amount || $amount < 0) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Amount cannot be less than or equal to zero';
            return false;
        }


        // Process the transaction
        $transaction->setType(Transaction::TYPE_TRANSFER);
        $transaction->setSourceAccountId($sourceAccount);
        $transaction->setDestinationAccountId($destinationAccount);
        $transaction->setDescription($description);
        $transaction->setDetails($details);
        $transaction->setAmount($amount);
        $transaction->setDate($date);

        // Transaction Flags
        if (array_key_exists('fiscal', $flags)) {
            $transaction->setFiscal($flags['fiscal']);
        } else {
            $transaction->setFiscal(false);
        }
        if (array_key_exists('extraordinary', $flags)) {
            $transaction->setExtraordinary($flags['extraordinary']);
        } else {
            $transaction->setExtraordinary(false);
        }

        // Set the User ID
        $transaction->setUserId($this->user);

        $this->em->persist($transaction);

        if (!$this->flushAndUpdateBalances()) {
            return false;
        } else {
            return $transaction->getTransactionId();
        }
    }

    /**
     * Delete a user's transactions
     *
     * @param int $id Transaction ID
     * @return bool
     */
    public function deleteTransaction($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Transaction
        $repository = $this->doctrine->getRepository(Transaction::class);
        /** @var Transaction $transaction */
        $transaction = $repository->findOneBy(array(
            'userId'        => $this->user->getUserId(),
            'transactionId' => $id,
        ));

        if (!$transaction) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Transaction not found';
            return false;
        }

        // Now delete the transaction
        $this->em->remove($transaction);

        return $this->flushAndUpdateBalances();
    }

    #####################################################################################################
    ###
    ###   SCHEDULED TRANSACTION METHODS
    ###

    /**
     * Return an ArrayCollection of the User's Scheduled Transactions
     * @return ArrayCollection
     */
    public function loadScheduledTransactions()
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Load the user's scheduled transactions
        /* @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->doctrine->getRepository(ScheduledTransaction::class);
        $query = $repository->createQueryBuilder('e')
            ->select('e AS transaction')
            ->addSelect('src.name AS sourceAccount')
            ->addSelect('dst.name AS destinationAccount')
            ->addSelect('cat.name AS category')
            ->addSelect('com.name AS commerce')
            ->leftJoin('e.sourceAccountId', 'src')
            ->leftJoin('e.destinationAccountId', 'dst')
            ->leftJoin('e.categoryId', 'cat')
            ->leftJoin('e.commerceId', 'com')
            ->where("e.userId = :userId")
            ->orderBy('e.day','ASC')
            ->setParameter('userId', $this->user->getUserId());

        $transactions = $query->getQuery()->getResult();

        return $transactions;
    }

    /**
     * Load a single scheduled transaction
     *
     * @param int $id Scheduled Transaction ID
     * @return ScheduledTransaction|false
     */
    public function loadScheduledTransaction($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Transaction
        $repository = $this->doctrine->getRepository(ScheduledTransaction::class);
        /** @var ScheduledTransaction $transaction */
        $transaction = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'scheduledTransactionId' => $id,
        ));

        if (!$transaction) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Scheduled Transaction not found';
            return false;
        }

        return $transaction;
    }

    /**
     * Create a new expense transaction
     *
     * @param int $transactionId Scheduled Transaction ID (if it exists, null if new)
     * @param string $type Scheduled Transaction type
     * @param int $sourceAccountId Source Account ID
     * @param int $destinationAccountId Destination Account ID
     * @param int|null $categoryId Category ID
     * @param string|null $commerceName Commerce name string
     * @param string $description Transaction description
     * @param string|null $details Transaction details
     * @param float $amount Transaction amount
     * @param int $day Recurring transaction day
     * @param array|null $flags Optionals transaction flags
     * @return int|false TransactionID created
     */
    public function processScheduledTransaction($transactionId, $type, $sourceAccountId, $destinationAccountId, $categoryId, $commerceName, $description, $details, $amount, $day, $flags=array())
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Scheduled Transaction
        $transaction = $this->doctrine->getRepository(ScheduledTransaction::class)
            ->findOneBy(array(
                'userId'        => $this->user->getUserId(),
                'scheduledTransactionId' => $transactionId
            ));

        // If the transaction was not found (invalid ID) then initialize one
        if (!$transaction) {
            $transaction = new ScheduledTransaction();
        }

        ## VALIDATE COMMON PROPERTIES
        // Check if transaction type exists
        if (!array_key_exists($type, ScheduledTransaction::getAvailableTypes())) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Invalid Scheduled Transaction type';
            return false;
        }

        // Check the provided day
        if ($day < 1 || $day > 31) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Invalid recurring day selected';
            return false;
        }

        // Attempt to load the Source Account
        $repository = $this->doctrine->getRepository(Account::class);
        /** @var Account $sourceAccount */
        $sourceAccount = $repository->findOneBy(array(
            'userId'    => $this->user->getUserId(),
            'accountId' => $sourceAccountId,
            'deleted'   => false,
        ));

        if (!$sourceAccountId) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Source Account not found';
            return false;
        }

        // Check the transaction amount
        if (!$amount) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Amount cannot be zero';
            return false;
        }

        ## VALIDATE SPECIAL PROPERTIES FOR EACH TYPE
        if ($type == 'E') {
            // Expense properties
            if ($categoryId) {
                // Attempt to load the Category
                $repository = $this->doctrine->getRepository(Category::class);
                /** @var Category $category */
                $category = $repository->findOneBy(array(
                    'userId'    => $this->user->getUserId(),
                    'categoryId' => $categoryId,
                ));

                if (!$category) {
                    $this->errorType = 'NickelTracker';
                    $this->errorCode = 1;
                    $this->errorMessage = 'Category not found';
                    return false;
                }
            } else {
                $category = null;
            }

            if ($commerceName) {
                // Attempt to load the Commerce
                $repository = $this->doctrine->getRepository(Commerce::class);
                /** @var Commerce $commerce */
                $commerce = $repository->findOneBy(array(
                    'userId'    => $this->user->getUserId(),
                    'name'      => $commerceName,
                ));

                // If no commerce was found with the same name, create a new one
                if (!$commerce) {
                    $commerce = new Commerce();
                    $commerce->setUserId($this->user);
                    $commerce->setName($commerceName);
                    $this->em->persist($commerce);
                }
            } else {
                $commerce = null;
            }
        } else {
            $category = null;
            $commerce = null;
        }

        if ($type == 'T') {
            // Attempt to load the Destination Account
            /** @var Account $destinationAccount */
            $destinationAccount = $repository->findOneBy(array(
                'userId'    => $this->user->getUserId(),
                'accountId' => $destinationAccountId,
                'deleted'   => false,
            ));

            if (!$destinationAccount) {
                $this->errorType = 'NickelTracker';
                $this->errorCode = 1;
                $this->errorMessage = 'Destination Account not found';
                return false;
            }

            if (!$amount || $amount < 0) {
                $this->errorType = 'NickelTracker';
                $this->errorCode = 1;
                $this->errorMessage = 'Amount cannot be less than or equal to zero';
                return false;
            }
        } else {
            $destinationAccount = null;
        }

        // Process the transaction
        $transaction->setType($type);
        $transaction->setSourceAccountId($sourceAccount);
        $transaction->setDestinationAccountId($destinationAccount);
        $transaction->setCategoryId($category);
        $transaction->setCommerceId($commerce);
        $transaction->setDescription($description);
        $transaction->setDetails($details);
        $transaction->setAmount($amount);
        $transaction->setDay($day);

        // Transaction Flags
        if (array_key_exists('fiscal', $flags)) {
            $transaction->setFiscal($flags['fiscal']);
        } else {
            $transaction->setFiscal(false);
        }
        if (array_key_exists('extraordinary', $flags)) {
            $transaction->setExtraordinary($flags['extraordinary']);
        } else {
            $transaction->setExtraordinary(false);
        }

        // Set the User ID
        $transaction->setUserId($this->user);

        $this->em->persist($transaction);

        if (!$this->flush()) {
            return false;
        } else {
            return $transaction->getScheduledTransactionId();
        }
    }

    /**
     * Safe-delete an scheduled transaction
     *
     * @param int $id Transaction ID
     * @return bool
     */
    public function deleteScheduledTransaction($id)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        // Attempt to load the Scheduled Transaction
        $repository = $this->doctrine->getRepository(ScheduledTransaction::class);
        /** @var ScheduledTransaction $transaction */
        $transaction = $repository->findOneBy(array(
            'userId'        => $this->user->getUserId(),
            'scheduledTransactionId' => $id,
        ));

        if (!$transaction) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'Scheduled Transaction not found';
            return false;
        }

        // TODO: Scheduled transaction reference
        // Load all the user's transactions where the Scheduled Transaction was used
        $repository = $this->doctrine->getRepository(Transaction::class);
        $transactions = $repository->findBy(array(
            'userId'                 => $this->user->getUserId(),
            'scheduledTransactionId' => $id,
        ));

        // Remove the reference to the ScheduledTransaction from each transaction
        foreach ($transactions as $transaction) {
            /** @var Transaction $transaction */
            $transaction->setScheduledTransactionId(null);
        }

        // Now delete the Scheduled transaction
        $this->em->remove($transaction);

        return $this->flush();
    }

    #####################################################################################################
    ###
    ###   USER METHODS
    ###

    public function loadUser()
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        return $this->user;
    }

    public function changeUserPassword($oldPassword, $newPassword)
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        /* @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
        $encoder = $this->encoderFactory->getEncoder($this->user);

        // First check if the old password matches the records
        if (!$encoder->isPasswordValid($this->user->getPassword(), $oldPassword, $this->user->getSalt())) {
            $this->errorType = 'NickelTracker';
            $this->errorCode = 1;
            $this->errorMessage = 'User password mismatch';
            return false;
        }

        // Now update the password
        $this->user->refreshSalt();
        $encodedNewPassword = $encoder->encodePassword($newPassword, $this->user->getSalt());
        $this->user->setPassword($encodedNewPassword);

        $this->em->persist($this->user);

        return $this->flush();
    }


    #####################################################################################################
    ###
    ###   REPORTS
    ###

    /**
     * Generate the dashboard info for a user
     * 
     * @return array
     */
    public function loadDashboard()
    {
        if (!$this->user) {
            throw new \RuntimeException('Session user was not found');
        }

        $dashboard = array();

        $today = new \DateTime("now", new \DateTimeZone('America/Monterrey'));

        $dashboard['currentDay']    = intval($today->format('j'));
        $dashboard['lastDay']       = intval($today->format('t'));

        // Hacky way to get the first and last dates of this month, based on the user's timezone
        $firstDayOfMonth = $today->format('Y-m') . '-01';
        $lastDayOfMonth = $today->format('Y-m-t');

        // Query the Transactions table to obtain general results of the user's budget and spending
        $dashboardTransactions = <<<ENDSQL
SELECT
    IFNULL(SUM(CASE WHEN t.type = 'I' THEN t.amount ELSE 0 END), 0) as `income`,
    IFNULL(SUM(CASE WHEN t.type = 'E' THEN t.amount ELSE 0 END), 0) as `expense`
FROM Transactions as t
WHERE t.userId = :userId
AND t.date >= :firstDayOfMonth
AND t.date <= :lastDayOfMonth
AND t.extraordinary = 0
ENDSQL;

        $stmt = $this->em->getConnection()->prepare($dashboardTransactions);
        $stmt->bindValue('userId', $this->user->getUserId());
        $stmt->bindValue('firstDayOfMonth', $firstDayOfMonth);
        $stmt->bindValue('lastDayOfMonth', $lastDayOfMonth);
        $stmt->execute();
        $result = $stmt->fetch();

        if (!$result) {
            $dashboard['income'] = 0;
            $dashboard['expense'] = 0;
        } else {
            $dashboard['income'] = $result['income'];
            $dashboard['expense'] = $result['expense'];
        }


        // Query the Accounts table to obtain general results of the user's accounts
        $dashboardAccounts = <<<ENDSQL
SELECT
    IFNULL(SUM(CASE WHEN a.type = 'M' THEN a.balance ELSE 0 END), 0) as `cash`,
    IFNULL(SUM(CASE WHEN a.type = 'D' THEN a.balance ELSE 0 END), 0) as `debit`,
    IFNULL(SUM(CASE WHEN a.type = 'C' THEN a.balance ELSE 0 END), 0) as `credit`,
    IFNULL(SUM(CASE WHEN a.type = 'S' THEN a.balance ELSE 0 END), 0) as `savings`,
    IFNULL(SUM(CASE WHEN a.type = 'L' THEN a.balance ELSE 0 END), 0) as `loaned`
FROM Accounts as a
WHERE a.userId = :userId
AND a.deleted = 0
ENDSQL;

        $stmt = $this->em->getConnection()->prepare($dashboardAccounts);
        $stmt->bindValue('userId', $this->user->getUserId());
        $stmt->execute();
        $result = $stmt->fetch();

        $dashboard['accounts'] = $result;

        // Query the Transactions table again to obtain general results of categories and expenditure
        $dashboardCategories = <<<ENDSQL
SELECT
	c.categoryId as `categoryId`,
	c.name as `name`,
	IFNULL(c.budget,0) as `budget`,
	IFNULL(tr.expense,0) as `expense`
FROM Categories as c
LEFT JOIN (
	SELECT
		t.categoryId as `categoryId`,
		SUM(t.amount) as `expense`
	FROM Transactions as t
	WHERE t.userId = :userId
        AND t.type = 'E'
        AND t.date >= :firstDayOfMonth
        AND t.date <= :lastDayOfMonth
        AND t.extraordinary = 0
	GROUP BY t.categoryId
) as tr ON c.categoryId = tr.categoryId
WHERE c.userId = :userId

UNION

SELECT
	null as `categoryId`,
	'-- No Category --' as `name`,
	0.0 as `budget`,
	SUM(t.amount) as `expense`
FROM Transactions as t
WHERE t.userId = :userId
    AND t.categoryId IS NULL
    AND t.type = 'E'
    AND t.date >= :firstDayOfMonth
    AND t.date <= :lastDayOfMonth
    AND t.extraordinary = 0

ORDER BY expense DESC, name ASC
ENDSQL;

        $stmt = $this->em->getConnection()->prepare($dashboardCategories);
        $stmt->bindValue('userId', $this->user->getUserId());
        $stmt->bindValue('firstDayOfMonth', $firstDayOfMonth);
        $stmt->bindValue('lastDayOfMonth', $lastDayOfMonth);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $dashboard['categories'] = $result;

        // Sum the budget for each category to get the global
        $dashboard['budget'] = 0;
        foreach ($dashboard['categories'] as $row) {
            $dashboard['budget'] += $row['budget'];
        }

        return $dashboard;
    }

}