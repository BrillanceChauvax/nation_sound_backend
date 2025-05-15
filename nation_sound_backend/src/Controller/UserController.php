<?php

namespace App\Controller;

use App\Form\EmailForm;
use App\Form\PasswordForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/user/edit', name: 'app_user_edit')]
    public function edit(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager
    ): Response {

        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Formulaire de modification d'email
        $emailForm = $this->createForm(EmailForm::class);
        $emailForm->handleRequest($request);

        // Formulaire de modification de mot de passe
        $passwordForm = $this->createForm(PasswordForm::class);
        $passwordForm->handleRequest($request);

        // Traitement formulaire email
        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $newEmail = $emailForm->get('newEmail')->getData();
            
            // Envoie un email de vérification au nouvel email
            $this->container->get(SecurityController::class)->sendVerificationEmail($user, 'app_verify_email_update');

            $this->addFlash('info', 'Un email de confirmation a été envoyé à ' . $newEmail);
            return $this->redirectToRoute('app_user_edit');
        }

        // Traitement formulaire mot de passe
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $newPassword = $passwordForm->get('newPassword')->getData();
            
            $user->setPassword(
                $passwordHasher->hashPassword($user, $newPassword)
            );
            
            $entityManager->flush();
            $this->addFlash('success', 'Mot de passe mis à jour avec succès');
            return $this->redirectToRoute('app_user_edit');
        }

        return $this->render('user/edit.html.twig', [
            'emailForm' => $emailForm->createView(),
            'passwordForm' => $passwordForm->createView()
        ]);
    }

    #[Route('/user/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete_'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $this->container->get('security.token_storage')->setToken(null);
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('info', 'Votre compte a été supprimé avec succès');
        } else {
            $this->addFlash('error', 'Token CSRF invalide');
        }

        return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
    }
}
