<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Création du formulaire avec l'email prérempli si besoin
        $form = $this->createForm(LoginForm::class, [
            'email' => $authenticationUtils->getLastUsername()
        ]);

        // Si l'utilisateur est déjà connecté
        $user = $this->getUser();
        if ($user instanceof User) {
            $this->entityManager->refresh($user);
            if (!$user->isVerified()) {
                $this->addFlash('error', 'Veuillez vérifier votre email avant de vous connecter.');
                return $this->redirectToRoute('app_resend_verification', [
                    'email' => $user->getEmail()
                ]);
            }
            return $this->redirectToRoute('app_user_edit');
        }

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'error' => $authenticationUtils->getLastAuthenticationError()
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Déconnexion');
    }
}
