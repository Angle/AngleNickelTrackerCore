<?php

namespace Angle\NickelTracker\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DashboardController extends Controller
{
    public function homeAction()
    {
        return $this->render('AngleNickelTrackerAdminBundle:Dashboard:home.html.twig', array(
            // do nothing..
        ));
    }
}