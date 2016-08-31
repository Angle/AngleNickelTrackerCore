<?php

namespace Angle\NickelTracker\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Angle\Common\UtilityBundle\Random\RandomUtility;

/**
 * @ORM\Entity
 * @ORM\Table(name="Accounts")
 * @ORM\HasLifecycleCallbacks()
 */
class Account
{
    #########################
    ##        PRESETS      ##
    #########################

    protected static $types = array(
        'M' => 'Cash',
        'D' => 'Debit',
        'C' => 'Credit',
        'S' => 'Savings',
        'L' => 'Loaned',
    );

    #########################
    ##      PROPERTIES     ##
    #########################

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $accountId;

    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    protected $type;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $name;

    /**
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=false)
     */
    protected $balance;

    /**
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     */
    protected $creditLimit;


    #########################
    ## OBJECT RELATIONSHIP ##
    #########################

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="transactions")
     * @ORM\JoinColumn(name="userId", referencedColumnName="userId", nullable=false)
     */
    protected $userId;

    /**
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="sourceAccountId")
     */
    protected $sourceTransactions;

    /**
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="destinationAccountId")
     */
    protected $destinationTransactions;


    #########################
    ##     CONSTRUCTOR     ##
    #########################

    public function __construct()
    {
        $this->sourceTransactions = new ArrayCollection();
        $this->destinationTransactions = new ArrayCollection();
    }


    #########################
    ##    STATIC METHODS   ##
    #########################

    public static function getAvailableTypes()
    {
        return self::$types;
    }


    #########################
    ##   SPECIAL METHODS   ##
    #########################

    public function getTypeName()
    {
        if (!array_key_exists($this->type, self::$types)) {
            throw new \RuntimeException("Account Type '" . $this->type . "' for Account ID " . $this->accountId . " is invalid.");
        }

        return self::$types[$this->type];
    }


    #########################
    ## GETTERS AND SETTERS ##
    #########################

    /**
     * @return integer
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Account
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Account
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param float $balance
     * @return Account
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     * @return float
     */
    public function getCreditLimit()
    {
        return $this->creditLimit;
    }

    /**
     * @param float $creditLimit
     * @return Account
     */
    public function setCreditLimit($creditLimit)
    {
        $this->creditLimit = $creditLimit;
        return $this;
    }


    #########################
    ##  OBJECT REL: G & S  ##
    #########################

    /**
     * @return User
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param User $userId
     * @return Account
     */
    public function setUserId(User $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Add sourceTransactions
     *
     * @param Transaction $sourceTransactions
     * @return Account
     */
    public function addSourceTransaction(Transaction $sourceTransactions)
    {
        $this->sourceTransactions[] = $sourceTransactions;

        return $this;
    }

    /**
     * Remove sourceTransactions
     *
     * @param Transaction $sourceTransactions
     * @return Account
     */
    public function removeSourceTransaction(Transaction $sourceTransactions)
    {
        $this->sourceTransactions->removeElement($sourceTransactions);

        return $this;
    }

    /**
     * Get sourceTransactions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSourceTransactions()
    {
        return $this->sourceTransactions;
    }

    /**
     * Add destinationTransactions
     *
     * @param Transaction $destinationTransactions
     * @return Account
     */
    public function addDestinationTransaction(Transaction $destinationTransactions)
    {
        $this->destinationTransactions[] = $destinationTransactions;

        return $this;
    }

    /**
     * Remove sourceTransactions
     *
     * @param Transaction $destinationTransactions
     * @return Account
     */
    public function removeDestinationTransaction(Transaction $destinationTransactions)
    {
        $this->destinationTransactions->removeElement($destinationTransactions);

        return $this;
    }

    /**
     * Get destinationTransactions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDestinationTransactions()
    {
        return $this->destinationTransactions;
    }


    #########################
    ##   HELPER FUNCTIONS  ##
    #########################

    // empty.

}