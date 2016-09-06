<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Angle\NickelTracker\CoreBundle\Utility\ResponseMessage;
use Angle\NickelTracker\CoreBundle\Entity\Commerce;

class CommerceController extends Controller
{
    public function listAction()
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        $commerces = $nt->loadCommerces();

        return $this->render('AngleNickelTrackerAppBundle:Commerce:list.html.twig', array(
            'commerces' => $commerces
        ));
    }

    /**
     * Update a commerce's field (AJAX only)
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
            $r = $nt->changeCommerceName($data['id'], trim($data['value']));

            if ($r) {
                $json = array('error' => 0, 'description' => 'Success');
            } else {
                $json = array('error' => 1, 'description' => 'Could not change the name of the Commerce');
            }
        } else {
            $json = array('error' => 1, 'description' => 'Invalid property selected');
        }

        return new JsonResponse($json);
    }

    /**
     * Safe-delete a commerce
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
            $r = $nt->deleteCommerce($id);

            if ($r) {
                // Everything went ok
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
                $message->addToFlashBag($this->get('session')->getFlashBag());
            } else {
                $error = $nt->getError();
                // Something failed when deleting the commerce
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
                $message->setExternalMessage($error['code'] . ': ' . $error['message']);
                $message->addToFlashBag($this->get('session')->getFlashBag());
            }

        } else {
            // Invalid request parameters
            $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
            $message->addToFlashBag($this->get('session')->getFlashBag());
        }

        return $this->redirectToRoute('angle_nt_app_commerce_list');
    }
}