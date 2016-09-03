<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DashboardController extends Controller
{
    public function homeAction()
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');
        $dashboard = $nt->loadDashboard();

        return $this->render('AngleNickelTrackerAppBundle:Dashboard:home.html.twig', array(
            'dashboard' => $dashboard,
        ));
    }
}