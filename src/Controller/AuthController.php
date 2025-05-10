<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $user = $this->getUser();

        // Crée un token avec les données supplémentaires
        $token = $jwtManager->createFromPayload($user, [
            'username' => $user->getEmail(),
            'first_name' => $user->getFirstName(), // Ajouté
            'last_name' => $user->getLastName(),   // Ajouté
            'roles' => $user->getRoles()
        ]);

        return $this->json(['token' => $token]);
    }
}