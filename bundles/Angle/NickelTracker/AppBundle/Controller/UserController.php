<?php

namespace Angle\NickelTracker\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Angle\NickelTracker\CoreBundle\Utility\ResponseMessage;
use Angle\NickelTracker\CoreBundle\Entity\User;

class UserController extends Controller
{
    public function profileAction()
    {
        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');

        $user = $nt->loadUser();

        return $this->render('AngleNickelTrackerAppBundle:User:view.html.twig', array(
            'user' => $user
        ));
    }

    public function changePasswordAction(Request $request)
    {
        // Process new account
        $oldPassword        = $request->request->get('oldPassword');
        $newPassword        = $request->request->get('newPassword');
        $confirmPassword    = $request->request->get('confirmPassword');

        // Check the request parameters
        if ($oldPassword && $newPassword && $confirmPassword && ($newPassword == $confirmPassword)) {
            // Attempt to change the user password
            /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
            $nt = $this->get('angle.nickeltracker');
            $r = $nt->changeUserPassword($oldPassword, $newPassword);

            if ($r) {
                // Everything went ok, redirect to the account list with a FlashBag
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
                $message->addToFlashBag($this->get('session')->getFlashBag());
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


        return $this->redirectToRoute('angle_nt_app_user_profile');
    }
}