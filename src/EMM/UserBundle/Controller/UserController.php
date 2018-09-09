<?php

namespace EMM\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use EMM\UserBundle\Entity\User;
use EMM\UserBundle\Form\UserType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;

class UserController extends Controller
{

    public function homeAction()
    {
        return $this->render('EMMUserBundle:User:home.html.twig');
    }

    public function indexAction(Request $request)
    {
        //return new Response("Bienvenido a mi módulo de usuarios");
        $searchQuery = $request->get('query');
        //print_r($searchQuery);
        //exit();

        if(!empty($searchQuery))
        {
            $finder = $this->container->get('fos_elastica.finder.app.user');
            $users = $finder->createPaginatorAdapter($searchQuery);
        }
        else
        {
            $em = $this->getDoctrine()->getManager();
            $dql = "SELECT u FROM EMMUserBundle:User u ORDER BY u.id DESC";
            $users = $em->createQuery($dql);
        }

        // $users = $em->getRepository('EMMUserBundle:User')->findAll();

        /*
        $res = "Lista de usuarios: <br />";

        foreach($users as $user)
        {
            $res.="Usuario: ".$user->getUsername()."<br />"." Email: ".$user->getEmail()."<br />";
        }
        return new Response($res);
        */

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($users, $request->query->getInt('page', 1), 3);

        //return $this->render('EMMUserBundle:User:index.html.twig', array('users' => $users));

        $deleteFormAjax = $this->createCustomForm(':USER_ID', 'DELETE', 'emm_user_delete');

        return $this->render('EMMUserBundle:User:index.html.twig', array(
          'pagination' => $pagination,
          'delete_form_ajax' => $deleteFormAjax->createView(),
        ));
    }

    public function addAction(){
        $user = new User(); //1- Llamar a user para pode utilizarlo.
        $form = $this->createCreateForm($user);

        return $this->render('EMMUserBundle:User:add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function createCreateForm(User $entity){
        //Hay que usar UserType::class
        // ESTE NO FUNCIONA: $form = $this->createForm(new UserType(), $entity);
        $form = $this->createForm(UserType::class, $entity, array(
            'action' => $this->generateUrl('emm_user_create'),
            'method' => 'POST'
        ));
        return $form;
    }

    public function createAction(Request $request)
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);

        if($form->isValid())
        {
            $password = $form->get('password')->getData();

            $passwordConstraint = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($password, $passwordConstraint);

            if(count($errorList)==0)
            {
                $encoder = $this->container->get('security.password_encoder')->encodePassword($user, $password);
                $user->setPassword($encoder);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $successMessage = $this->get('translator')->trans('The user has been created.');
                $this->addFlash('mensaje', $successMessage);
                return $this->redirectToRoute('emm_user_index');
            }
            else
            {
                $errorMessage = new FormError($errorList[0]->getMessage());
                $form->get('password')->addError($errorMessage);
            }

            //ESTO NO FUNCIONA PORQUE NO SE DEFINIO LA VARIABLE encoder
            //Notice: Undefined variable: enconder
            //$encoder = $this->container->get('security.password_encoder');
            //$encoded = $enconder->encodePassword($user, $password);
            //$user->setPassword($encoded);
        }
        return $this->render('EMMUserBundle:User:add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function add1Action()
    {
        //return $this->render('EMMUserBundle:Menu:menu.html.twig'); ESTE NOMBRE ESTA MAL
        //PORQUE *.html.twig DEBE LLEVAR EL NOMBRE DE LA ACCION. ESTE DEBERIA LLAMARSE
        //add.html.twig
        //return new Response("Agregar Usuario");
        //return $this->render('EMMUserBundle:User:add.html.twig', array('form' => 'EMMUserBundle:User'));

        $user = new User();

        $form = $this->createFormBuilder($user)
           ->add('username', TextType::class)
           ->add('firstName')
           ->add('lastName')
           ->add('email')
           ->add('password')
           ->add('role')
           ->add('isActive')
           ->add('createdAt')
           ->add('updateAt')
           ->getForm();
           //EMMUserBundle:Default:new.html.twig
       return $this->render('EMMUserBundle:Default:new.html.twig', array(
           'form' => $form->createView(),
       ));
       //return $this->render('EMMUserBundle:User:add.html.twig');
    }

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('EMMUserBundle:User')->find($id);

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
        }

