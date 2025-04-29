<?php

namespace App\Tests\Controller;

use App\Entity\MapPoint;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MapPointControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $mapPointRepository;
    private string $path = '/map/point/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->mapPointRepository = $this->manager->getRepository(MapPoint::class);

        foreach ($this->mapPointRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('MapPoint index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'map_point[type]' => 'Testing',
            'map_point[name]' => 'Testing',
            'map_point[position]' => 'Testing',
            'map_point[description]' => 'Testing',
            'map_point[color]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->mapPointRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new MapPoint();
        $fixture->setType('My Title');
        $fixture->setName('My Title');
        $fixture->setPosition('My Title');
        $fixture->setDescription('My Title');
        $fixture->setColor('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('MapPoint');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new MapPoint();
        $fixture->setType('Value');
        $fixture->setName('Value');
        $fixture->setPosition('Value');
        $fixture->setDescription('Value');
        $fixture->setColor('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'map_point[type]' => 'Something New',
            'map_point[name]' => 'Something New',
            'map_point[position]' => 'Something New',
            'map_point[description]' => 'Something New',
            'map_point[color]' => 'Something New',
        ]);

        self::assertResponseRedirects('/map/point/');

        $fixture = $this->mapPointRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getType());
        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getPosition());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getColor());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new MapPoint();
        $fixture->setType('Value');
        $fixture->setName('Value');
        $fixture->setPosition('Value');
        $fixture->setDescription('Value');
        $fixture->setColor('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/map/point/');
        self::assertSame(0, $this->mapPointRepository->count([]));
    }
}
