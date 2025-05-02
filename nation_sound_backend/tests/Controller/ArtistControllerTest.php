<?php

namespace App\Tests\Controller;

use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Event;

final class ArtistControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $artistRepository;
    private string $path = '/artist/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->artistRepository = $this->manager->getRepository(Artist::class);

        foreach ($this->artistRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();

        $schemaTool = new SchemaTool($this->manager);
        $metadata = $this->manager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Artist index');
    }

    public function testNew(): void
    {
        $event = new Event();
        $event->setTitle('Concert Test');
        $event->setDate(new \DateTime()); 
        $event->setStartTime(new \DateTime()); 
        $event->setDuration(120); 
        $event->setLocation('Main Stage'); 
        $event->setCategory('Rock'); 
        $event->setImage('concert.jpg'); 
        $this->manager->persist($event);
        $this->manager->flush();

        $crawler = $this->client->request('GET', '/artist/new');
        self::assertResponseIsSuccessful(200);
        self::assertSelectorExists('form[name="artist"]');

        $csrfToken = $crawler->filter('input[name="artist[_token]"]')->attr('value');
        self::assertNotEmpty($csrfToken, 'CSRF token non trouvé dans le formulaire.');
        
        $this->client->submitForm('Save', [
            'artist[name]' => 'Nouvel Artiste',
            'artist[events]' => [$event->getId()], // Tableau d'IDs
            'artist[image]' => 'photo.jpg',
            'artist[_token]' => $csrfToken
        ]);

        self::assertResponseRedirects('/artist');
        self::assertSame(1, $this->artistRepository->count([]));
    }

    public function testShow(): void
    {
        $sharedImage = 'concert.jpg';

        $event = new Event();
        $event->setTitle('Concert Test');
        $event->setDate(new \DateTime());
        $event->setStartTime(new \DateTime()); 
        $event->setDuration(120); 
        $event->setLocation('Main Stage'); 
        $event->setCategory('Rock'); 
        $event->setImage($sharedImage); 
        $this->manager->persist($event);

        $artist = new Artist();
        $artist->setName('Test Artist');
        $artist->setImage($sharedImage);
        $artist->addEvent($event); 
        $this->manager->persist($artist);
        $this->manager->flush();

        $this->client->request('GET', $this->path.$artist->getId());
        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Artist');
    }

    public function testEdit(): void
{
    // Création d'un événement 
    $event = new Event();
    $event->setTitle('Concert Test');
    $event->setDate(new \DateTime());
    $event->setStartTime(new \DateTime());
    $event->setDuration(120);
    $event->setLocation('Main Stage');
    $event->setCategory('Rock');
    $event->setImage('concert.jpg');
    $this->manager->persist($event);
    $this->manager->flush();

    // Création de l'artiste avec relation
    $artist = new Artist();
    $artist->setName('Value');
    $artist->setImage('image.jpg');
    $artist->addEvent($event); 
    $this->manager->persist($artist);
    $this->manager->flush();

    // Récupération du formulaire d'édition
    $crawler = $this->client->request('GET', '/artist/'.$artist->getId().'/edit');
    self::assertSelectorExists('form[name="artist"]');
    
    // Extraction du token CSRF
    $csrfToken = $crawler->filter('input[name="artist[_token]"]')->attr('value');
    self::assertNotEmpty($csrfToken, 'Le token CSRF est manquant dans le formulaire');

    // Soumission du formulaire
    $this->client->submitForm('Update', [
        'artist[name]' => 'Nouveau nom',
        'artist[image]' => 'nouvelle-image.jpg',
        'artist[events]' => [$event->getId()], // Tableau d'IDs
        'artist[_token]' => $csrfToken
    ]);

    // Vérifications
    self::assertResponseRedirects('/artist');
    $updatedArtist = $this->artistRepository->find($artist->getId());
    self::assertCount(1, $updatedArtist->getEvents());
    self::assertSame('Nouveau nom', $updatedArtist->getName());
}

public function testRemove(): void
{
    // Création d'un événement et artiste associé
    $event = new Event();
    $event->setTitle('Concert Test');
    $event->setDate(new \DateTime());
    $event->setStartTime(new \DateTime());
    $event->setDuration(120);
    $event->setLocation('Main Stage');
    $event->setCategory('Rock');
    $event->setImage('concert.jpg'); 
    $this->manager->persist($event);

    $artist = new Artist();
    $artist->setName('Artiste à supprimer');
    $artist->addEvent($event);
    $artist->setImage('concert.jpg');
    $this->manager->persist($artist);
    $this->manager->flush();

    // Extraction du token CSRF depuis la page de détail
    // Accès à la page de détail de l'artiste
    $crawler = $this->client->request('GET', '/artist/'.$artist->getId());
    self::assertResponseIsSuccessful(); // Vérifie que la page est chargée

    $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');
    self::assertNotEmpty($csrfToken, 'Le token CSRF est manquant dans le formulaire.');

    // Soumission de la suppression
    $this->client->submitForm('Delete', ['_token' => $csrfToken]);

    // Vérifications
    self::assertResponseRedirects('/artist');
    self::assertSame(0, $this->artistRepository->count([]));
    
    // Vérifier que l'événement existe toujours (relation ManyToMany)
    self::assertSame(1, $this->manager->getRepository(Event::class)->count([]));
}
}
