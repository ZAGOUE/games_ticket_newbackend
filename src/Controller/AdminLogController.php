<?php

namespace App\Controller;


use App\Entity\AdminLog;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


class AdminLogController extends AbstractController
{
    #[Route('/api/admin_logs', methods: ['GET'])]
    public function getAdminLogs(EntityManagerInterface $em): JsonResponse
    {
        $logs = $em->getRepository(AdminLog::class)->findAll();
        return $this->json($logs);
    }
}