<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Angle\NickelTracker\CoreBundle\Entity\Account;

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

    public function newAction(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            // We are creating a new one!
            // Check the data


            if ($ok) {
                // Everything went ok, redirect to the account list with a FlashBag
            }
        }

        // Create a sample account to pass down (access static methods)
        $account = new Account();

        return $this->render('AngleNickelTrackerAppBundle:Account:new.html.twig', array(
            'account' => $account
        ));
    }

    public function updateAction(Request $request)
    {
        ## VALIDATE JSON REQUEST
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            // Error: Bad JSON packages
            $json = array('error' => 1, 'description' => 'Bad JSON data');
            return new JsonResponse($json, 400);
        }

        if (!array_key_exists('id', $data) || !array_key_exists('property', $data) || !array_key_exists('value', $data)) {
            // Error: Missing parameters
            $json = array('error' => 1, 'description' => 'Bad JSON data');
            return new JsonResponse($json, 400);
        }

        ## Process properties
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        if ($data['property'] == 'name') {
            $r = $nt->changeAccountName($data['id'], $data['value']);

            if ($r) {
                $json = array('error' => 0, 'description' => 'Success');
            } else {
                $json = array('error' => 1, 'description' => 'Could not change the name of the Account');
            }
        } elseif ($data['property'] == 'creditLimit') {
            $r = $nt->changeAccountCreditLimit($data['id'], $data['value']);

            if ($r) {
                $json = array('error' => 0, 'description' => 'Success');
            } else {
                $json = array('error' => 1, 'description' => 'Could not change the name of the Account');
            }
        } else {
            $json = array('error' => 1, 'description' => 'Invalid property selected');
        }

        return new JsonResponse($json);
    }
}