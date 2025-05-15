<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Service\EmailVerificationService;

class EmailVerifierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EmailVerificationService $emailVerifier
    ) {}

    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        try {
            $user = $userRepository->find($request->query->get('id'));

            $this->container->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();
            
            $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request,
                (string)$user->getId(),
                $user->getEmail()
            );

            $user->setIsVerified(true);
            $this->entityManager->flush();

            $this->addFlash('success', 'Email vérifié avec succès !');
            return $this->redirectToRoute('app_login');

        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());
            return $this->redirectToRoute('app_register');
        }
    }

    public function sendVerificationEmail(User $user, string $routeName): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $routeName,
            (string)$user->getId(),
            $user->getEmail()
        );

        $email = (new TemplatedEmail())
            ->from('noreply@nationsound.com')
            ->to($user->getEmail())
            ->subject('Confirmation de votre email')
            ->htmlTemplate('registration/update_email_confirmation.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAt' => $signatureComponents->getExpiresAt()
            ]);

        $this->mailer->send($email);
    }

    #[Route('/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request, UserRepository $userRepository): Response
    {
        $email = $request->request->get('email');
        
        // Validation CSRF
        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('resend_verification', $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide');
            return $this->redirectToRoute('app_login');
        }

        // Validation de l'email
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Adresse email invalide');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        
        // Gestion des cas manquants
        if (!$user) {
            $this->addFlash('error', 'Aucun compte associé à cette adresse');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('warning', 'Votre compte est déjà vérifié');
            return $this->redirectToRoute('app_login');
        }

        try {
            $this->emailVerifier->sendVerificationEmail($user, 'app_verify_email');
            $this->addFlash('success', 'Nouvel email envoyé !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email');
        }

        return $this->redirectToRoute('app_login');
    }
}
