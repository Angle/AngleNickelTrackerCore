<?php

namespace Angle\NickelTracker\CoreBundle\Entity;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @ORM\Entity
 * @ORM\Table(name="Users", indexes={@ORM\Index(name="email_idx", columns={"email"})})
 * @UniqueEntity("email")
 */
class User implements AdvancedUserInterface, \Serializable
{
    #########################
    ##       METADATA      ##
    #########################

    private static $roles = array (
        'ROLE_NT_USER'      => 'User',
    );


    #########################
    ##      PROPERTIES     ##
    #########################

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", unique=TRUE)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $userId;

    /**
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $salt;

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $password;

    /**
     * @see self::$roles
     * @ORM\Column(type="string", length=60)
     */
    private $role;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $fullName;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(name="is_account_non_expired", type="boolean")
     */
    private $isAccountNonExpired = true;

    /**
     * @ORM\Column(name="is_account_non_locked", type="boolean")
     */
    private $isAccountNonLocked = true;

    /**
     * @ORM\Column(name="is_credential_non_expired", type="boolean")
     */
    private $isCredentialsNonExpired = true;


    #########################
    ## OBJECT RELATIONSHIP ##
    #########################

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
        $this->isActive = true;
        $this->refreshSalt();

        $this->transactions = new ArrayCollection();
    }


    #########################
    ##  INTERFACE METHODS  ##
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
        # TODO: Implement proper Role class
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
    public function isAccountNonExpired()
    {
        return $this->isAccountNonExpired;
    }

    /**
     * @inheritDoc
     */
    public function isAccountNonLocked()
    {
        return $this->isAccountNonLocked;
    }

    /**
     * @inheritDoc
     */
    public function isCredentialsNonExpired()
    {
        return $this->isCredentialsNonExpired;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled()
    {
        return $this->isActive;
    }

    #########################
    ##    STATIC METHODS   ##
    #########################

    public static function getAvailableRoles()
    {
        return self::$roles;
    }


    #########################
    ##   SPECIAL METHODS   ##
    #########################

    /**
     * Generate or refresh the User's security salt
     */
    public function refreshSalt()
    {
        $this->salt = md5(uniqid(null, true));
    }

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

    public function getRoleName()
    {
        if (in_array($this->role, self::$roles)) {
            return self::$roles[$this->role];
        }

        return 'Unknown';
    }

    /**
     * Apply extra operations needed for the CRUD
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container Used for Dependency Injection
     * @param array $options
     */
    public function applyCrudExtraOperations($container, $options)
    {
        if (!is_array($options) || !array_key_exists('mode', $options)) {
            throw new \RuntimeException('Key "mode" not found in CRUD options');
        }

        if ($options['mode'] == 'edit') {
            // do nothing.. for now.
        } else {
            // Load security encoder
            $factory = $container->get('security.encoder_factory');

            /* @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
            $encoder = $factory->getEncoder($this);
            $encodedPassword = $encoder->encodePassword($this->password, $this->salt);
            $this->setPassword($encodedPassword);
        }
    }

    #########################
    ## GETTERS AND SETTERS ##
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
     * Set fullName
     *
     * @param string $fullName
     * @return User
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get fullName
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
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


    #########################
    ##  OBJECT REL: G & S  ##
    #########################

    /**
     * Add Transaction
     *
     * @param Transaction $transaction
     * @return User
     */
    public function addPayment(Transaction $transaction)
    {
        $this->transactions[] = $transaction;
        return $this;
    }

    /**
     * Remove Transaction
     *
     * @param Transaction $transaction
     */
    public function removePayment(Transaction $transaction)
    {
        $this->transactions->removeElement($transaction);
    }

    /**
     * Get Transactions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }


}