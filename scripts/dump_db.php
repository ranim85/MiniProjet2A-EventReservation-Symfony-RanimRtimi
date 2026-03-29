<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('test', true);
$kernel->boot();
$cn = $kernel->getContainer()->get('doctrine')->getConnection();
echo "Connected DB: " . $cn->getDatabase() . "\n";
$tables = $cn->createSchemaManager()->listTableNames();
echo "Tables: \n";
print_r($tables);
