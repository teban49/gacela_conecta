<?php

try {
    $pdo = new PDO('mysql:host=localhost;port=3306;charset=utf8mb4', 'root', 'Gacelator*', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Conexión a MySQL exitosa\n";
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gacela_conecta_beta");
    echo "Base de datos gacela_conecta_beta verificada\n";
    
    $pdo->exec("USE gacela_conecta_beta");
    echo "Usando base de datos gacela_conecta_beta\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}