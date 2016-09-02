<?php

namespace Angle\NickelTracker\CoreBundle\Entity;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @ORM\Entity
 * @ORM\Table(name="AdminUsers", indexes={@ORM\Index(name="user_idx", columns={"username"})})
 * @UniqueEntity("username")
 */
class AdminUser implements AdvancedUserInterface, \Serializable
{
    #########################
    ##       METADATA      ##
    #########################

    private static $roles = array (
        'ROLE_SUPER_ADMIN'  => 'Super Admin',
        'ROLE_ADMIN'        => 'Admin',
    );


    #########################
    ##      PROPERTIES     ##
    #########################

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", unique=TRUE)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $adminUserId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $username;

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
     * @ORM\Column(type="string", nullable=true)
     */
    private $email;

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

    // none.


    #########################
    ##     CONSTRUCTOR     ##
    #########################

    public function __construct()
    {
        $this->isActive = true;
        $this->salt = md5(uniqid(null, true));
    }


    #########################
    ##  INTERFACE METHODS  ##
    #########################

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->username;
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
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(array(
            $this->adminUserId,
        ));
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
            $this->adminUserId,
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
     * Get adminUserId
     *
     * @return integer
     */
    public function getAdminUserId()
    {
        return $this->adminUserId;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return AdminUser
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return AdminUser
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
     * @return AdminUser
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
     * @return AdminUser
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
     * @return AdminUser
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
     * @return AdminUser
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
     * @return AdminUser
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

    // none.


}