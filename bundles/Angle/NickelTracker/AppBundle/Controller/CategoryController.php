<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Angle\NickelTracker\CoreBundle\Utility\ResponseMessage;
use Angle\NickelTracker\CoreBundle\Entity\Category;

class CategoryController extends Controller
{
    public function listAction()
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        $categories = $nt->loadCategories();

        return $this->render('AngleNickelTrackerAppBundle:Category:list.html.twig', array(
            'categories' => $categories
        ));
    }

    /**
     * Create a new Category
     *
     * @param Request $request
     * @return Response
     */
    public function newAction(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            // Process new category
            $name   = $request->request->get('categoryName');
            $budget = $request->request->get('categoryBudget');

            // Check the request parameters
            if ($name && $budget) {
                // Attempt to create a new category
                /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
                $nt = $this->get('angle.nickeltracker');
                $r = $nt->createCategory($name, $budget);

                if ($r) {
                    // Everything went ok, redirect to the category list with a FlashBag
                    $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
                    $message->addToFlashBag($this->get('session')->getFlashBag());
                    return $this->redirectToRoute('angle_nt_app_category_list');
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

        // Create a sample category to pass down (access static methods)
        $category = new Category();

        return $this->render('AngleNickelTrackerAppBundle:Category:new.html.twig', array(
            'category' => $category
        ));
    }

    /**
     * Update a category's field (AJAX only)
     *
     * @param Request $request
     * @return JsonResponse
     */
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
            $r = $nt->changeCategoryName($data['id'], $data['value']);

            if ($r) {
                $json = array('error' => 0, 'description' => 'Success');
            } else {
                $json = array('error' => 1, 'description' => 'Could not change the name of the Category');
            }
        } elseif ($data['property'] == 'budget') {
            $r = $nt->changeCategoryBudget($data['id'], $data['value']);

            if ($r) {
                $json = array('error' => 0, 'description' => 'Success');
            } else {
                $json = array('error' => 1, 'description' => 'Could not change the budget value of the Category');
            }
        } else {
            $json = array('error' => 1, 'description' => 'Invalid property selected');
        }

        return new JsonResponse($json);
    }

    /**
     * Safe-delete a category
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
            $r = $nt->deleteCategory($id);

            if ($r) {
                // Everything went ok
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
                $message->addToFlashBag($this->get('session')->getFlashBag());
            } else {
                $error = $nt->getError();
                // Something failed when deleting the category
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
                $message->setExternalMessage($error['code'] . ': ' . $error['message']);
                $message->addToFlashBag($this->get('session')->getFlashBag());
            }

        } else {
            // Invalid request parameters
            $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
            $message->addToFlashBag($this->get('session')->getFlashBag());
        }

        return $this->redirectToRoute('angle_nt_app_category_list');
    }
}