<?php

namespace App\Tests\Controller;

use App\Entity\Information;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InformationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $informationRepository;
    private string $path = '/information';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->informationRepository = $this->manager->getRepository(Information::class);

        foreach ($this->informationRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Information index');
    }

    public function testNew(): void
    {
        $crawler = $this->client->request('GET', '/information/new');
        self::assertResponseIsSuccessful();
        
        $csrfToken = $crawler->filter('input[name="information[_token]"]')->attr('value');
        self::assertNotEmpty($csrfToken, 'Le token CSRF est manquant.');

        $this->client->submitForm('Save', [
            'information[title]' => 'Titre test',
            'information[content]' => 'Contenu test',
            'information[isUrgent]' => '1', // Case cochée, sinon 0
            'information[publishedAt]' => '2025-05-01T12:00',
            'information[_token]' => $csrfToken
        ]);

        self::assertResponseRedirects('/information');
        self::assertSame(1, $this->informationRepository->count([]));
    }

    public function testShow(): void
    {
        $information = new Information();
        $information->setTitle('My Title');
        $information->setContent('My Title');
        $information->setIsUrgent(true);
        $information->setPublishedAt(new \DateTime());

        $this->manager->persist($information);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s/%s', $this->path, $information->getId()));
        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Information');
    }

    public function testEdit(): void
    {
        $information = new Information();
        $information->setTitle('Ancien titre');
        $information->setContent('Ancien contenu');
        $information->setIsUrgent(false);
        $information->setPublishedAt(new \DateTime('2024-05-01'));
        $this->manager->persist($information);
        $this->manager->flush();

        $crawler = $this->client->request('GET', "/information/{$information->getId()}/edit");
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="information"]');
        
        $csrfToken = $crawler->filter('input[name="information[_token]"]')->attr('value');

        $this->client->submitForm('Update', [
            'information[title]' => 'Nouveau titre',
            'information[content]' => 'Nouveau contenu',
            'information[isUrgent]' => '1',
            'information[publishedAt]' => '2025-05-01T14:30',
            'information[_token]' => $csrfToken
        ]);

        self::assertResponseRedirects('/information');

        $updatedInformation = $this->informationRepository->find($information->getId());

        self::assertSame('Nouveau titre', $updatedInformation->getTitle());
        self::assertSame('Nouveau contenu', $updatedInformation->getContent());
        self::assertTrue($updatedInformation->isUrgent());
        self::assertEquals('2025-05-01T14:30', $updatedInformation->getPublishedAt()->format('Y-m-d\TH:i'));
    }

    public function testRemove(): void
    {
        $information = new Information();
        $information->setTitle('Value');
        $information->setContent('');
        $information->setIsUrgent(true);
        $information->setPublishedAt(new \DateTime());
        $this->manager->persist($information);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s/%s', $this->path, $information->getId()));
        $this->client->submitForm('Delete');
        self::assertResponseRedirects('/information');
        self::assertSame(0, $this->informationRepository->count([]));
    }
}
