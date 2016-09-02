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
     * @param int $id User ID
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function processAction(Request $request, $id)
    {
        // Entity Manager
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if (!$user) {
            $user = new User();
        }

        $form = $this->createForm(UserType::class, $user);

        // Check if form was submitted
        $form->handleRequest($request);

        // Check form validation
        if ($form->isSubmitted() && $form->isValid()) {

            // No S3 File operations

            // Encode and save password
            $factory = $this->get('security.encoder_factory');
            /* @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
            $encoder = $factory->getEncoder($user);
            $encodedPassword = $encoder->encodePassword($user->getPassword(), $user->getSalt());
            $user->setPassword($encodedPassword);

            // Persist to database using Entity Manager
            $em->persist($user);

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
                $message->addToFlashBag($this->get('session')->getFlashBag());
                return $this->redirectToRoute('angle_nt_admin_user_view', array('id' => $user->getUserId()));
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
}