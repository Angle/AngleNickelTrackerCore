<?php

namespace Angle\NickelTracker\CoreBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Angle\NickelTracker\CoreBundle\Entity\User;

class NickelTrackerService
{
    protected $doctrine;
    protected $em;

    /** @var User $user */
    protected $user;

    public function __construct(Doctrine $doctrine, TokenStorageInterface $tokenStorage)
    {
        $this->doctrine = $doctrine;
        $this->em = $this->doctrine->getManager();

        $this->user = $tokenStorage->getToken()->getUser();
    }
}