<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasskeyAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface     $jwtManager,
        private RefreshTokenManagerInterface $refreshManager,
        private EntityManagerInterface       $em,
        private UserRepository               $userRepo,
    ) {}

    // ── Page HTML d'inscription ────────────────────────────────────────

    #[Route('/register', name: 'passkey_register_page', methods: ['GET'])]
    public function registerPage(): Response
    {
        return $this->render('security/passkey_register.html.twig');
    }

    // ── Page HTML de connexion ─────────────────────────────────────────

    #[Route('/passkey-login', name: 'passkey_login_page', methods: ['GET'])]
    public function loginPage(): Response
    {
        return $this->render('security/passkey_login.html.twig');
    }

    // 
    // API : ENREGISTREMENT PASSKEY
    // 

    /**
     * POST /api/auth/register/options
     * Retourne le challenge et les options pour créer une passkey.
     */
    #[Route('/register/options', name: 'api_passkey_register_options', methods: ['POST'])]
    public function registerOptions(
        Request $request,
        PasskeyAuthService $passkeyService,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true);
        $username = trim($data['username'] ?? '');

        if (!$username) {
            return $this->json(['error' => 'Le nom d\'utilisateur est requis.'], 400);
        }

        // Crée l'utilisateur s'il n'existe pas
        $user = $this->userRepo->findOneBy(['username' => $username]);
        if (!$user) {
            $user = new User();
            $user->setUsername($username);
            $user->setPasswordHash($hasher->hashPassword($user, bin2hex(random_bytes(16))));
            $this->em->persist($user);
            $this->em->flush();
        }

        try {
            return $this->json($passkeyService->getRegistrationOptions($user));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /api/auth/register/verify
     * Vérifie la passkey créée par le navigateur et retourne un JWT.
     */
    #[Route('/register/verify', name: 'api_passkey_register_verify', methods: ['POST'])]
    public function registerVerify(
        Request $request,
        PasskeyAuthService $passkeyService
    ): JsonResponse {
        $data           = json_decode($request->getContent(), true);
        $username       = trim($data['username'] ?? '');
        $credentialData = $data['credential']     ?? null;
        $credentialName = $data['credentialName'] ?? 'Ma passkey';

        if (!$username || !$credentialData) {
            return $this->json(['error' => 'Données manquantes (username, credential).'], 400);
        }

        $user = $this->userRepo->findOneBy(['username' => $username]);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable.'], 404);
        }

        try {
            $passkeyService->verifyRegistration($credentialData, $user, $credentialName);

            return $this->json([
                'success'       => true,
                'message'       => 'Passkey enregistrée ! Bienvenue ' . $user->getUsername(),
                'token'         => $this->jwtManager->create($user),
                'refresh_token' => $this->refreshManager->createForUser($user)->getRefreshToken(),
                'user'          => ['id' => $user->getId(), 'username' => $user->getUsername()],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    // 
    // API : CONNEXION PASSKEY
    // 

    /**
     * POST /api/auth/login/options
     * Retourne le challenge de connexion.
     */
    #[Route('/login/options', name: 'api_passkey_login_options', methods: ['POST'])]
    public function loginOptions(
        Request $request,
        PasskeyAuthService $passkeyService
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true);
        $username = trim($data['username'] ?? '');

        try {
            return $this->json($passkeyService->getLoginOptions($username ?: null));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /api/auth/login/verify
     * Vérifie la signature de la passkey et retourne un JWT.
     */
    #[Route('/login/verify', name: 'api_passkey_login_verify', methods: ['POST'])]
    public function loginVerify(
        Request $request,
        PasskeyAuthService $passkeyService
    ): JsonResponse {
        $data           = json_decode($request->getContent(), true);
        $assertionData  = $data['credential'] ?? null;

        if (!$assertionData) {
            return $this->json(['error' => 'Credential manquant.'], 400);
        }

        try {
            $user = $passkeyService->verifyLogin($assertionData);

            return $this->json([
                'success'       => true,
                'message'       => 'Connexion réussie ! Bonjour ' . $user->getUsername(),
                'token'         => $this->jwtManager->create($user),
                'refresh_token' => $this->refreshManager->createForUser($user)->getRefreshToken(),
                'user'          => ['id' => $user->getId(), 'username' => $user->getUsername()],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 401);
        }
    }

    // ── Profil utilisateur ─────────────────────────────────────────────

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié.'], 401);
        }
        return $this->json(['id' => $user->getId(), 'username' => $user->getUsername(), 'roles' => $user->getRoles()]);
    }
}