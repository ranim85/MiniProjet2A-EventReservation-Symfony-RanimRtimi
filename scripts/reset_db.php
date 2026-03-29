<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
$pdo->exec('DROP DATABASE IF EXISTS event_reservation_test');
$pdo->exec('CREATE DATABASE event_reservation_test');
echo "Database reset successfully.\n";
