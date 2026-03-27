<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepository;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Implémente PublicKeyCredentialUserEntityRepository pour WebAuthn
 */
class UserRepository extends ServiceEntityRepository
    implements PasswordUpgraderInterface, PublicKeyCredentialUserEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPasswordHash($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Retrouve un utilisateur par son handle WebAuthn (requis par WebAuthn)
     */
    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        $user = $this->findAll();
        // Le userHandle est l'ID utilisateur en binaire
        foreach ($user as $u) {
            if (hash_equals(base64_encode($u->getUsername()), base64_encode($userHandle))
                || $u->getUsername() === $userHandle) {
                return new PublicKeyCredentialUserEntity(
                    $u->getUsername(),
                    $u->getUsername(),
                    $u->getUsername()
                );
            }
        }
        return null;
    }

    /**
     * Retrouve un utilisateur par son username WebAuthn (requis par WebAuthn)
     */
    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        $user = $this->findOneBy(['username' => $username]);
        if (!$user) return null;

        return new PublicKeyCredentialUserEntity(
            $user->getUsername(),
            $user->getUsername(),
            $user->getUsername()
        );
    }

    /**
     * Méthode utilitaire pour retrouver l'entité User depuis un UserEntity WebAuthn
     */
    public function findUserByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }
}