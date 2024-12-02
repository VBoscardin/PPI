<?php

session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário tem permissão para acessar esta página (exemplo: administrador)
if ($_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar a conexão com o banco de dados
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil, email, tipo FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil, $email, $tipo);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Administrador</title>
    <link rel="stylesheet" href="style.css"> <!-- Se tiver um arquivo CSS -->
</head>
<body>
    <header>
        <h1>Meu Perfil - Administrador</h1>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <section>
        <h2>Informações do Usuário</h2>
        <table>
            <tr>
                <th>Nome</th>
                <td><?php echo htmlspecialchars($nome); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo htmlspecialchars($email); ?></td>
            </tr>
            <tr>
                <th>Tipo</th>
                <td><?php echo ucfirst(htmlspecialchars($tipo)); ?></td>
            </tr>
            <tr>
                <th>Foto de Perfil</th>
                <td>
                    <?php if ($foto_perfil) : ?>
                        <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de perfil" width="100">
                    <?php else : ?>
                        <p>Sem foto de perfil.</p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>
    
</body>
</html>

<?php
// Fechar a conexão com o banco de dados
$conn->close();
?>
