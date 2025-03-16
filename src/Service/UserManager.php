<?php

// src/Service/UserManager.php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserManager
{
    private $entityManager;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    public function createUser(string $name, string $email, string $role): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRole($role);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser(User $user, string $name, string $email, string $role): User
    {
        $user->setName($name);
        $user->setEmail($email);
        $user->setRole($role);

        $this->entityManager->flush();

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function canAssignRoleDirector(int $branchId): bool
    {
        $director = $this->userRepository->findOneBy(['branch' => $branchId, 'role' => 'director']);
        return $director === null;
    }
}