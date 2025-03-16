<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\Branch;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // тестовые данные
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');
        $manager->persist($user);

        $branch = new Branch();
        $branch->setName('Test Branch');
        $manager->persist($branch);

        $manager->flush();
    }
}