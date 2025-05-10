<?php

namespace App\Controller;

use App\Entity\TicketOrder;
use App\Repository\OfferRepository;
use App\Repository\TicketOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\Response;


use TCPDF;





#[Route('/api/orders')]
class TicketOrderController extends AbstractController
{
    #[Route('', name: 'create_order', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createOrder(
        Request $request,
        OfferRepository $offerRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['offer_id'], $data['quantity']) || $data['quantity'] <= 0) {
            return new JsonResponse(['error' => 'Les champs offer_id et quantity sont requis et doivent être valides'], 400);
        }

        $offer = $offerRepository->find($data['offer_id']);
        if (!$offer) {
            return new JsonResponse(['error' => 'Offre non trouvée'], 404);
        }

        $order = new TicketOrder();
        $order->setUser($this->getUser());
        $order->setOffer($offer);
        $order->setQuantity($data['quantity']);
        $order->setOrderKey(bin2hex(random_bytes(16)));
        $this->generateQrCode($order->getOrderKey());


        $entityManager->persist($order);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Commande créée avec succès',
            'order_key' => $order->getOrderKey()
        ], 201);
    }

    #[Route('', name: 'get_orders', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getOrders(TicketOrderRepository $orderRepository): JsonResponse
    {
        $orders = $orderRepository->findBy(['user' => $this->getUser()]);
        return $this->json($orders);
    }

    #[Route('/{id<\d+>}', name: 'get_order', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getOrder(TicketOrderRepository $orderRepository, int $id, Security $security): JsonResponse
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        // L'admin peut voir toutes les commandes, l'utilisateur ne peut voir que les siennes
        if ($security->isGranted('ROLE_ADMIN') || $order->getUser() === $this->getUser()) {
            return $this->json([
                'id' => $order->getId(),
                'user' => $order->getUser()->getEmail(),
                'offer' => $order->getOffer()->getName(),
                'status' => $order->getStatus(),
                'validated_at' => $order->getValidatedAt()?->format('Y-m-d H:i:s'),
            ]);
        }

        return new JsonResponse(['error' => 'Accès refusé'], 403);
    }

    #[Route('/all', name: 'get_all_orders', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllOrders(TicketOrderRepository $orderRepository): JsonResponse
    {
        $orders = $orderRepository->findAll();

        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'id' => $order->getId(),
                'user' => $order->getUser()->getEmail(),
                'offer' => $order->getOffer()->getName(),
                'status' => $order->getStatus(),
                'validated_at' => $order->getValidatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data);
    }


    #[Route('/{id}/pay', name: 'pay_order', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function payOrder(TicketOrderRepository $orderRepository, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $order = $orderRepository->findOneBy(['id' => $id, 'user' => $this->getUser()]);

        if (!$order) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        $order->setStatus('PAID');
        $order->setOrderKey(bin2hex(random_bytes(16))); // Génération d'une clé unique 32 caractères hexadécimaux


        $entityManager->flush();

        return new JsonResponse(['message' => 'Paiement effectué avec succès', 'order_key' => $order->getOrderKey()

        ]);
    }



    #[Route('/{id}/qrcode', name: 'get_qr_code', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getQrCode(TicketOrderRepository $orderRepository, int $id): Response
    {
        $order = $orderRepository->findOneBy(['id' => $id, 'user' => $this->getUser()]);

        if (!$order) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        if ($order->getStatus() !== 'PAID') {
            return new JsonResponse(['error' => 'Commande non payée'], 400);
        }

        // Générer le QR Code avec la méthode centralisée
        $qrCodePath = $this->generateQrCode($order->getOrderKey());

        // Retourner le QR Code en réponse HTTP
        return new Response(file_get_contents($qrCodePath), Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }



    #[Route('/{id}/download', name: 'download_ticket', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function downloadTicket(TicketOrderRepository $orderRepository, int $id, Security $security): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            return new Response('Commande non trouvée', 404);
        }

        if (!$security->isGranted('ROLE_ADMIN') && $order->getUser() !== $this->getUser()) {
            return new Response('Accès refusé', 403);
        }

        // Création du PDF avec TCPDF
        $pdf = new TCPDF();
        $pdf->SetCreator('Games Ticket');
        $pdf->SetTitle('E-Billet');
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        // Dessiner les anneaux olympiques centrés sur la page
        function drawOlympicLogo($pdf)
        {
            $colors = [
                [0, 133, 199],   // Bleu
                [255, 179, 0],   // Jaune
                [0, 0, 0],       // Noir
                [0, 159, 61],    // Vert
                [239, 51, 64],   // Rouge
            ];

            $pageWidth = 210; // Largeur d'une page A4 en mm
            $centerX = $pageWidth / 2; // Milieu de la page

            $positions = [
                [$centerX - 35, 30],  // Bleu
                [$centerX - 10, 30],  // Jaune
                [$centerX + 15, 30],  // Noir
                [$centerX - 22, 45],  // Vert
                [$centerX + 8, 45],   // Rouge
            ];

            $pdf->SetLineWidth(3);

            for ($i = 0; $i < 5; $i++) {
                list($r, $g, $b) = $colors[$i];
                list($x, $y) = $positions[$i];

                $pdf->SetDrawColor($r, $g, $b);
                $pdf->Ellipse($x, $y, 15, 10);
            }
        }



        // Appeler la fonction pour dessiner le logo
        drawOlympicLogo($pdf);


        // Titre du billet (mise en page)
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Ln(50); // Avant : 25 → Maintenant : 45
        $pdf->Cell(0, 10, 'E-Billet - ' . $order->getOffer()->getName(), 0, 1, 'C');

        // Informations sur le billet (mise en page)
        $pdf->SetFont('helvetica', '', 14);
        $pdf->Ln(10); // Avant : 5 → Maintenant : 10
        $pdf->Cell(0, 10, 'Titulaire : ' . $order->getUser()->getEmail(), 0, 1, 'C');
        $pdf->Cell(0, 10, 'Statut : ' . $order->getStatus(), 0, 1, 'C');
        $pdf->Cell(0, 10, 'Validé le : ' . ($order->getValidatedAt() ? $order->getValidatedAt()->format('Y-m-d H:i:s') : 'Non validé'), 0, 1, 'C');


        // Dessiner un cadre autour des informations
        $pdf->Rect(15, 75, 180, 40, 'D'); // Avant : Y=45 → Maintenant : Y=70

        // Générer et ajouter le QR Code
        $qrCodePath = $this->generateQrCode($order->getOrderKey());
        $pdf->Image($qrCodePath, 75, 130, 60, 60, 'PNG'); // Avant : Y=90 → Maintenant : Y=130

        // Sortie du PDF
        $pdfContent = $pdf->Output('', 'S');
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="E-Billet_' . $order->getId() . '.pdf"',
        ]);
    }



    #[Route('/verify-ticket/{order_key}', name: 'verify_ticket', methods: ['GET'])]
    #[IsGranted('ROLE_CONTROLLER')]
    public function verifyTicket(
        TicketOrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        string $order_key
    ): JsonResponse {
        $order = $orderRepository->findOneBy(['orderKey' => $order_key]);

        if (!$order) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Billet introuvable',
                'code' => 'ticket_not_found'
            ], 404);
        }

        // Vérifications

        if ($order->getStatus() === 'USED') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Ce billet a déjà été utilisé le ' . $order->getValidatedAt()->format('d/m/Y H:i'),
                'code' => 'ticket_already_used',
                'validated_at' => $order->getValidatedAt()->format('Y-m-d H:i:s')
            ], 400);
        }

        if ($order->getStatus() !== 'PAID') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Billet non payé. Statut actuel : ' . $order->getStatus(),
                'code' => 'ticket_not_paid'
            ], 400);
        }

        // 👇 Empêcher la réutilisation si déjà validé (mais statut pas à USED)
        if ($order->getValidatedAt() !== null) {
            $order->setStatus('USED');
            $entityManager->flush();

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Billet déjà validé (mais marqué comme USED maintenant)',
                'code' => 'ticket_already_validated'
            ], 400);
        }

        // 👇 Tout est OK : validation finale
        $order->setStatus('USED');
        $order->setValidatedAt(new \DateTime());
        $entityManager->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Billet validé avec succès',
            'order_id' => $order->getId(),
            'user' => [
                'email' => $order->getUser()->getEmail(),
                'first_name' => $order->getUser()->getFirstName(),
                'last_name' => $order->getUser()->getLastName(),
            ],

            'offer' => $order->getOffer()->getName(),
            'validated_at' => $order->getValidatedAt()->format('Y-m-d H:i:s'),
            'qr_code' => '/api/orders/' . $order->getId() . '/qrcode'
        ]);
    }


    private function generateQrCode(string $orderKey): string
    {
        $qrCode = new QrCode($orderKey);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Sauvegarde temporaire du QR Code
        $qrCodePath = sys_get_temp_dir() . '/qrcode_' . md5($orderKey) . '.png';
        file_put_contents($qrCodePath, $result->getString());

        return $qrCodePath;
    }


}
