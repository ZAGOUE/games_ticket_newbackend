<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/offers', name: 'api_offers_')]
class OfferController extends AbstractController
{
    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createOffer(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['description'], $data['price'], $data['max_people'])) {
            return new JsonResponse(['error' => 'Les champs name, description, price et max_people sont requis'], 400);
        }

        if (!is_numeric($data['price']) || $data['price'] < 0) {
            return new JsonResponse(['error' => 'Le prix doit être un nombre positif'], 400);
        }

        if (!is_int($data['max_people']) || $data['max_people'] <= 0) {
            return new JsonResponse(['error' => 'max_people doit être un entier positif'], 400);
        }

        $offer = new Offer();
        $offer->setName($data['name']);
        $offer->setDescription($data['description']);
        $offer->setPrice($data['price']);
        $offer->setMaxPeople($data['max_people']);

        $entityManager->persist($offer);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Offre créée avec succès'], 201);
    }


    #[Route('', methods: ['GET'])]
    public function getAllOffers(OfferRepository $offerRepository): JsonResponse
    {
        $offers = $offerRepository->findAll();
        return $this->json($offers);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getOfferById(OfferRepository $offerRepository, int $id): JsonResponse
    {
        $offer = $offerRepository->find($id);

        if (!$offer) {
            return new JsonResponse(['error' => 'Offre non trouvée'], 404);
        }

        return $this->json($offer);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateOffer(Request $request, OfferRepository $offerRepository, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $offer = $offerRepository->find($id);

        if (!$offer) {
            return new JsonResponse(['error' => 'Offre non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $offer->setName($data['name']);
        }
        if (isset($data['description'])) {
            $offer->setDescription($data['description']);
        }
        if (isset($data['price'])) {
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                return new JsonResponse(['error' => 'Le prix doit être un nombre positif'], 400);
            }
            $offer->setPrice($data['price']);
        }
        if (isset($data['max_people'])) {
            $offer->setMaxPeople($data['max_people']);
        }


        $entityManager->flush();

        return new JsonResponse(['message' => 'Offre mise à jour', 'offer' => $offer]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteOffer(OfferRepository $offerRepository, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $offer = $offerRepository->find($id);

        if (!$offer) {
            return new JsonResponse(['error' => 'Offre non trouvée'], 404);
        }

        $entityManager->remove($offer);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Offre supprimée'], 204);
    }
}
