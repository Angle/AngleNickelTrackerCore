<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AccountController extends Controller
{
    public function listAction()
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        $accounts = $nt->loadAccounts();

        return $this->render('AngleNickelTrackerAppBundle:Account:list.html.twig', array(
            'accounts' => $accounts
        ));
    }
}