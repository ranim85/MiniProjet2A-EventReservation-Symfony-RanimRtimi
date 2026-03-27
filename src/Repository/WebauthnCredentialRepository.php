<?php
namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    /**
     * Retrouve une passkey par son credentialId (base64)
     */
    public function findByCredentialId(string $credentialId): ?WebauthnCredential
    {
        return $this->findOneBy(['credentialId' => $credentialId]);
    }

    /**
     * Sauvegarde une nouvelle passkey liée à un utilisateur
     */
    public function save(WebauthnCredential $credential): void
    {
        $this->getEntityManager()->persist($credential);
        $this->getEntityManager()->flush();
    }
}