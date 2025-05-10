<?php

namespace App\Controller;

use App\Repository\TicketOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
class AdminStatsController extends AbstractController
{
    #[Route('/stats/offers', name: 'admin_offer_stats', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getOfferSales(TicketOrderRepository $orderRepo): JsonResponse
    {
        $stats = $orderRepo->countOrdersGroupedByOffer();
        return new JsonResponse($stats);
    }
}
