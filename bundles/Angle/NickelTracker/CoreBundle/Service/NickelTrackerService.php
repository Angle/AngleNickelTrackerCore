<?php

namespace Angle\NickelTracker\CoreBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

use Angle\NickelTracker\CoreBundle\Entity\User;

class NickelTrackerService
{
    protected $doctrine;
    protected $em;

    /** @var User $user */
    protected $user;

    public function __construct(Doctrine $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->em = $this->doctrine->getManager();
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}