<?php

namespace Angle\NickelTracker\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Angle\Common\UtilityBundle\Random\RandomUtility;

/**
 * @ORM\Entity
 * @ORM\Table(name="Categories", indexes={@ORM\Index(name="name_idx", columns={"name"})})
 * @ORM\HasLifecycleCallbacks()
 */
class Category
{
    #########################
    ##        PRESETS      ##
    #########################

    // none.


    #########################
    ##      PROPERTIES     ##
    #########################

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $categoryId;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $name;

    /**
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=false)
     */
    protected $budget;


    #########################
    ## OBJECT RELATIONSHIP ##
    #########################

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="transactions")
     * @ORM\JoinColumn(name="userId", referencedColumnName="userId", nullable=false)
     */
    protected $userId;

    /**
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="userId")
     * @ORM\OrderBy({"date" = "asc"})
     */
    protected $transactions;


    #########################
    ##     CONSTRUCTOR     ##
    #########################

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }


    #########################
    ##    STATIC METHODS   ##
    #########################

    // none.


    #########################
    ##   SPECIAL METHODS   ##
    #########################

    // none.

    #########################
    ## GETTERS AND SETTERS ##
    #########################

    /**
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->categoryId;
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
     * @return Category
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return float
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * @param float $budget
     * @return Category
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;
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
     * @return Category
     */
    public function setUserId(User $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Add transactions
     *
     * @param Transaction $transactions
     * @return Category
     */
    public function addTransaction(Transaction $transactions)
    {
        $this->transactions[] = $transactions;

        return $this;
    }

    /**
     * Remove transactions
     *
     * @param Transaction $transactions
     * @return Category
     */
    public function removeTransaction(Transaction $transactions)
    {
        $this->transactions->removeElement($transactions);

        return $this;
    }

    /**
     * Get transactions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }


    #########################
    ##   HELPER FUNCTIONS  ##
    #########################

    // empty.

}