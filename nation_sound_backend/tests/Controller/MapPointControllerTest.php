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
    private string $path = '/map/point';

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
        $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('MapPoint index');
    }

    public function testNew(): void
    {
        $crawler = $this->client->request('GET', '/map/point/new');
        $csrfToken = $crawler->filter('input[name="map_point[_token]"]')->attr('value');

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'map_point[type]' => 'Testing',
            'map_point[name]' => 'Testing',
            'map_point[position]' => 'Testing',
            'map_point[description]' => 'Testing',
            'map_point[color]' => 'Testing',
            'map_point[_token]' => $csrfToken,
        ]);

        self::assertResponseRedirects($this->path);
        self::assertSame(1, $this->mapPointRepository->count([]));
    }

    public function testShow(): void
    {
        $mapPoint = new MapPoint();
        $mapPoint->setType('My Title');
        $mapPoint->setName('My Title');
        $mapPoint->setPosition('My Title');
        $mapPoint->setDescription('My Title');
        $mapPoint->setColor('My Title');
        $this->manager->persist($mapPoint);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s/%s', $this->path, $mapPoint->getId()));
        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('MapPoint');
    }

    public function testEdit(): void
    {
        $mapPoint = new MapPoint();
        $mapPoint->setType('Value');
        $mapPoint->setName('Value');
        $mapPoint->setPosition('Value');
        $mapPoint->setDescription('Value');
        $mapPoint->setColor('Value');
        $this->manager->persist($mapPoint);
        $this->manager->flush();

        $crawler = $this->client->request('GET', sprintf('%s/%s/edit', $this->path, $mapPoint->getId()));
        $csrfToken = $crawler->filter('input[name="map_point[_token]"]')->attr('value');

        $this->client->submitForm('Update', [
            'map_point[type]' => 'Something New',
            'map_point[name]' => 'Something New',
            'map_point[position]' => 'Something New',
            'map_point[description]' => 'Something New',
            'map_point[color]' => 'Something New',
            'map_point[_token]' => $csrfToken,
        ]);

        self::assertResponseRedirects('/map/point');

        $newMapPoint = $this->mapPointRepository->findAll();
        self::assertSame('Something New', $newMapPoint[0]->getType());
        self::assertSame('Something New', $newMapPoint[0]->getName());
        self::assertSame('Something New', $newMapPoint[0]->getPosition());
        self::assertSame('Something New', $newMapPoint[0]->getDescription());
        self::assertSame('Something New', $newMapPoint[0]->getColor());
    }

    public function testRemove(): void
    {
        $mapPoint = new MapPoint();
        $mapPoint->setType('Value');
        $mapPoint->setName('Value');
        $mapPoint->setPosition('Value');
        $mapPoint->setDescription('Value');
        $mapPoint->setColor('Value');
        $this->manager->persist($mapPoint);
        $this->manager->flush();

        $crawler = $this->client->request('GET', sprintf('%s/%s', $this->path, $mapPoint->getId()));
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');
        $this->client->submitForm('Delete', ['_token' => $csrfToken]);

        self::assertResponseRedirects('/map/point');
        self::assertSame(0, $this->mapPointRepository->count([]));
    }
}
