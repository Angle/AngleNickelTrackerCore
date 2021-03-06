<?php

namespace Angle\NickelTracker\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Angle\Common\UtilityBundle\Random\RandomUtility;

use Angle\NickelTracker\CoreBundle\Preset\Currency;

/**
 * @ORM\Entity
 * @ORM\Table(name="Transactions", indexes={@ORM\Index(name="date_idx", columns={"date"}), @ORM\Index(name="type_idx", columns={"type"}), @ORM\Index(name="source_amount_idx", columns={"sourceAmount"})})
 * @ORM\HasLifecycleCallbacks()
 */
class Transaction
{
    #########################
    ##        PRESETS      ##
    #########################

    const TYPE_INCOME   = 'I';
    const TYPE_EXPENSE  = 'E';
    const TYPE_TRANSFER = 'T';

    protected static $types = array(
        self::TYPE_INCOME   => 'Income',
        self::TYPE_EXPENSE  => 'Expense',
        self::TYPE_TRANSFER => 'Transfer'
    );

    #########################
    ##      PROPERTIES     ##
    #########################

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $transactionId;

    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    protected $type;

    /**
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=false)
     */
    protected $sourceAmount = 0;

    /**
     * @ORM\Column(type="smallint", nullable=false)
     * @see \Angle\NickelTracker\CoreBundle\Preset\Currency
     */
    protected $sourceCurrency;

    /**
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     */
    protected $destinationAmount = 0;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     * @see \Angle\NickelTracker\CoreBundle\Preset\Currency
     */
    protected $destinationCurrency;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $details;

    /**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $date;

    /**
     * Unique auto-generated code
     * @ORM\Column(type="string", nullable=false)
     */
    protected $code;

    // Flags
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $fiscal = false;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $extraordinary = false;


    #########################
    ## OBJECT RELATIONSHIP ##
    #########################

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="transactions")
     * @ORM\JoinColumn(name="userId", referencedColumnName="userId", nullable=false)
     */
    protected $userId;

    /**
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="sourceTransactions")
     * @ORM\JoinColumn(name="sourceAccountId", referencedColumnName="accountId", nullable=false)
     */
    protected $sourceAccountId;

    /**
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="destinationTransactions")
     * @ORM\JoinColumn(name="destinationAccountId", referencedColumnName="accountId", nullable=true)
     */
    protected $destinationAccountId;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="transactions")
     * @ORM\JoinColumn(name="categoryId", referencedColumnName="categoryId", nullable=true)
     */
    protected $categoryId;

    /**
     * @ORM\ManyToOne(targetEntity="Commerce", inversedBy="transactions")
     * @ORM\JoinColumn(name="commerceId", referencedColumnName="commerceId", nullable=true)
     */
    protected $commerceId;


    #########################
    ##     CONSTRUCTOR     ##
    #########################

    public function __construct()
    {
        $this->code = RandomUtility::generateString(16, true);
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
            throw new \RuntimeException("Transaction Type '" . $this->type . "' for Transaction ID " . $this->transactionId . " is invalid.");
        }

        return self::$types[$this->type];
    }

    public function getSourceCurrencyName()
    {
        return Currency::getCurrencyName($this->sourceCurrency);
    }

    public function getSourceCurrencyCode()
    {
        return Currency::getCurrencyCode($this->sourceCurrency);
    }

    public function getFormattedSourceAmount($full=false)
    {
        return Currency::formatMoney($this->sourceCurrency, $this->sourceAmount, $full);
    }

    public function getDestinationCurrencyName()
    {
        return Currency::getCurrencyName($this->destinationCurrency);
    }

    public function getDestinationCurrencyCode()
    {
        return Currency::getCurrencyCode($this->destinationCurrency);
    }

    public function getFormattedDestinationAmount($full=false)
    {
        return Currency::formatMoney($this->destinationCurrency, $this->destinationAmount, $full);
    }


    #########################
    ## GETTERS AND SETTERS ##
    #########################

    /**
     * @return integer
     */
    public function getTransactionId()
    {
        return $this->transactionId;
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
     * @return Transaction
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return float
     */
    public function getSourceAmount()
    {
        return $this->sourceAmount;
    }

    /**
     * @param float $sourceAmount
     * @return Transaction
     */
    public function setSourceAmount($sourceAmount)
    {
        $this->sourceAmount = $sourceAmount;
        return $this;
    }

    /**
     * @return int
     */
    public function getSourceCurrency()
    {
        return $this->sourceCurrency;
    }

    /**
     * @param int $sourceCurrency
     * @return Transaction
     */
    public function setSourceCurrency($sourceCurrency)
    {
        $this->sourceCurrency = $sourceCurrency;
        return $this;
    }

    /**
     * @return float
     */
    public function getDestinationAmount()
    {
        return $this->destinationAmount;
    }

    /**
     * @param float $destinationAmount
     * @return Transaction
     */
    public function setDestinationAmount($destinationAmount)
    {
        $this->destinationAmount = $destinationAmount;
        return $this;
    }

    /**
     * @return int
     */
    public function getDestinationCurrency()
    {
        return $this->destinationCurrency;
    }

    /**
     * @param int $destinationCurrency
     * @return Transaction
     */
    public function setDestinationCurrency($destinationCurrency)
    {
        $this->destinationCurrency = $destinationCurrency;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Transaction
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param string $details
     * @return Transaction
     */
    public function setDetails($details)
    {
        $this->details = $details;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return Transaction
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return Transaction
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return bool
     */
    public function getFiscal()
    {
        return $this->fiscal;
    }

    /**
     * @param bool $fiscal
     * @return Transaction
     */
    public function setFiscal($fiscal)
    {
        $this->fiscal = $fiscal;
        return $this;
    }

    /**
     * @return bool
     */
    public function getExtraordinary()
    {
        return $this->extraordinary;
    }

    /**
     * @param bool $extraordinary
     * @return Transaction
     */
    public function setExtraordinary($extraordinary)
    {
        $this->extraordinary = $extraordinary;
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
     * @return Transaction
     */
    public function setUserId(User $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Set sourceAccountId
     *
     * @param Account $accountId
     * @return Transaction
     */
    public function setSourceAccountId(Account $accountId)
    {
        $this->sourceAccountId = $accountId;
        $this->sourceCurrency = $accountId->getCurrency();

        return $this;
    }

    /**
     * Get sourceAccountId
     *
     * @return Account
     */
    public function getSourceAccountId()
    {
        return $this->sourceAccountId;
    }

    /**
     * Set destinationAccountId
     *
     * @param Account|null $accountId
     * @return Transaction
     */
    public function setDestinationAccountId(Account $accountId = null)
    {
        $this->destinationAccountId = $accountId;

        if ($accountId) {
            $this->destinationCurrency = $accountId->getCurrency();
        } else {
            $this->destinationCurrency = null;
        }

        return $this;
    }

    /**
     * Get destinationAccountId
     *
     * @return Account|null
     */
    public function getDestinationAccountId()
    {
        return $this->destinationAccountId;
    }

    /**
     * @return Category
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param Category|null $categoryId
     * @return Transaction
     */
    public function setCategoryId(Category $categoryId = null)
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    /**
     * @return Commerce|null
     */
    public function getCommerceId()
    {
        return $this->commerceId;
    }

    /**
     * @param Commerce|null $commerceId
     * @return Transaction
     */
    public function setCommerceId(Commerce $commerceId = null)
    {
        $this->commerceId = $commerceId;
        return $this;
    }


    #########################
    ##   HELPER FUNCTIONS  ##
    #########################

    // empty.

}