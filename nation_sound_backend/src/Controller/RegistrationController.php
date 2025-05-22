<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\EmailVerificationService;

class RegistrationController extends AbstractController
{

    public function __construct(
        private EmailVerificationService $emailVerifier
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode le mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            
            $entityManager->persist($user);
            $entityManager->flush();

            try {
                $this->emailVerifier->sendVerificationEmail($user, 'app_verify_email');

                $this->addFlash('success', 'Un email de vérification a été envoyé à votre adresse email.');
                return $this->redirectToRoute('app_login');
                } catch (\Throwable $e) {
                    $entityManager->remove($user);
                    $entityManager->flush();
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email de confirmation, votre compte n\'a pas été créé. Veuillez réessayer.');
                    return $this->redirectToRoute('app_register');
            }   
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'turnstile_key' => $_ENV['TURNSTILE_KEY']
        ]);
    }
}
