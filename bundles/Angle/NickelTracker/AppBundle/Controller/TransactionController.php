<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Angle\NickelTracker\CoreBundle\Utility\ResponseMessage;
use Angle\NickelTracker\CoreBundle\Entity\Account;
use Angle\NickelTracker\CoreBundle\Entity\Category;
use Angle\NickelTracker\CoreBundle\Entity\Commerce;
use Angle\NickelTracker\CoreBundle\Entity\Transaction;

class TransactionController extends Controller
{
    public function dashboardAction()
    {

    }

    /**
     * Create a New Income Transaction
     *
     * @param Request $request
     * @return Response
     */
    public function newIncomeAction(Request $request)
    {
        // Get today's date in the default timezone
        $today = new \DateTime("now", new \DateTimeZone('America/Monterrey'));

        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        if ($request->getMethod() == 'POST') {
            // Process new account
            $type   = $request->request->get('accountType');
            $name   = $request->request->get('accountName');
            $limit  = $request->request->get('accountCreditLimit');

            // Check the request parameters
            if ($type && $name) {
                // Attempt to create a new account
                $r = $nt->createAccount($type, $name, $limit);

                if ($r) {
                    // Everything went ok, redirect to the account list with a FlashBag
                    $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
                    $message->addToFlashBag($this->get('session')->getFlashBag());
                    return $this->redirectToRoute('angle_nt_app_account_list');
                } else {
                    $error = $nt->getError();
                    // Something failed, build a new Response Message and return to the create new view
                    $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
                    $message->setExternalMessage($error['code'] . ': ' . $error['message']);
                    $message->addToFlashBag($this->get('session')->getFlashBag());
                }
            } else {
                // Invalid request parameters
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
                $message->addToFlashBag($this->get('session')->getFlashBag());
            }
        }

        // Load the user's information to passdown
        $accounts = $nt->loadAccounts();

        return $this->render('AngleNickelTrackerAppBundle:Transaction:new-income.html.twig', array(
            'today' => $today,
            'accounts' => $accounts,
        ));
    }
}