<?php
require 'vendor/autoload.php';

use App\Entity\User;

(new \Symfony\Component\Dotenv\Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$hasher = $container->get('security.user_password_hasher');

$username = 'khalfallahsameh7@gmail.com';
$password = 'password123';

$user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
if (!$user) {
    $user = new User();
    $user->setUsername($username);
    echo "Creating new user...\n";
} else {
    echo "Updating existing user password...\n";
}

$user->setPasswordHash($hasher->hashPassword($user, $password));
$user->setRoles(['ROLE_USER']);

$em->persist($user);
$em->flush();

echo "User '$username' has been saved with password '$password'.\n";
