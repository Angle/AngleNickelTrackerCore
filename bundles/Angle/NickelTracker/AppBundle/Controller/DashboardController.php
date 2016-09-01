<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DashboardController extends Controller
{
    public function homeAction()
    {
        return $this->render('AngleNickelTrackerAppBundle:Dashboard:home.html.twig', array(
        ));
    }
}