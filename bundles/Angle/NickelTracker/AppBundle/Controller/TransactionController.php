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
    public function listAction(Request $request)
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        if ($request->getMethod() == 'POST') {
            $accountId          = $request->request->get('accountId');
            $categoryId         = $request->request->get('categoryId');
            $searchString       = $request->request->get('searchString');
            $startDate          = $request->request->get('startDate');
            $endDate            = $request->request->get('endDate');

            $startDate = \DateTime::createFromFormat('Y-m-d', $startDate);
            $endDate = \DateTime::createFromFormat('Y-m-d', $endDate);

            // Compile filters array
            $filters = array(
                'accountId' => $accountId,
                'categoryId' => $categoryId,
                'searchString' => $searchString,
                'startDate' => $startDate,
                'endDate' => $endDate
            );

            $transactions = $nt->loadTransactions($filters);
        } else {
            $transactions = $nt->loadTransactions();
            $filters = null;
        }

        $accounts = $nt->loadAccounts();
        $categories = $nt->loadCategories();

        return $this->render('AngleNickelTrackerAppBundle:Transaction:list.html.twig', array(
            'transactions'  => $transactions,
            'accounts'      => $accounts,
            'categories'    => $categories,
            'filters'       => $filters
        ));
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
        } elseif ($transaction->getType() != Transaction::TYPE_INCOME) {
            // Cannot edit another type of transaction (cannot change type)
            $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
            $message->setExternalMessage('Invalid transaction type, cannot edit in this controller');
            $message->addToFlashBag($this->get('session')->getFlashBag());
            return $this->redirectToRoute('angle_nt_app_transaction_view', array('id' => $id));
        }

        if ($request->getMethod() == 'POST') {
            // Process new transaction
            $sourceAccountId    = $request->request->get('transactionSourceAccount');
            $description        = trim($request->request->get('transactionDescription'));
            $details            = trim($request->request->get('transactionDetails'));
            $amount             = $request->request->get('transactionAmount');

            $date               = $request->request->get('transactionDate');
            $date               = \DateTime::createFromFormat('Y-m-d', $date);

            $flags = array();
            if ($request->request->get('transactionFlagFiscal')) {
                $flags['fiscal'] = true;
            }
            if ($request->request->get('transactionFlagExtraordinary')) {
                $flags['extraordinary'] = true;
            }

            // Check the request parameters
            if ($sourceAccountId && $description && $amount && $date) {
                // Attempt to create a new income transaction
                $r = $nt->processIncomeTransaction($id, $sourceAccountId, $description, $details, $amount, $date, $flags);

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
        $currenciesByAccounts = array();
        foreach ($accounts as $a) {
            /** @var Account $a */
            $currenciesByAccounts[$a->getAccountId()] = $a->getCurrencyCode();
        }

        return $this->render('AngleNickelTrackerAppBundle:Transaction:new-income.html.twig', array(
            'transaction'   => $transaction,
            'accounts' => $accounts,
            'currenciesByAccounts' => $currenciesByAccounts,
        ));
    }

    /**
     * Process an Expense Transaction
     *
     * @param Request $request
     * @param int $id Transaction ID (if it exists)
     * @return Response
     */
    public function expenseAction(Request $request, $id)
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        // Attempt to load the transaction ID
        $transaction = $nt->loadTransaction($id);

        if (!$transaction) {
            // Transaction not found, initialize a new one
            $transaction = new Transaction();
        } elseif ($transaction->getType() != Transaction::TYPE_EXPENSE) {
            // Cannot edit another type of transaction (cannot change type)
            $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
            $message->setExternalMessage('Invalid transaction type, cannot edit in this controller');
            $message->addToFlashBag($this->get('session')->getFlashBag());
            return $this->redirectToRoute('angle_nt_app_transaction_view', array('id' => $id));
        }

        if ($request->getMethod() == 'POST') {
            // Process new transaction
            $sourceAccountId    = $request->request->get('transactionSourceAccount');
            $categoryId         = $request->request->get('transactionCategory');
            $commerceName       = trim($request->request->get('transactionCommerce'));
            $description        = trim($request->request->get('transactionDescription'));
            $details            = trim($request->request->get('transactionDetails'));
            $amount             = $request->request->get('transactionAmount');

            $date               = $request->request->get('transactionDate');
            $date               = \DateTime::createFromFormat('Y-m-d', $date);

            $flags = array();
            if ($request->request->get('transactionFlagFiscal')) {
                $flags['fiscal'] = true;
            }
            if ($request->request->get('transactionFlagExtraordinary')) {
                $flags['extraordinary'] = true;
            }

            // Check the request parameters
            if ($sourceAccountId && $description && $amount && $date) {
                // Attempt to create a new expense transaction
                $r = $nt->processExpenseTransaction($id, $sourceAccountId, $categoryId, $commerceName, $description, $details, $amount, $date, $flags);

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
        $accounts   = $nt->loadAccounts();
        $currenciesByAccounts = array();
        foreach ($accounts as $a) {
            /** @var Account $a */
            $currenciesByAccounts[$a->getAccountId()] = $a->getCurrencyCode();
        }

        $categories = $nt->loadCategories();

        $commerces  = $nt->loadCommerces();
        $commercesArray = array();
        foreach ($commerces as $c) {
            /** @var Commerce $c */
            $commercesArray[] = $c->getName();
        }

        return $this->render('AngleNickelTrackerAppBundle:Transaction:new-expense.html.twig', array(
            'transaction'   => $transaction,
            'accounts'      => $accounts,
            'currenciesByAccounts' => $currenciesByAccounts,
            'categories'    => $categories,
            'commerces'     => $commercesArray,
        ));
    }

    /**
     * Process a Transfer Transaction
     *
     * @param Request $request
     * @param int $id Transaction ID (if it exists)
     * @return Response
     */
    public function transferAction(Request $request, $id)
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        // Attempt to load the transaction ID
        $transaction = $nt->loadTransaction($id);

        if (!$transaction) {
            // Transaction not found, initialize a new one
            $transaction = new Transaction();
        } elseif ($transaction->getType() != Transaction::TYPE_TRANSFER) {
            // Cannot edit another type of transaction (cannot change type)
            $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
            $message->setExternalMessage('Invalid transaction type, cannot edit in this controller');
            $message->addToFlashBag($this->get('session')->getFlashBag());
            return $this->redirectToRoute('angle_nt_app_transaction_view', array('id' => $id));
        }

        if ($request->getMethod() == 'POST') {
            // Process new transaction
            $sourceAccountId    = $request->request->get('transactionSourceAccount');
            $destinationAccountId = $request->request->get('transactionDestinationAccount');
            $description        = trim($request->request->get('transactionDescription'));
            $details            = trim($request->request->get('transactionDetails'));
            $amount             = $request->request->get('transactionAmount');

            $date               = $request->request->get('transactionDate');
            $date = \DateTime::createFromFormat('Y-m-d', $date);

            $flags = array();
            if ($request->request->get('transactionFlagFiscal')) {
                $flags['fiscal'] = true;
            }
            if ($request->request->get('transactionFlagExtraordinary')) {
                $flags['extraordinary'] = true;
            }

            // Check the request parameters
            if ($sourceAccountId && $destinationAccountId && $description && $amount && $date) {
                // Attempt to create a new transfer transaction
                $r = $nt->processTransferTransaction($id, $sourceAccountId, $destinationAccountId, $description, $details, $amount, $date, $flags);

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

        return $this->render('AngleNickelTrackerAppBundle:Transaction:new-transfer.html.twig', array(
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