<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EventControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $eventRepository;
    private string $path = '/event/';

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
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Event index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'event[title]' => 'Testing',
            'event[date]' => 'Testing',
            'event[startTime]' => 'Testing',
            'event[duration]' => 'Testing',
            'event[location]' => 'Testing',
            'event[category]' => 'Testing',
            'event[image]' => 'Testing',
            'event[artists]' => 'Testing',
            'event[mapPoint]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->eventRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Event();
        $fixture->setTitle('My Title');
        $fixture->setDate('My Title');
        $fixture->setStartTime('My Title');
        $fixture->setDuration('My Title');
        $fixture->setLocation('My Title');
        $fixture->setCategory('My Title');
        $fixture->setImage('My Title');
        $fixture->setArtists('My Title');
        $fixture->setMapPoint('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Event');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Event();
        $fixture->setTitle('Value');
        $fixture->setDate('Value');
        $fixture->setStartTime('Value');
        $fixture->setDuration('Value');
        $fixture->setLocation('Value');
        $fixture->setCategory('Value');
        $fixture->setImage('Value');
        $fixture->setArtists('Value');
        $fixture->setMapPoint('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'event[title]' => 'Something New',
            'event[date]' => 'Something New',
            'event[startTime]' => 'Something New',
            'event[duration]' => 'Something New',
            'event[location]' => 'Something New',
            'event[category]' => 'Something New',
            'event[image]' => 'Something New',
            'event[artists]' => 'Something New',
            'event[mapPoint]' => 'Something New',
        ]);

        self::assertResponseRedirects('/event/');

        $fixture = $this->eventRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getTitle());
        self::assertSame('Something New', $fixture[0]->getDate());
        self::assertSame('Something New', $fixture[0]->getStartTime());
        self::assertSame('Something New', $fixture[0]->getDuration());
        self::assertSame('Something New', $fixture[0]->getLocation());
        self::assertSame('Something New', $fixture[0]->getCategory());
        self::assertSame('Something New', $fixture[0]->getImage());
        self::assertSame('Something New', $fixture[0]->getArtists());
        self::assertSame('Something New', $fixture[0]->getMapPoint());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Event();
        $fixture->setTitle('Value');
        $fixture->setDate('Value');
        $fixture->setStartTime('Value');
        $fixture->setDuration('Value');
        $fixture->setLocation('Value');
        $fixture->setCategory('Value');
        $fixture->setImage('Value');
        $fixture->setArtists('Value');
        $fixture->setMapPoint('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/event/');
        self::assertSame(0, $this->eventRepository->count([]));
    }
}
