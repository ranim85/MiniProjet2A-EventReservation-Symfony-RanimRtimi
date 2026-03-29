<?php
require 'vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$users = $em->getRepository(App\Entity\User::class)->findAll();

echo "Found " . count($users) . " users.\n";
foreach ($users as $u) {
    echo "Username: " . $u->getUsername() . " | Hash: " . $u->getPasswordHash() . "\n";
}
