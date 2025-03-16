<?php

// src/Service/BranchManager.php

namespace App\Service;

use App\Entity\Branch;
use App\Repository\BranchRepository;
use Doctrine\ORM\EntityManagerInterface;

class BranchManager
{
    private $entityManager;
    private $branchRepository;

    public function __construct(EntityManagerInterface $entityManager, BranchRepository $branchRepository)
    {
        $this->entityManager = $entityManager;
        $this->branchRepository = $branchRepository;
    }

    public function createBranch(string $name, string $address): Branch
    {
        $branch = new Branch();
        $branch->setName($name);
        $branch->setAddress($address);

        $this->entityManager->persist($branch);
        $this->entityManager->flush();

        return $branch;
    }

    public function updateBranch(Branch $branch, string $name, string $address): Branch
    {
        $branch->setName($name);
        $branch->setAddress($address);

        $this->entityManager->flush();

        return $branch;
    }

    public function deleteBranch(Branch $branch): void
    {
        if ($this->branchHasUsers($branch)) {
            throw new \Exception('Cannot delete branch because it has associated users');
        }

        $this->entityManager->remove($branch);
        $this->entityManager->flush();
    }

    private function branchHasUsers(Branch $branch): bool
    {
        return count($branch->getUsers()) > 0;
    }
}