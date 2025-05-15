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

class EmailVerifierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer
    ) {}

    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        try {
            $user = $userRepository->find($request->query->get('id'));
            
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

    #[Route('/resend-verification', name: 'app_resend_verification')]

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

    public function resendVerification(Request $request, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['email' => $request->query->get('email')]);
        
        if ($user && !$user->isVerified()) {
            $this->sendVerificationEmail($user, 'app_verify_email');
            $this->addFlash('success', 'Nouvel email envoyé !');
        }

        return $this->redirectToRoute('app_login');
    }
}

