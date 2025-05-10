<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Offer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création des utilisateurs
        $users = [
            ["firstName" => "John", "lastName" => "Doe", "email" => "user@example.com", "roles" => ["ROLE_USER"], "password" => "User@1234"],
            ["firstName" => "Alice", "lastName" => "Admin", "email" => "admin@example.com", "roles" => ["ROLE_ADMIN"], "password" => "Admin@1234"],
            ["firstName" => "Charlie", "lastName" => "Controller", "email" => "controller@example.com", "roles" => ["ROLE_CONTROLLER"], "password" => "Control@1234"],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setFirstName($userData["firstName"]);
            $user->setLastName($userData["lastName"]);
            $user->setEmail($userData["email"]);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData["password"]);
            $user->setPassword($hashedPassword);
            $user->setRoles($userData["roles"]);
            $manager->persist($user);
            echo "Utilisateur créé : {$userData['firstName']} {$userData['lastName']} ({$userData['email']}) avec mot de passe : {$userData['password']}\n";
        }

        // Création de deux offres de billets
        $offers = [
            ["name" => "Billet Bronze", "description" => "Accès aux épreuves générales", "price" => 50, "max_people" => 500],
            ["name" => "Billet Or", "description" => "Accès VIP avec places réservées", "price" => 200, "max_people" => 100],
        ];

        foreach ($offers as $offerData) {
            $offer = new Offer();
            $offer->setName($offerData["name"]);
            $offer->setDescription($offerData["description"]);
            $offer->setPrice($offerData["price"]);
            $offer->setMaxPeople($offerData["max_people"]);
            $manager->persist($offer);
            echo "Offre créée : {$offerData['name']} ({$offerData['price']}€) avec une capacité de {$offerData['max_people']} personnes\n";
        }

        // Enregistrement en base
        $manager->flush();
    }
}
