<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        VerifyEmailHelperInterface $verifyEmailHelper,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
    
            $entityManager->persist($user);
            $entityManager->flush();
    
            // Génération du lien de vérification
            $signatureComponents = $verifyEmailHelper->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail()
            );
    
            // Envoi de l'email
            $email = (new TemplatedEmail())
                ->from('noreply@example.com')
                ->to($user->getEmail())
                ->subject('Confirmez votre email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context([
                    'signedUrl' => $signatureComponents->getSignedUrl(),
                    'expiresAt' => $signatureComponents->getExpiresAt(),
                ]);
    
            $mailer->send($email);
    
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'turnstile_key' => $_ENV['TURNSTILE_KEY']
        ]);
    }
    
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): Response {
        try {
            // 1. Validation du lien via le token
            $verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request,
                (string) $request->query->get('id'), 
             (string) $request->query->get('email') 
            );
    
            // 2. Récupération de l'utilisateur
            $userId = $request->query->get('id');
            $user = $userRepository->find($userId);
    
            if (!$user) {
                throw $this->createNotFoundException('Utilisateur introuvable');
            }
    
            // 3. Mise à jour du statut
            $user->setIsVerified(true);
            $entityManager->flush();
    
            $this->addFlash('success', 'Email vérifié avec succès !');
            return $this->redirectToRoute('app_home');
    
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        UserRepository $userRepository,
        EmailVerifier $emailVerifier
    ): Response {
        $email = $request->get('email');
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user && !$user->isVerified()) {
            $emailVerifier->sendEmailConfirmation('app_verify_email', $user, 
                (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            
            $this->addFlash('success', 'Un nouveau lien a été envoyé.');
        }

        return $this->redirectToRoute('app_login');
    }
}