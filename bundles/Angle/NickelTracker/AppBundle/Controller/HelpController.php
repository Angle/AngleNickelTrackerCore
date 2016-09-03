<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HelpController extends Controller
{
    public function howtoAction()
    {
        return $this->render('AngleNickelTrackerAppBundle:Help:howto.html.twig', array(
        ));
    }
}