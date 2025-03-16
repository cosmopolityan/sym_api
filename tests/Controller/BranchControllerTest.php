<?php

namespace App\Tests\Controller;

use App\Entity\Branch;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BranchControllerTest extends WebTestCase
{
    private $entityManager;
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Очистка базы данных перед каждым тестом
        $this->truncateEntities([
            Branch::class,
            User::class,
        ]);
    }

    public function testIndex(): void
    {
        // Создаем филиал для теста
        $branch = new Branch();
        $branch->setName('Test Branch');
        $branch->setAddress('Test Address');
        $this->entityManager->persist($branch);
        $this->entityManager->flush();

        $this->client->request('GET', '/branches');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData);
    }

    public function testShow(): void
    {
        $branch = new Branch();
        $branch->setName('Test Branch');
        $branch->setAddress('Test Address');
        $this->entityManager->persist($branch);
        $this->entityManager->flush();

        $this->client->request('GET', '/branches/' . $branch->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($branch->getId(), $responseData['id']);
        $this->assertEquals('Test Branch', $responseData['name']);
        $this->assertEquals('Test Address', $responseData['address']);
    }

    public function testCreate(): void
    {
        $this->client->request('POST', '/branches', [], [], [], json_encode([
            'name' => 'New Branch',
            'address' => 'New Address',
        ]));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('New Branch', $responseData['name']);
        $this->assertEquals('New Address', $responseData['address']);
    }

    public function testUpdate(): void
    {
        $branch = new Branch();
        $branch->setName('Test Branch');
        $branch->setAddress('Test Address');
        $this->entityManager->persist($branch);
        $this->entityManager->flush();

        $this->client->request('PUT', '/branches/' . $branch->getId(), [], [], [], json_encode([
            'name' => 'Updated Branch',
            'address' => 'Updated Address',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Branch', $responseData['name']);
        $this->assertEquals('Updated Address', $responseData['address']);
    }

    public function testDelete(): void
    {
        $branch = new Branch();
        $branch->setName('Test Branch');
        $branch->setAddress('Test Address');
        $this->entityManager->persist($branch);
        $this->entityManager->flush();

        $this->client->request('DELETE', '/branches/' . $branch->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Branch deleted', $responseData['message']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function truncateEntities(array $entities): void
    {
        $connection = $this->entityManager->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                $this->entityManager->getClassMetadata($entity)->getTableName(),
                true
            );
            $connection->executeStatement($query);
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
