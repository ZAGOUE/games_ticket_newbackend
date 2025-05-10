<?php

namespace App\Entity;

use App\Repository\TicketOrderRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: TicketOrderRepository::class)]
class TicketOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Offer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Offer $offer;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $quantity;

    #[ORM\Column(type: 'string', length: 255)]
    private string $status = 'PENDING'; // Par défaut, commande en attente

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $created_at;

    #[ORM\Column(name: 'order_key', type: 'string', length: 255, unique: true)]
    private string $orderKey;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $qrcode;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $validatedAt = null;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();

        $this->created_at = new \DateTimeImmutable();
        $this->qrcode = bin2hex(random_bytes(16)); // Génère une valeur aléatoire
    }

    public function getValidatedAt(): ?\DateTime
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTime $validatedAt): self
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getOrderKey(): string
    {
        return $this->orderKey;
    }

    public function setOrderKey(string $orderKey): self
    {
        $this->orderKey = $orderKey;
        return $this;
    }

    public function getQrcode(): string
    {
        return $this->qrcode;
    }

    public function setQrcode(string $qrcode): void
    {
        $this->qrcode = $qrcode;
    }
}
