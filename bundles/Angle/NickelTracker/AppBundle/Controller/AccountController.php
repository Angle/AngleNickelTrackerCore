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

    public function updateAction(Request $request)
    {
        ## VALIDATE JSON REQUEST
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            // Error: Bad JSON packages
            $json = array(
                'error' => 1,
                'description' => 'Bad JSON data'
            );
            return new JsonResponse($json, 400);
        }

        if (!array_key_exists('id', $data) || !array_key_exists('property', $data) || !array_key_exists('value', $data)) {
            // Error: Missing parameters
            $json = array(
                'error' => 1,
                'description' => 'Bad JSON data'
            );
            return new JsonResponse($json, 400);
        }

        ## Process properties
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        if ($data['property'] == 'name') {
            $r = $nt->changeAccountName($data['accountId'], $data['value']);

            if ($r) {
                $json = array(
                    'error' => 0,
                    'description' => 'Success'
                );
            } else {
                $json = array(
                    'error' => 1,
                    'description' => 'Could not change the name of the Account'
                );
            }
        } elseif ($data['property'] == 'creditLimit') {
            $r = $nt->changeAccountCreditLimit($data['accountId'], $data['value']);

            if ($r) {
                $json = array(
                    'error' => 0,
                    'description' => 'Success'
                );
            } else {
                $json = array(
                    'error' => 1,
                    'description' => 'Could not change the name of the Account'
                );
            }
        } else {
            $json = array(
                'error' => 1,
                'description' => 'Invalid property selected'
            );
        }





        return new JsonResponse($json);
    }
}