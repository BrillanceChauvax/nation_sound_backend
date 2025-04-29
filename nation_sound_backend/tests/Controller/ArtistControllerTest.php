<?php

namespace App\Tests\Controller;

use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArtistControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $artistRepository;
    private string $path = '/artist/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->artistRepository = $this->manager->getRepository(Artist::class);

        foreach ($this->artistRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Artist index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        //$this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'artist[name]' => 'Testing',
            'artist[image]' => 'Testing',
            'artist[events]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->artistRepository->count([]));
    }

    public function testShow(): void
    {
        //$this->markTestIncomplete();
        $fixture = new Artist();
        $fixture->setName('My Title');
        $fixture->setImage('My Title');
        $fixture->setEvents('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Artist');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        //$this->markTestIncomplete();
        $fixture = new Artist();
        $fixture->setName('Value');
        $fixture->setImage('Value');
        $fixture->setEvents('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'artist[name]' => 'Something New',
            'artist[image]' => 'Something New',
            'artist[events]' => 'Something New',
        ]);

        self::assertResponseRedirects('/artist/');

        $fixture = $this->artistRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getImage());
        self::assertSame('Something New', $fixture[0]->getEvents());
    }

    public function testRemove(): void
    {
        //$this->markTestIncomplete();
        $fixture = new Artist();
        $fixture->setName('Value');
        $fixture->setImage('Value');
        $fixture->setEvents('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/artist/');
        self::assertSame(0, $this->artistRepository->count([]));
    }
}
