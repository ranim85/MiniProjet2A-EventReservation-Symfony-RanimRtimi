<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/api/auth/register', name: 'api_register', methods: ['POST'])]
    public function apiRegister(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['username'], $data['password'])) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setPasswordHash($hasher->hashPassword($user, $data['password']));
        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Utilisateur créé',
            'token' => $jwtManager->create($user),
        ], 201);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $confirm  = $request->request->get('confirm_password');

            if (!$username || !$password) {
                $this->addFlash('error', 'Veuillez remplir tous les champs');
                return $this->render('security/register.html.twig');
            }

            if ($password !== $confirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas');
                return $this->render('security/register.html.twig');
            }

            $existing = $em->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existing) {
                $this->addFlash('error', 'Ce nom d\'utilisateur est déjà pris');
                return $this->render('security/register.html.twig');
            }

            $user = new User();
            $user->setUsername($username);
            $user->setPasswordHash($hasher->hashPassword($user, $password));
            $user->setRoles(['ROLE_USER']);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Félicitations ! Votre compte a été créé. Connectez-vous maintenant.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig');
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
    }
}