<?php

// src/Controller/BranchController.php

namespace App\Controller;

use App\Entity\Branch;
use App\Entity\User;
use App\Repository\BranchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class BranchController extends AbstractController
{
    #[Route('/branches', name: 'branch_index', methods: ['GET'])]
    public function index(BranchRepository $branchRepository, SerializerInterface $serializer): Response
    {
        $branches = $branchRepository->createQueryBuilder('b')
            ->leftJoin('b.director', 'd')
            ->addSelect('d')
            ->getQuery()
            ->getResult();

        $data = array_map(function (Branch $branch) {
            return [
                'id' => $branch->getId(),
                'name' => $branch->getName(),
                'address' => $branch->getAddress(),
                'director' => $branch->getDirector() ? [
                    'id' => $branch->getDirector()->getId(),
                    'name' => $branch->getDirector()->getName(),
                ] : null,
            ];
        }, $branches);

        if (empty($data)) {
            return $this->json(['message' => 'No branches found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($data);
    }

    #[Route('/branches/{id}', name: 'branch_show', methods: ['GET'])]
    public function show(BranchRepository $branchRepository, int $id): Response
    {
        $branch = $branchRepository->find($id);
        if (!$branch) {
            return $this->json(['message' => 'Branch not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $branch->getId(),
            'name' => $branch->getName(),
            'address' => $branch->getAddress(),
            'director' => $branch->getDirector() ? [
                'id' => $branch->getDirector()->getId(),
                'name' => $branch->getDirector()->getName(),
            ] : null,
        ];

        return $this->json($data);
    }

    #[Route('/branches', name: 'branch_create', methods: ['POST'])]
public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, SerializerInterface $serializer): Response
{
    $data = json_decode($request->getContent(), true);

    $branch = new Branch();
    $branch->setName($data['name']);
    $branch->setAddress($data['address']);

    $errors = $validator->validate($branch);
    if (count($errors) > 0) {
        return $this->json((string) $errors, Response::HTTP_BAD_REQUEST);
    }

    $entityManager->persist($branch);
    $entityManager->flush();

    $jsonContent = $serializer->serialize($branch, 'json', [
        'circular_reference_handler' => function ($object) {
            return $object->getId();
        }
    ]);

    return new Response($jsonContent, 201, [
        'Content-Type' => 'application/json'
    ]);
}

    #[Route('/branches/{id}', name: 'branch_update', methods: ['PUT'])]
    public function update(Request $request, BranchRepository $branchRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator, int $id): Response
    {
        $branch = $branchRepository->find($id);
        if (!$branch) {
            return $this->json(['message' => 'Branch not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $branch->setName($data['name'] ?? $branch->getName());
        $branch->setAddress($data['address'] ?? $branch->getAddress());

        if (isset($data['director_id'])) {
            $director = $entityManager->getReference(User::class, $data['director_id']);
            if ($director) {
                $director->setRole('director');
                $branch->setDirector($director);
            }
        }

        $errors = $validator->validate($branch);
        if (count($errors) > 0) {
            return $this->json((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        $data = [
            'id' => $branch->getId(),
            'name' => $branch->getName(),
            'address' => $branch->getAddress(),
            'director' => $branch->getDirector() ? [
                'id' => $branch->getDirector()->getId(),
                'name' => $branch->getDirector()->getName(),
            ] : null,
        ];

        return $this->json($data);
    }

    #[Route('/branches/{id}', name: 'branch_delete', methods: ['DELETE'])]
    public function delete(BranchRepository $branchRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        $branch = $branchRepository->find($id);
        if (!$branch) {
            return $this->json(['message' => 'Branch not found'], Response::HTTP_NOT_FOUND);
        }

        // Проверяем, есть ли у филиала связанные пользователи
        if (!$branch->getUsers()->isEmpty()) {
            return $this->json(['message' => 'Cannot delete branch because it has associated users'], Response::HTTP_BAD_REQUEST);
        }

        // Удаляем филиал
        $entityManager->remove($branch);
        $entityManager->flush();

        // Проверяем, был ли филиал удален
        if (!$branchRepository->find($id)) {
            return $this->json(['message' => 'Branch deleted'], Response::HTTP_OK);
        } else {
            return $this->json(['message' => 'Failed to delete branch'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
