<?php

namespace Angle\NickelTracker\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SecurityController extends Controller
{
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        // $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('AngleNickelTrackerAdminBundle:Security:login.html.twig', array('error' => $error));
    }
}
