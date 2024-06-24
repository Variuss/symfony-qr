<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class UsersController extends AbstractController
{

    #[OA\Response(response: 200, description: 'OK')]
    #[Route('/panel_users', name: 'api_panel_users_index', methods: 'get')]
    public function index(UsersRepository $usersRepository): JsonResponse
    {
        $users = $usersRepository->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'activity' => $user->getActivity(),
                'lang' => $user->getLang(),
                'valid_till' => $user->getValidTill(),
                'register_date' => $user->getRegisterDate()
            ];
        }

        return $this->json($data);
    }

    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: [
        'email' => 'test@testowy.pl',
        'name' => 'tester',
        'password' => 'test123',
        'valid_till' => '2024-07-01 15:00:00',
        'lang' => 'pl',
        'activity' => 1
    ]))]
    #[OA\Response(response: 200, description: 'New User has been added')]
    #[OA\Response(response: 400, description: 'Request data is invalid')]
    #[Route('/panel_users', name: 'api_panel_users_create', methods: 'post')]
    public function create(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UsersRepository $usersRepository
    ): JsonResponse
    {
        $content = json_decode($request->getContent());

        try {
            $user = new Users();
            $plaintextPassword = $content->password;

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plaintextPassword
            );

            $user->setPassword($hashedPassword);
            $user->setEmail($content->email);
            $user->setName($content->name);
            $user->setActivity($content->activity);
            $user->setLang($content->lang);
            $user->setValidTill(new \DateTime($content->valid_till));
            $user->setRoles(['ROLE_USER']);
            $usersRepository->save($user);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Request data is invalid.'], 400);
        }

        return $this->json(['message' => 'New User has been added successfully with id ' . $user->getId()]);
    }

    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: [
        'email' => 'test@testowy.pl',
        'name' => 'tester',
        'valid_till' => '2024-07-01 15:00:00',
        'lang' => 'pl',
        'activity' => 1
    ]))]
    #[OA\RequestBody(required: true, content: [] )]
    #[OA\Response(response: 200, description: 'User has been edited')]
    #[OA\Response(response: 400, description: 'Request data is invalid')]
    #[OA\Response(response: 404, description: 'No User found')]
    #[Route('/panel_users/{id}', name: 'api_panel_users_edit', methods: 'put')]
    public function edit(
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository,
        Request $request,
        int $id
    ): JsonResponse
    {
        $user = $usersRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'No User found for id' . $id], 404);
        }

        $content = json_decode($request->getContent());
        try {
            $user->setEmail($content->email);
            $user->setName($content->name);
            $user->setActivity($content->activity);
            $user->setLang($content->lang);
            $user->setValidTill(new \DateTime($content->valid_till));
            $usersRepository->save($user);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['message' => 'Request data is invalid.'], 400);
        }

        return $this->json(['message' => 'User with id: ' . $user->getId() . ' has been edited successfully.']);
    }

    #[OA\Response(response: 200, description: 'Deleted a User')]
    #[OA\Response(response: 404, description: 'No User found')]
    #[Route('/panel_users/{id}', name: 'api_panel_users_delete', methods: 'delete')]
    public function delete(UsersRepository $usersRepository, int $id): JsonResponse
    {
        $user = $usersRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'No User found for id ' . $id], 404);
        }

        $usersRepository->delete($user);

        return $this->json(['message' => 'Deleted a User successfully with id ' . $id]);
    }
}