        $form = $this->createEditForm($user);
        return $this->render('EMMUserBundle:User:edit.html.twig', array('user'=> $user, 'form' => $form->createView()));
    }

    private function createEditForm(User $entity)
    {
        $form = $this->createForm(UserType::class, $entity, array(
            'action' => $this->generateUrl('emm_user_update',
            array('id' => $entity->getId())),
            'method' => 'PUT'
      ));
      return $form;
    }

    public function updateAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('EMMUserBundle:User')->find($id);

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
        }

        $form = $this->createEditForm($user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $password = $form->get('password')->getData();
            if(!empty($password))
            {
                $encoder = $this->container->get('security.password_encoder')->encodePassword($user, $password);
                $user->setPassword($encoder);
            }else {
              $recoverPass = $this->recoverPass($id);
              $user->setPassword($recoverPass[0]['password']);
            }
            //print_r($password);
            //exit();

            if($form->get('role')->getData()=='ROLE_ADMIN')
            {
                $user->setIsActive(1);
            }

            $em->flush();

            $successMessage = $this->get('translator')->trans('The user has been modified.');
            $this->addFlash('mensaje', $successMessage);
            return $this->redirectToRoute('emm_user_edit', array('id' => $user->getId()));
        }
        return $this->render('EMMUserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
    }

    private function recoverPass($id)
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT u.password
             FROM EMMUserBundle:User u
             WHERE u.id = :id'
        )->setParameter('id', $id);

        $currentPass = $query->getResult();
        return $currentPass;
    }

    public function viewAction($id)
    {
        //return new Response("Ver Usuario".$id);
        //return new Response("Usuario: ".$user->getUsername()." - Email: ".$user->getEmail());
        $repository=$this->getDoctrine()->getRepository('EMMUserBundle:User');
        $user=$repository->find($id);

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
        }

        //$deleteForm = $this->createDeleteForm($user);
        $deleteForm = $this->createCustomForm($user->getId(), 'DELETE', 'emm_user_delete');

        return $this->render('EMMUserBundle:User:view.html.twig', array('user' => $user, 'delete_form' => $deleteForm->createView()));
    }

    //private function createDeleteForm($user)
    //{
    //    return $this->createFormBuilder($user)
    //        ->setAction($this->generateUrl('emm_user_delete', array('id' => $user->getId())))
    //        ->setMethod('DELETE')
    //        ->getForm();
    //}

    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('EMMUserBundle:User')->find($id);

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
        }

        $allUsers = $em->getRepository('EMMUserBundle:User')->findAll();
        $countUsers = count($allUsers);

        //$form = $this->createDeleteForm($user);
        $form = $this->createCustomForm($user->getId(), 'DELETE', 'emm_user_delete');
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            if($request->isXmlHttpRequest())
            {
                $res = $this->deleteUser($user->getRole(), $em, $user);

                return new Response(
                    json_encode(array('removed' => $res['removed'], 'message' =>
                    $res['message'], 'countUsers' => $countUsers)),
                    200,
                    array('Content-Type' => 'application/json')
                );
            }
            //$em->remove($user);
            //$em->flush();
            //$successMessage = $this->get('translator')->trans('The user has been deleted.');
            //$this->addFlash('mensaje', $successMessage);

            $res = $this->deleteUser($user->getRole(), $em, $user);
            $this->addFlash($res['alert'], $res['message']);
            return $this->redirectToRoute('emm_user_index');

        }
        //return new Response("Eliminar Usuario".$id);
    }

    private function deleteUser($role, $em, $user)
    {
        if($role == 'ROLE_USER')
        {
            $em->remove($user);
            $em->flush();

            $message = $this->get('translator')->trans('The user has been deleted.');
            $removed = 1;
            $alert = 'mensaje';
        }
        elseif($role == 'ROLE_ADMIN')
        {
            $message = $this->get('translator')->trans('The user could not be deleted.');
            $removed = 0;
            $alert = 'error';
        }
        return array('removed' => $removed, 'message' => $message, 'alert' => $alert);
    }

    private function createCustomForm($id, $method, $route)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route, array('id' => $id)))
            ->setMethod($method)
            ->getForm();
    }

    public function articlesAction($page)
    {
        return new Response("Este es mi articulo ".$page);
    }
}
