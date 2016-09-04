<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserController extends Controller
{
    public function profileAction()
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        $user = $nt->loadUser();

        return $this->render('AngleNickelTrackerAppBundle:User:view.html.twig', array(
            'user' => $user
        ));
    }

    public function changePasswordAction(Request $request)
    {
        
    }
}