<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Branch;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private $entityManager;
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Очистка базы данных перед каждым тестом
        $this->truncateEntities([
            User::class,
            Branch::class,
        ]);
    }

    public function testIndex(): void
    {
        $this->client->request('GET', '/users');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testShow(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setRole('user');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($user->getId(), $responseData['id']);
        $this->assertEquals('Test User', $responseData['name']);
        $this->assertEquals('test@example.com', $responseData['email']);
        $this->assertEquals('user', $responseData['role']);
    }

    public function testCreate(): void
    {
        $this->client->request('POST', '/users', [], [], [], json_encode([
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'user',
        ]));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('New User', $responseData['name']);
        $this->assertEquals('newuser@example.com', $responseData['email']);
        $this->assertEquals('user', $responseData['role']);
    }

    public function testUpdate(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setRole('user');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('PUT', '/users/' . $user->getId(), [], [], [], json_encode([
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'role' => 'admin',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated User', $responseData['name']);
        $this->assertEquals('updated@example.com', $responseData['email']);
        $this->assertEquals('admin', $responseData['role']);
    }

    public function testDelete(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setRole('user');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('DELETE', '/users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User deleted', $responseData['message']);
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
