<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

class RegistrationControllerTest extends WebTestCase
{
    use MailerAssertionsTrait;
    private $client;
    private $manager;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->manager->getRepository(User::class);
        
        // Réinitialiser la base de test
        foreach ($this->userRepository->findAll() as $user) {
            $this->manager->remove($user);
        }
        $this->manager->flush();
    }

    public function testRegister(): void
    {
        // Inscription
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Register', [
            'registration_form[email]' => 'user@example.com',
            'registration_form[plainPassword]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        // Vérification redirection
        self::assertResponseRedirects('/user');
        $this->client->followRedirect();

        // Vérification utilisateur non vérifié
        $user = $this->userRepository->findOneByEmail('user@example.com');
        self::assertFalse($user->isVerified());

        // Vérification email envoyé
        self::assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailHeaderSame($email, 'Subject', 'Confirmation email');

        // Extraction lien de vérification
        $verificationUrl = $this->extractVerificationUrl($email->getHtmlBody());

        // Clique sur le lien
        $this->client->request('GET', $verificationUrl);
        self::assertResponseRedirects('/');
        
        // Vérification utilisateur vérifié
        $this->manager->refresh($user);
        self::assertTrue($user->isVerified());
    }

    private function extractVerificationUrl(string $htmlBody): string
    {
        preg_match('/href="([^"]+verify[^"]+)"/', $htmlBody, $matches);
        return $matches[1] ?? '';
    }
}
