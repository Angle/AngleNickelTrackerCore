<?php

namespace Angle\NickelTracker\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\DBAL\DBALException;

use Angle\NickelTracker\CoreBundle\Utility\ResponseMessage;
use Angle\NickelTracker\CoreBundle\Entity\User;

use Angle\NickelTracker\AdminBundle\Form\Type\UserType;

class UserController extends Controller
{
    public function listAction()
    {
        /* @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->getDoctrine()->getRepository(User::class);
        $users = $repository->findAll();

        return $this->render('AngleNickelTrackerAdminBundle:User:list.html.twig', array(
            'users' => $users,
        ));
    }

    public function viewAction($id)
    {
        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                "User ID '{$id}' not found."
            );
        }

        return $this->render('AngleNickelTrackerAdminBundle:User:view.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function processAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        // Check if form was submitted
        $form->handleRequest($request);

        // Check form validation
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
            $nt = $this->get('angle.nickeltracker');
            $nt->enableAdminMode(true);

            $r = $nt->createUser($user->getEmail(), $user->getFullName(), $user->getPassword());

            if (!$r) {
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
            }

            if (!isset($message)) { // No error, therefore it was successful!
                $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
                $message->addToFlashBag($this->get('session')->getFlashBag());
                return $this->redirectToRoute('angle_nt_admin_user_view', array('id' => $r));
            }

        }

        // Render as new Entity
        return $this->render('AngleNickelTrackerAdminBundle:User:form.html.twig', array(
            'form'  => $form->createView(),
            'user'  => $user
        ));

    }

    public function deleteAction($id)
    {
        // Entity Manager
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                "User ID '{$id}' not found."
            );
        }

        $em->remove($user);

        try {
            $em->flush();
        } catch (DBALException $e) {
            $message = new ResponseMessage(ResponseMessage::DOCTRINE, $e->getCode());
            if ($this->container->getParameter('kernel.environment') == 'dev') {
                $message->setExternalMessage($e->getMessage());
            }
        }

        if (!isset($message)) { // No error, therefore it was successful!
            $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
        }
        $message->addToFlashBag($this->get('session')->getFlashBag());


        return $this->redirectToRoute('angle_nt_admin_user_list');
    }

    public function toggleAction($id)
    {
        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                "User ID '{$id}' not found."
            );
        }

        /** @var \Angle\NickelTracker\CoreBundle\Service\NickelTrackerService $nt */
        $nt = $this->get('angle.nickeltracker');
        $nt->enableAdminMode(true);

        if ($user->getIsActive()) {
            $r = $nt->disableUser($user);
        }else{
            $r = $nt->enableUser($user);
        }

        if (!$r) {
            $message = new ResponseMessage(ResponseMessage::CUSTOM, 1);
        }


        if (!isset($message)) { // No error, therefore it was successful!
            $message = new ResponseMessage(ResponseMessage::CUSTOM, 0);
        }
        $message->addToFlashBag($this->get('session')->getFlashBag());


        return $this->redirectToRoute('angle_nt_admin_user_list');
    }
}