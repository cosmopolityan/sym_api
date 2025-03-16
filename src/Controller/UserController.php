<?php

// src/Controller/UserController.php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Branch;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class UserController extends AbstractController
{
    #[Route('/users', name: 'user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, SerializerInterface $serializer): Response
    {
        // Загрузка пользователей с их филиалами
        $users = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.branch', 'b')
            ->addSelect('b')
            ->getQuery()
            ->getResult();

        if (empty($users)) {
            return $this->json(['message' => 'No users found'], Response::HTTP_NOT_FOUND);
        }

        $data = array_map(function ($user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'branch_id' => $user->getBranch() ? $user->getBranch()->getId() : null,
            ];
        }, $users);

        return $this->json($data);
    }

    #[Route('/users/{id}', name: 'user_show', methods: ['GET'])]
    public function show(UserRepository $userRepository, SerializerInterface $serializer, int $id): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'branch_id' => $user->getBranch() ? $user->getBranch()->getId() : null,
        ];

        return $this->json($data);
    }

    #[Route('/users', name: 'user_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, SerializerInterface $serializer): Response
{
    $data = json_decode($request->getContent(), true);

    $user = new User();
    $user->setName($data['name']);
    $user->setEmail($data['email']);
    $user->setRole($data['role']);
    if (isset($data['branch_id'])) {
        $user->setBranch($entityManager->getReference(Branch::class, $data['branch_id']));
    }

    $errors = $validator->validate($user);
    if (count($errors) > 0) {
        return $this->json((string) $errors, Response::HTTP_BAD_REQUEST);
    }

    $entityManager->persist($user);
    $entityManager->flush();

    $userData = [
        'id' => $user->getId(),
        'name' => $user->getName(),
        'email' => $user->getEmail(),
        'role' => $user->getRole(),
        'branch_id' => $user->getBranch() ? $user->getBranch()->getId() : null,
    ];

    $jsonContent = $serializer->serialize($userData, 'json');

    return new Response($jsonContent, 201, [
        'Content-Type' => 'application/json'
    ]);
}

    #[Route('/users/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator, int $id): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Проверка на наличие директора в филиале
        if (isset($data['role']) && $data['role'] === 'director' && isset($data['branch_id'])) {
            $branch = $entityManager->getReference(Branch::class, $data['branch_id']);
            $existingDirector = $branch->getDirector();
            if ($existingDirector && $existingDirector->getId() !== $user->getId()) {
                return $this->json(['message' => 'A director already exists for this branch'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Обновление данных пользователя
        $user->setName($data['name'] ?? $user->getName());
        $user->setEmail($data['email'] ?? $user->getEmail());
        if (isset($data['role']) && $data['role'] !== 'director') {
            $user->setRole($data['role']);
        }

        // Обработка branch_id
        if (array_key_exists('branch_id', $data)) {
            if ($data['branch_id'] === null) {
                $user->setBranch(null);
            } else {
                $user->setBranch($entityManager->getReference(Branch::class, $data['branch_id']));
            }
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        // Подготовка данных для ответа
        $responseData = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'branch_id' => $user->getBranch() ? $user->getBranch()->getId() : null,
        ];

        return $this->json($responseData, Response::HTTP_OK);
    }

    #[Route('/users/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(UserRepository $userRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Проверка на наличие связанных филиалов
        if ($user->getBranch()) {
            return $this->json(['message' => 'Cannot delete user because it is associated with a branch'], Response::HTTP_BAD_REQUEST);
        }

        // Удаляем пользователя
        $entityManager->remove($user);
        $entityManager->flush();

        // Проверяем, был ли пользователь удален
        if (!$userRepository->find($id)) {
            return $this->json(['message' => 'User deleted'], Response::HTTP_OK);
        } else {
            return $this->json(['message' => 'Failed to delete user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}