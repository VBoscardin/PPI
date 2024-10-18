<?php
$senha = "1234"; // A senha que você deseja criptografar
$hash = password_hash($senha, PASSWORD_BCRYPT);

echo "Senha criptografada: " . $hash;
?>
