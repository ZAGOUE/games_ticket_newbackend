<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;

class EntityRelationshipChecker
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function checkRelationships(OutputInterface $output)
    {
        $io = new SymfonyStyle($output);

        $entities = [
            'App\\Entity\\User',
            'App\\Entity\\Offer',
            'App\\Entity\\TicketOrder',
            'App\\Entity\\Payment',
            'App\\Entity\\AdminLog'
        ];

        foreach ($entities as $entityClass) {
            $metaData = $this->entityManager->getClassMetadata($entityClass);
            $io->section("Vérification des relations pour l'entité " . $entityClass);

            foreach ($metaData->getAssociationMappings() as $fieldName => $mapping) {
                $targetEntity = $mapping['targetEntity'];
                $relationType = $mapping['type'];
                $field = $mapping['fieldName'];

                $io->text("Relation trouvée : $entityClass::$field (" . $this->getRelationType($relationType) . ") -> $targetEntity");
            }

            $io->success("Vérification terminée pour $entityClass");
        }
    }

    private function getRelationType(int $relationType): string
    {
        switch ($relationType) {
            case 1:
                return 'OneToOne';
            case 2:
                return 'ManyToOne';
            case 4:
                return 'OneToMany';
            case 8:
                return 'ManyToMany';
            default:
                return 'Relation inconnue';
        }
    }
}
