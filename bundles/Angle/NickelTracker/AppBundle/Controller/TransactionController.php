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

    public function viewAction($id)
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        $transaction = $nt->loadTransaction($id);

        if (!$transaction) {
            throw $this->createNotFoundException('Transaction ID ' . $id . ' not found.');
        }

        return $this->render('AngleNickelTrackerAppBundle:Transaction:view.html.twig', array(
            'transaction' => $transaction
        ));
    }

    /**
     * Process an Income Transaction
     *
     * @param Request $request
     * @param int $id Transaction ID (if it exists)
     * @return Response
     */
    public function incomeAction(Request $request, $id)
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        // Attempt to load the transaction ID
        $transaction = $nt->loadTransaction($id);

        if (!$transaction) {
            // Transaction not found, initialize a new one
            $transaction = new Transaction();
        }

        if ($request->getMethod() == 'POST') {
            // Process new transaction
            $sourceAccountId    = $request->request->get('transactionSourceAccount');
            $description        = $request->request->get('transactionDescription');
            $details            = $request->request->get('transactionDetails');
            $amount             = $request->request->get('transactionAmount');
            $date               = $request->request->get('transactionDate');
            $date = \DateTime::createFromFormat('Y-m-d', $date);

            // Check the request parameters
            if ($sourceAccountId && $description && $amount && $date) {
                // Attempt to create a new account
                $r = $nt->processIncomeTransaction($id, $sourceAccountId, $description, $details, $amount, $date);

                if ($r) {
                    // Everything went ok, redirect to the account list with a FlashBag
                    $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
                    $message->addToFlashBag($this->get('session')->getFlashBag());
                    return $this->redirectToRoute('angle_nt_app_transaction_view', array('id' => $r));
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
            'transaction'   => $transaction,
            'accounts' => $accounts,
        ));
    }

    /**
     * Safe-delete a transaction
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
            $r = $nt->deleteTransaction($id);

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

        return $this->redirectToRoute('angle_nt_app_transaction_list');
    }
}