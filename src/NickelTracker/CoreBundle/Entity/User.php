<?php

namespace NickelTracker\CoreBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="Users", indexes={@ORM\Index(name="email_idx", columns={"email"})})
 * TODO: check entity unique assert (or that)..
 */
class User implements UserInterface, \Serializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", unique=TRUE)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $userId;

    /**
     * @ORM\Column(type="string", unique=true, nullable=false)
     * TODO: ASSERT EMAIL
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $salt;

    /**
     * @ORM\Column(type="string", length=88)
     */
    private $password;

    /**
     * ROLE_SUPER_ADMIN
     * ROLE_ADVERTISER
     * ROLE_PROVIDER
     *
     * @ORM\Column(type="string", length=60)
     */
    private $role;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $timestamp;

    #########################
    ## OBJECT RELATIONSHIP ##
    #########################

    /**
     * @ORM\OneToMany(targetEntity="Account", mappedBy="userId")
     */
    protected $accounts;

    /**
     * @ORM\OneToMany(targetEntity="Account", mappedBy="userId")
     */
    protected $categories;

    /**
     * @ORM\OneToMany(targetEntity="Account", mappedBy="userId")
     */
    protected $transactions;


    #########################
    ##      CONSTRUCTOR    ##
    #########################

    public function __construct()
    {
        //$this->status = 'A';
        //$this->isActive = true;
        $this->salt = md5(uniqid(null, true));

        $this->accounts = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    #########################
    ##   SPECIAL METHODS   ##
    #########################

    /**
     * Get formatted timestamp
     *
     * @return string
     */
    public function getFormattedTimestamp()
    {
        /** @var \DateTime $date */
        $date = $this->timestamp;

        return $date->format('c');
    }

    /**
     * Set TimestampValue
     * @ORM\PrePersist
     * @return User
     */
    public function setTimestampValue()
    {
        $this->timestamp = new \Datetime("now");
        return $this;
    }


    #########################
    # USER_INTERFACE METHODS
    # Implemented from abstract class UserInterface
    #########################

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return array($this->role);
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {

    }

    /**
     * @inheritDoc
     */
    public function isEqualTo(User $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        return true;
    }


    #########################
    # SERIALIZABLE METHODS
    #########################

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(array(
            $this->userId,
        ));
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
            $this->userId,
            ) = unserialize($serialized);
    }

    public function equals(UserInterface $user)
    {
        return $this->getUsername() === $user->getUsername();
    }


    #########################
    ## GETTERs AND SETTERs ##
    #########################

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set role
     *
     * @param string $role
     * @return User
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Get Name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Name
     * @param string $name
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Get Timestamp
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }


    #########################
    ##  OBJECT REL: G & S  ##
    #########################

    /**
     * Add Account
     *
     * @param Account $accounts
     * @return User
     */
    public function addAccount(Account $accounts)
    {
        $this->accounts[] = $accounts;
        return $this;
    }

    /**
     * Remove Account
     *
     * @param Account $accounts
     */
    public function removeAccount(Account $accounts)
    {
        $this->accounts->removeElement($accounts);
    }

    /**
     * Get Accounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

}