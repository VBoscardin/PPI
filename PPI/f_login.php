<?php
session_start();

// Configurações do banco de dados
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "bd_ppi";

// Criar conexão
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Preparar e executar a consulta SQL
    $stmt = $conn->prepare("SELECT password_hash, tipo FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password, $user_type);
        $stmt->fetch();

        // Verificar a senha
        if (password_verify($password, $hashed_password)) {
            // Credenciais corretas, armazenar o tipo de usuário na sessão
            $_SESSION['email'] = $email;
            $_SESSION['user_type'] = $user_type;

            // Redirecionar para a página apropriada com base no tipo de usuário
            switch ($user_type) {
                case 'administrador':
                    header("Location: f_pagina_adm.php");
                    break;
                case 'docente':
                    header("Location: f_pagina_docente.php");
                    break;
                case 'setor':
                    header("Location: f_pagina_setor.php");
                    break;
                default:
                    $error = "Tipo de usuário desconhecido.";
                    break;
            }
            exit();
        } else {
            // Senha incorreta
            $error = "Credenciais inválidas. Por favor, tente novamente.";
        }
    } else {
        // E-mail não encontrado
        $error = "Credenciais inválidas. Por favor, tente novamente.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilo específico para o container do topo */
        .top-container {
            display: flex;
            align-items: center;
            gap: 20px; /* Espaço entre logo e texto */
            padding: 20px; /* Adiciona padding ao container do topo */
            background-color: #ffffff; /* Fundo branco */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra leve */
            border-radius: 8px; /* Cantos arredondados */
            margin-bottom: 40px; /* Espaço abaixo do container do topo */
        }

        .top-container .logo {
            max-width: 80px; /* Tamanho da logo */
            height: auto;
        }

        .top-container .text {
            text-align: left; /* Alinha o texto à esquerda */
            font-family: 'Forum';
        }

        .login-container {
            background-color: #003d00; /* Verde escuro */
            color: white;
            padding: 30px;
            border-radius: 8px; /* Cantos arredondados */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra leve */
        }

        .login-container h3 {
            color: white;
        }

        .login-container .form-control {
            border-radius: 4px; /* Cantos arredondados */
        }

        .login-container .btn-primary {
            background-color: #ffffff; /* Botão branco */
            color: #000000; /* Texto preto */
            border: none;
            border-radius: 4px; /* Cantos arredondados */
        }

        .login-container .btn-primary:hover {
            background-color: #000000; /* Fundo preto ao passar o mouse */
            color: #ffffff; /* Texto branco ao passar o mouse */
        }
    </style>
</head>
<body>
    <!-- Container do topo -->
    <header class="container my-4">
        <div class="top-container d-flex justify-content-start align-items-center">
            <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
            <div class="text">
                <h1 class="mb-0">SIGNE</h1>
                <h2 class="mb-0">Sistema Gerenciador de Notas Escolares</h2>
            </div>
        </div>
    </header>

    <!-- Container do formulário de login -->
    <main class="container d-flex justify-content-center align-items-center" style="min-height: 60vh;">
        <div class="login-container w-100" style="max-width: 400px;">
            <form method="POST" action="">
                <h3 class="text-center mb-4">Entrar no Sistema</h3>
                <div class="mb-3">
                    <label for="email" class="form-label">Usuário:</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Digite seu e-mail" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Senha:</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Digite sua senha" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
