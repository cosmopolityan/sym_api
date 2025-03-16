<?php

// tests/Service/UserManagerTest.php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private $entityManager;
    private $userRepository;
    private $userManager;

    protected function setUp(): void
    {
        // Создаем моки для зависимостей
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        // Создаем экземпляр сервиса
        $this->userManager = new UserManager($this->entityManager, $this->userRepository);
    }

    public function testCreateUser(): void
    {
        $name = 'John Doe';
        $email = 'john.doe@example.com';
        $role = 'user';

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRole($role);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($user));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $createdUser = $this->userManager->createUser($name, $email, $role);

        $this->assertInstanceOf(User::class, $createdUser);
        $this->assertEquals($name, $createdUser->getName());
        $this->assertEquals($email, $createdUser->getEmail());
        $this->assertEquals($role, $createdUser->getRole());
    }

    public function testUpdateUser(): void
    {
        $user = new User();
        $user->setName('Old Name');
        $user->setEmail('old.email@example.com');
        $user->setRole('old_role');

        $newName = 'New Name';
        $newEmail = 'new.email@example.com';
        $newRole = 'new_role';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $updatedUser = $this->userManager->updateUser($user, $newName, $newEmail, $newRole);

        $this->assertInstanceOf(User::class, $updatedUser);
        $this->assertEquals($newName, $updatedUser->getName());
        $this->assertEquals($newEmail, $updatedUser->getEmail());
        $this->assertEquals($newRole, $updatedUser->getRole());
    }

    public function testDeleteUser(): void
    {
        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('john.doe@example.com');
        $user->setRole('user');

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($user));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userManager->deleteUser($user);
    }

    public function testCanAssignRoleDirectorWhenNoDirectorExists(): void
    {
        $branchId = 1;
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['branch' => $branchId, 'role' => 'director'])
            ->willReturn(null);

        $this->assertTrue($this->userManager->canAssignRoleDirector($branchId));
    }

    public function testCannotAssignRoleDirectorWhenDirectorExists(): void
    {
        $branchId = 1;
        $existingDirector = new User();
        $existingDirector->setRole('director');

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['branch' => $branchId, 'role' => 'director'])
            ->willReturn($existingDirector);

        $this->assertFalse($this->userManager->canAssignRoleDirector($branchId));
    }
}