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
use Angle\NickelTracker\CoreBundle\Entity\ScheduledTransaction;

class ScheduledController extends Controller
{
    public function listAction(Request $request)
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        $transactions = $nt->loadScheduledTransactions();

        return $this->render('AngleNickelTrackerAppBundle:Scheduled:list.html.twig', array(
            'transactions'  => $transactions
        ));
    }

    public function viewAction($id)
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        $transaction = $nt->loadScheduledTransaction($id);

        if (!$transaction) {
            throw $this->createNotFoundException('Scheduled Transaction ID ' . $id . ' not found.');
        }

        return $this->render('AngleNickelTrackerAppBundle:Scheduled:view.html.twig', array(
            'transaction' => $transaction
        ));
    }

    /**
     * Process a Scheduled Transaction
     *
     * @param Request $request
     * @param int $id Scheduled Transaction ID (if it exists)
     * @return Response
     */
    public function processAction(Request $request, $id)
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        // Attempt to load the transaction ID
        $transaction = $nt->loadScheduledTransaction($id);

        if (!$transaction) {
            // Transaction not found, initialize a new one
            $transaction = new ScheduledTransaction();
        }

        if ($request->getMethod() == 'POST') {
            // Process new transaction
            $type               = $request->request->get('transactionType');
            $sourceAccountId    = $request->request->get('transactionSourceAccount');
            $destinationAccountId = $request->request->get('transactionDestinationAccount');
            $categoryId         = $request->request->get('transactionCategory');
            $commerceName       = trim($request->request->get('transactionCommerce'));
            $description        = trim($request->request->get('transactionDescription'));
            $details            = trim($request->request->get('transactionDetails'));
            $amount             = $request->request->get('transactionAmount');
            $day                = $request->request->get('transactionDay');

            $flags = array();
            if ($request->request->get('transactionFlagFiscal')) {
                $flags['fiscal'] = true;
            }
            if ($request->request->get('transactionFlagExtraordinary')) {
                $flags['extraordinary'] = true;
            }

            // Check the request parameters
            if ($type && $sourceAccountId && $description && $amount && $day) {
                // Attempt to create a new Scheduled Transaction
                $r = $nt->processScheduledTransaction($id, $type, $sourceAccountId, $destinationAccountId, $categoryId, $commerceName, $description, $details, $amount, $day, $flags);

                if ($r) {
                    // Everything went ok, redirect to the account list with a FlashBag
                    $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
                    $message->addToFlashBag($this->get('session')->getFlashBag());
                    return $this->redirectToRoute('angle_nt_app_scheduled_view', array('id' => $r));
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
        $accounts   = $nt->loadAccounts();
        $categories = $nt->loadCategories();

        $commerces  = $nt->loadCommerces();
        $commercesArray = array();
        foreach ($commerces as $c) {
            /** @var Commerce $c */
            $commercesArray[] = $c->getName();
        }

        return $this->render('AngleNickelTrackerAppBundle:Scheduled:process.html.twig', array(
            'transaction'   => $transaction,
            'accounts'      => $accounts,
            'categories'    => $categories,
            'commerces'     => $commercesArray,
        ));
    }

    /**
     * Safe-delete a scheduled transaction
     *
     * @param Request $request
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $id = $request->request->get('id');

        // Check the request parameters
        if ($id) {
            /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
            $nt = $this->get('angle.nickeltracker');
            $r = $nt->deleteScheduledTransaction($id);

            if ($r) {
                // Everything went ok
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
                $message->addToFlashBag($this->get('session')->getFlashBag());
            } else {
                $error = $nt->getError();
                // Something failed when deleting the transaction
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
                $message->setExternalMessage($error['code'] . ': ' . $error['message']);
                $message->addToFlashBag($this->get('session')->getFlashBag());
            }
        } else {
            // Invalid request parameters
            $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
            $message->addToFlashBag($this->get('session')->getFlashBag());
        }

        return $this->redirectToRoute('angle_nt_app_scheduled_list');
    }
}