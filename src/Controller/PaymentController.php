<?php

namespace App\Controller;


use App\Entity\Payment;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;



class PaymentController extends AbstractController
{
    #[Route('/api/payments', methods: ['GET'])]
    public function getPayments(EntityManagerInterface $em): JsonResponse
    {
        $payments = $em->getRepository(Payment::class)->findAll();
        return $this->json($payments);
    }
}