<?php

namespace Angle\NickelTracker\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Angle\Common\UtilityBundle\Random\RandomUtility;

use Angle\NickelTracker\CoreBundle\Preset\Currency;

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

    const TYPE_CASH      = 'M';
    const TYPE_DEBIT     = 'D';
    const TYPE_CREDIT    = 'C';
    const TYPE_SAVINGS   = 'S';
    const TYPE_COUPON    = 'P';
    const TYPE_LOANED    = 'L';

    protected static $types = array(
        self::TYPE_CASH      => 'Cash',
        self::TYPE_DEBIT     => 'Debit',
        self::TYPE_CREDIT    => 'Credit',
        self::TYPE_SAVINGS   => 'Savings',
        self::TYPE_COUPON    => 'Coupon',
        self::TYPE_LOANED    => 'Loaned',
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
     * @ORM\Column(type="smallint", nullable=false)
     * @see \Angle\NickelTracker\CoreBundle\Preset\Currency
     */
    protected $currency = 1;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $name;

    /**
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=false)
     */
    protected $balance = 0;

    /**
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     */
    protected $creditLimit;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $deleted = false;


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

    public static function getAvailableCurrencies()
    {
        return Currency::availableCurrenciesFlat();
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

    public function getCurrencyName()
    {
        return Currency::getCurrencyName($this->currency);
    }

    public function getCurrencyCode()
    {
        return Currency::getCurrencyCode($this->currency);
    }

    public function getFormattedBalance($full=false)
    {
        return Currency::formatMoney($this->currency, $this->balance, $full);
    }

    public function getFormattedCreditLimit($full=false)
    {
        return Currency::formatMoney($this->currency, $this->creditLimit, $full);
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
     * @return int
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param int $currency
     * @return Account
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
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
        if ($creditLimit >= 0) {
            $this->creditLimit = $creditLimit;
        } else {
            $this->creditLimit = 0;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     * @return Account
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
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