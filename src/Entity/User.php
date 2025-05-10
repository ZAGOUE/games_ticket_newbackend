<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeImmutable;
use Random\RandomException;



#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\Column(length: 255)]
    private ?string $first_name;

    #[ORM\Column(length: 255)]
    private ?string $last_name;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email;

    #[Assert\NotBlank(message: "Le mot de passe ne peut pas être vide.")]
    #[Assert\Length(min: 8, minMessage: "Le mot de passe doit contenir au moins 8 caractères.")]
    #[Assert\Regex(pattern: "/[A-Z]/", message: "Le mot de passe doit contenir au moins une lettre majuscule.")]
    #[Assert\Regex(pattern: "/[a-z]/", message: "Le mot de passe doit contenir au moins une lettre minuscule.")]
    #[Assert\Regex(pattern: "/[0-9]/", message: "Le mot de passe doit contenir au moins un chiffre.")]
    #[Assert\Regex(pattern: "/[\W]/", message: "Le mot de passe doit contenir au moins un caractère spécial.")]
    #[ORM\Column(length: 255)]
    private ?string $password = null;


    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $security_key;

    #[ORM\Column(type: 'json', nullable: false)]
    private array $roles = ['ROLE_USER']; // Par défaut, tout utilisateur a ROLE_USER

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();
        try {
            $this->security_key = bin2hex(random_bytes(16)); // Génération de la clé
        } catch (RandomException $e) {
            throw new \RuntimeException('Erreur lors de la génération de la security_key', 0, $e);
        }
    }



public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getSecurityKey(): ?string
    {
        return $this->security_key;
    }

    public function setSecurityKey(string $security_key): static
    {
        $this->security_key = $security_key;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles ?? [];
        $roles[] = 'ROLE_USER'; // Assurer que chaque utilisateur a au moins ROLE_USER
        return array_unique($roles);
    }




    public function setRoles(array $roles): self
    {
        $this->roles = empty($roles) ? ['ROLE_USER'] : $roles;
        return $this;
    }



    public function getCreatedAt(): ?\DateTimeImmutable
    {
        if ($this->created_at instanceof \DateTimeImmutable) {
            return $this->created_at;
        }

        if ($this->created_at instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($this->created_at);
        }

        return null;
    }


    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
    public function getUserIdentifier(): string
    {
        return $this->email; // ou un autre champ unique comme username
    }

    public function eraseCredentials(): void
    {
        // Si tu stockes des informations sensibles en clair, nettoie-les ici
    }


}
