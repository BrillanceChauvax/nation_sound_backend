<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $userRepository;
    private string $path = '/user';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->manager->getRepository(User::class);
    
        foreach ($this->userRepository->findAll() as $object) {
            $this->manager->remove($object);
        }
        $this->manager->flush();
    
        $testUser = new User();
        $testUser->setEmail('admin@example.com');
        $testUser->setPassword(password_hash('password', PASSWORD_BCRYPT));
        $testUser->setRoles(['ROLE_ADMIN']);
        $testUser->setCreatedAt(new \DateTimeImmutable());
    
        $this->manager->persist($testUser);
        $this->manager->flush();
    
        $this->client->loginUser($testUser);
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User index');
    }

    public function testNew(): void
    {
        $crawler = $this->client->request('GET', '/user/new');
        self::assertResponseIsSuccessful();

        $csrfToken = $crawler->filter('input[name="user[_token]"]')->attr('value');

        $this->client->submitForm('Save', [
            'user[email]' => 'test@example.com',
            'user[password]' => 'password',
            'user[roles]' => [], 
            'user[_token]' => $csrfToken
        ]);

        self::assertResponseRedirects('/user');
        $user = $this->userRepository->findAll()[0];
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertNotNull($user->getCreatedAt());
        self::assertSame(1, $this->userRepository->count(['email' => 'test@example.com']));
    }

    public function testShow(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setRoles([]);
        $user->setCreatedAt(new \DateTimeImmutable());
        $this->manager->persist($user);
        $this->manager->flush();

        $this->client->request('GET', '/user/'.$user->getId());

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User');
    }

    public function testEdit(): void
    {
        // Création d'un utilisateur 
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword(password_hash('password', PASSWORD_DEFAULT));
        $user->setRoles(['ROLE_ADMIN']);
        $this->manager->persist($user);
        $this->manager->flush();

        // Récupération du formulaire d'édition
        $crawler = $this->client->request('GET', '/user/'.$user->getId().'/edit');
        self::assertResponseIsSuccessful();
        
        // Extraction du token CSRF
        $csrfToken = $crawler->filter('input[name="user[_token]"]')->attr('value');

        // Soumission du formulaire avec données valides
        $this->client->submitForm('Update', [
            'user[email]' => 'updated@example.com',
            'user[password]' => 'new_password',
            'user[roles]' => ['ROLE_ADMIN'], 
            'user[_token]' => $csrfToken 
        ]);

        // Vérification de la redirection
        self::assertResponseRedirects('/user');

        // Récupération de l'utilisateur mis à jour
        $updatedUser = $this->userRepository->find($user->getId());

        // Assertions des valeurs modifiées
        self::assertSame('updated@example.com', $updatedUser->getEmail());
        self::assertTrue(password_verify('new_password', $updatedUser->getPassword()));
        self::assertContains('ROLE_ADMIN', $updatedUser->getRoles());
    }


    public function testRemove(): void
    {
        $user = new User();
        $user->setEmail('Value');
        $user->setPassword('Value');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setCreatedAt(new \DateTimeImmutable());
        $this->manager->persist($user);
        $this->manager->flush();

        $this->client->request('GET', '/user/'.$user->getId());
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/user');
        self::assertSame(0, $this->userRepository->count(['email' => 'Value']));
    }
}
