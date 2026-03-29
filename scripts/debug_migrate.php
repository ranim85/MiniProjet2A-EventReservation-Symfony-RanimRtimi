<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('test', true);
$kernel->boot();
$cn = $kernel->getContainer()->get('doctrine')->getConnection();
$cn->createSchemaManager()->dropDatabase('event_reservation_test');
$cn->createSchemaManager()->createDatabase('event_reservation_test');
echo "Reset OK.\n";
$cn->executeStatement("CREATE TABLE `admin` (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, PRIMARY KEY(id))");
$tables = $cn->createSchemaManager()->listTableNames();
print_r($tables);
