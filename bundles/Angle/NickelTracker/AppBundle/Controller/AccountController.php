<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

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

    public function updateNameAction(Request $request)
    {
        ## VALIDATE JSON REQUEST
        // We simply use json_decode to parse the content of the request and
        //    then replace the request data on the $request object.
        //    This is useful if we ever decide to deprecate JSON in favor of
        //    other request method, for example HTTP POST.
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            // Error: Bad JSON packages
            $json = array(
                'error' => 1,
                'description' => 'Bad JSON data'
            );
            return new JsonResponse($json, 400);
        }

        if (!array_key_exists('accountId', $data) || !array_key_exists('name', $data)) {
            // Error: Missing parameters
            $json = array(
                'error' => 1,
                'description' => 'Bad JSON data'
            );
            return new JsonResponse($json, 400);
        }

        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        $r = $nt->changeAccountName($data['accountId'], $data['name']);

        if ($r) {
            $json = array(
                'error' => 0,
                'description' => 'Success'
            );
        } else {
            $json = array(
                'error' => 1,
                'description' => 'Could not change the '
            );
        }

        return new JsonResponse($json);
    }
}