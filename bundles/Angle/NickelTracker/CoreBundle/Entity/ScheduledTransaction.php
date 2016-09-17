<?php

namespace Angle\NickelTracker\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Angle\Common\UtilityBundle\Random\RandomUtility;
use Angle\NickelTracker\CoreBundle\Entity\Transaction;

/**
 * @ORM\Entity
 * @ORM\Table(name="ScheduledTransactions")
 * @ORM\HasLifecycleCallbacks()
 */
class ScheduledTransaction
{
    #########################
    ##        PRESETS      ##
    #########################

    // none. Inheriting from Transaction

    #########################
    ##      PROPERTIES     ##
    #########################

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $scheduledTransactionId;

    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    protected $type;

    /**
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=false)
     */
    protected $amount = 0;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $details;

    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $day;

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
        return Transaction::getAvailableTypes();
    }


    #########################
    ##   SPECIAL METHODS   ##
    #########################

    public function getTypeName()
    {
        if (!array_key_exists($this->type, Transaction::getAvailableTypes())) {
            throw new \RuntimeException("Scheduled Transaction Type '" . $this->type . "' for Scheduled Transaction ID " . $this->scheduledTransactionId . " is invalid.");
        }

        return Transaction::getAvailableTypes()[$this->type];
    }


    #########################
    ## GETTERS AND SETTERS ##
    #########################

    /**
     * @return integer
     */
    public function getTransactionId()
    {
        return $this->scheduledTransactionId;
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
     * @return ScheduledTransaction
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return ScheduledTransaction
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
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
     * @return ScheduledTransaction
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
     * @return ScheduledTransaction
     */
    public function setDetails($details)
    {
        $this->details = $details;
        return $this;
    }

    // TODO: Day

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return ScheduledTransaction
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
     * @return ScheduledTransaction
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
     * @return ScheduledTransaction
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
     * @return ScheduledTransaction
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
     * @return ScheduledTransaction
     */
    public function setSourceAccountId(Account $accountId)
    {
        $this->sourceAccountId = $accountId;

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
     * @return ScheduledTransaction
     */
    public function setDestinationAccountId(Account $accountId = null)
    {
        $this->destinationAccountId = $accountId;

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
     * @return ScheduledTransaction
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
     * @return ScheduledTransaction
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