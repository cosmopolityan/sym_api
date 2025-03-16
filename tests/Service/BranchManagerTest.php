<?php

// tests/Service/BranchManagerTest.php

namespace App\Tests\Service;

use App\Entity\Branch;
use App\Entity\User;
use App\Repository\BranchRepository;
use App\Service\BranchManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BranchManagerTest extends TestCase
{
    private $entityManager;
    private $branchRepository;
    private $branchManager;

    protected function setUp(): void
    {
        // Создаем моки для зависимостей
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->branchRepository = $this->createMock(BranchRepository::class);

        // Создаем экземпляр сервиса
        $this->branchManager = new BranchManager($this->entityManager, $this->branchRepository);
    }

    public function testCreateBranch(): void
    {
        $name = 'Head Office';
        $address = '123 Main St, City';

        $branch = new Branch();
        $branch->setName($name);
        $branch->setAddress($address);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($branch));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $createdBranch = $this->branchManager->createBranch($name, $address);

        $this->assertInstanceOf(Branch::class, $createdBranch);
        $this->assertEquals($name, $createdBranch->getName());
        $this->assertEquals($address, $createdBranch->getAddress());
    }

    public function testUpdateBranch(): void
    {
        $branch = new Branch();
        $branch->setName('Old Name');
        $branch->setAddress('Old Address');

        $newName = 'New Name';
        $newAddress = 'New Address';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $updatedBranch = $this->branchManager->updateBranch($branch, $newName, $newAddress);

        $this->assertInstanceOf(Branch::class, $updatedBranch);
        $this->assertEquals($newName, $updatedBranch->getName());
        $this->assertEquals($newAddress, $updatedBranch->getAddress());
    }

    public function testDeleteBranchWithUsers(): void
    {
        $branch = new Branch();
        $branch->setName('Head Office');
        $branch->setAddress('123 Main St, City');

        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('john.doe@example.com');
        $user->setRole('user');
        $user->setBranch($branch);

        $branch->addUser($user);

        $this->entityManager->expects($this->never())
            ->method('remove');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete branch because it has associated users');

        $this->branchManager->deleteBranch($branch);
    }

    public function testDeleteBranchWithoutUsers(): void
    {
        $branch = new Branch();
        $branch->setName('Head Office');
        $branch->setAddress('123 Main St, City');

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($branch));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->branchManager->deleteBranch($branch);
    }
}