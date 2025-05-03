<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Entity\Artist;
use App\Entity\MapPoint;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EventControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $eventRepository;
    private string $path = '/event';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->eventRepository = $this->manager->getRepository(Event::class);

        foreach ($this->eventRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Event index');
    }

    public function testNew(): void
    {
        $mapPoint = new MapPoint();
        $mapPoint->setType('scene'); 
        $mapPoint->setName('Point 1');
        $mapPoint->setPosition('A1');
        $mapPoint->setDescription('text');
        $mapPoint->setColor('blue');
        $this->manager->persist($mapPoint);
        $this->manager->flush();

        $crawler = $this->client->request('GET', '/event/new');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="event"]');

        $csrfToken = $crawler->filter('input[name="event[_token]"]')->attr('value');
        self::assertNotEmpty($csrfToken, 'CSRF token manquant dans le formulaire');

        $this->client->submitForm('Save', [
            'event[title]' => 'Concert',
            'event[date]' => '2025-10-10',
            'event[startTime]' => '18:30',
            'event[duration]' => 120,
            'event[location]' => 'Salle 1',
            'event[category]' => 'Rock',
            'event[image]' => 'concert.jpg',
            'event[mapPoint]' => $mapPoint->getId(),
            'event[_token]' => $csrfToken
        ]);

        self::assertResponseRedirects('/event');
        self::assertSame(1, $this->eventRepository->count([]));
    }

    public function testShow(): void
    {
        $artist = new Artist();
        $artist->setName('Test Artist');
        $artist->setImage('artiste.jpg');
        $this->manager->persist($artist);
        $this->manager->flush();

        $mapPoint = new MapPoint();
        $mapPoint->setType('scene'); 
        $mapPoint->setName('Point 1');
        $mapPoint->setPosition('A1');
        $mapPoint->setDescription('text');
        $mapPoint->setColor('blue');
        $this->manager->persist($mapPoint);

        $event = new Event();
        $event->setTitle('TitleTest');
        $event->setDate(new \DateTime());
        $event->setStartTime(new \DateTime());
        $event->setDuration(1);
        $event->setLocation('LocationTest');
        $event->setCategory('categoryTest');
        $event->setImage('concert.jpg');
        $event->addArtist($artist);
        $event->setMapPoint($mapPoint);

        $this->manager->persist($event);
        $this->manager->flush();

        $this->client->request('GET', '/event/'.$event->getId());
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Event');
    }

    public function testEdit(): void
    {
        $artist = new Artist();
        $artist->setName('Test Artist');
        $artist->setImage('artiste.jpg');
        $this->manager->persist($artist);
        
        $mapPoint = new MapPoint();
        $mapPoint->setType('scene'); 
        $mapPoint->setName('Point 1');
        $mapPoint->setPosition('A1');
        $mapPoint->setDescription('text');
        $mapPoint->setColor('blue');
        $this->manager->persist($mapPoint);

        $event = new Event();
        $event->setTitle('Value');
        $event->setDate(new \DateTime());
        $event->setStartTime(new \DateTime());
        $event->setDuration(2);
        $event->setLocation('Value');
        $event->setCategory('Value');
        $event->setImage('concert.jpg');
        $event->addArtist($artist);
        $event->setMapPoint($mapPoint);
        $this->manager->persist($event);
        $this->manager->flush();

        $crawler = $this->client->request('GET', '/event/'.$event->getId().'/edit');
        $csrfToken = $crawler->filter('input[name="event[_token]"]')->attr('value');

        $this->client->submitForm('Update', [
            'event[title]' => 'Nouveau titre',
            'event[date]' => '2025-11-15',
            'event[startTime]' => '20:00',
            'event[duration]' => 180,
            'event[location]' => 'Nouveau lieu',
            'event[category]' => 'Nouvelle catégorie',
            'event[image]' => 'nouveau-concert.jpg',
            'event[artists]' => [$artist->getId()],
            'event[mapPoint]' => $mapPoint->getId(),
            'event[_token]' => $csrfToken
        ]);

        self::assertResponseRedirects('/event');

        $updatedEvent = $this->eventRepository->find($event->getId());

        self::assertSame('Nouveau titre', $updatedEvent->getTitle());
        self::assertSame('2025-11-15', $updatedEvent->getDate()->format('Y-m-d'));
        self::assertSame('20:00', $updatedEvent->getStartTime()->format('H:i'));
        self::assertSame(180, $updatedEvent->getDuration());
        self::assertSame('Nouveau lieu', $updatedEvent->getLocation());
        self::assertSame('Nouvelle catégorie', $updatedEvent->getCategory());
        self::assertSame('nouveau-concert.jpg', $updatedEvent->getImage());
        self::assertCount(1, $updatedEvent->getArtists());
        self::assertSame($mapPoint->getId(), $updatedEvent->getMapPoint()->getId());
    }

    public function testRemove(): void
    {
        $artist = new Artist();
        $artist->setName('Test Artist');
        $artist->setImage('artiste.jpg');
        $this->manager->persist($artist);
        
        $mapPoint = new MapPoint();
        $mapPoint->setType('scene'); 
        $mapPoint->setName('Point 1');
        $mapPoint->setPosition('A1');
        $mapPoint->setDescription('text');
        $mapPoint->setColor('blue');
        $this->manager->persist($mapPoint);

        $event = new Event();
        $event->setTitle('Value');
        $event->setDate(new \DateTime());
        $event->setStartTime(new \DateTime());
        $event->setDuration(2);
        $event->setLocation('Value');
        $event->setCategory('Value');
        $event->setImage('concert.jpg');
        $event->addArtist($artist);
        $event->setMapPoint($mapPoint);
        $this->manager->persist($event);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s/%s', $this->path, $event->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/event');
        self::assertSame(0, $this->eventRepository->count([]));
    }
}
