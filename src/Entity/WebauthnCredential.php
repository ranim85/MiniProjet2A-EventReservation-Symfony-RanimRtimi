<?php

namespace App\Entity;

use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;

#[ORM\Entity(repositoryClass: WebauthnCredentialRepository::class)]
#[ORM\Table(name: 'webauthn_credential')]
class WebauthnCredential
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'webauthnCredentials')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Stocke le JSON sérialisé de PublicKeyCredentialSource
     */
    #[ORM\Column(type: 'text')]
    private string $credentialData;

    /**
     * Nom lisible par l'utilisateur (ex: "iPhone Touch ID")
     */
    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $lastUsedAt;

    public function __construct()
    {
        $this->id        = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    // ── Getters / Setters ──────────────────────────────────────────────

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Désérialise le JSON en objet PublicKeyCredentialSource
     */
    public function getCredentialSource(): PublicKeyCredentialSource
    {
        $data = json_decode($this->credentialData, true);
        return PublicKeyCredentialSource::createFromArray($data);
    }

    /**
     * Sérialise l'objet PublicKeyCredentialSource en JSON
     */
    public function setCredentialSource(PublicKeyCredentialSource $source): void
    {
        $this->credentialData = json_encode($source);
    }

    public function getCredentialData(): string
    {
        return $this->credentialData;
    }

    public function setCredentialData(string $credentialData): static
    {
        $this->credentialData = $credentialData;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): \DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * Met à jour la date de dernière utilisation
     */
    public function touch(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }
}