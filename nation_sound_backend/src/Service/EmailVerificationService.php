<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EmailVerificationService
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer
    ) {}

    public function sendVerificationEmail(User $user, string $routeName, ?string $newEmail = null,?string $template = null): void
    {

        $context = ['id' => (string)$user->getId()];
    
        if ($newEmail) {
        $context['new_email'] = $newEmail;
        $template = $template ?? 'user/update_email_confirmation.html.twig';
        } else {
            $template = $template ?? 'registration/confirmation_email.html.twig';
        }

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $routeName,
            (string)$user->getId(),
            $user->getEmail(),
            $context,
        );

        $email = (new TemplatedEmail())
            ->from('noreply@nationsound.com')
            ->to($user->getEmail())
            ->subject($newEmail ? 'Confirmation de changement d\'email' : 'Confirmation d\'inscription')
            ->htmlTemplate($template)
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAt' => $signatureComponents->getExpiresAt(),
                'newEmail' => $newEmail
            ]);

        $this->mailer->send($email);
    }
}