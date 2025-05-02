<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\Tools\SchemaTool;


abstract class BaseControllerTest extends WebTestCase
{
    protected $client;
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->manager = self::getContainer()->get('doctrine')->getManager();
        $this->clearDatabase();

        self::assertResponseIsSuccessful();
    }

    private function clearDatabase(): void
    {
        $schemaTool = new SchemaTool($this->manager);
        $metadata = $this->manager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        self::assertResponseIsSuccessful();
    }
}
