<?php
session_start();

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Preparar e executar a consulta SQL
    $stmt = $conn->prepare("SELECT id, password_hash, tipo FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $user_type);
        $stmt->fetch();

        // Verificar a senha
        if (password_verify($password, $hashed_password)) {
            // Credenciais corretas, armazenar os dados do usuário na sessão
            $_SESSION['user_id'] = $user_id;  // Correção aqui para pegar o ID do usuário
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
        #toggle-password {
    cursor: pointer;
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
                <div class="mb-3 position-relative">
    <label for="password" class="form-label">Senha:</label>
    <div class="position-relative">
        <input type="password" id="password" name="password" class="form-control pe-5" placeholder="Digite sua senha" required>
        <span id="toggle-password" class="position-absolute" style="top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" class="bi bi-eye" viewBox="0 0 16 16">
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5 8-5.5 8-5.5zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.166 2.457A13.133 13.133 0 0 1 14.828 8c-.058.077-.122.167-.195.267-.02.025-.047.054-.07.081a13.133 13.133 0 0 1-1.66 2.043C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.166-2.457A13.133 13.133 0 0 1 1.173 8z"/>
                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM8 6.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/>
            </svg>
        </span>
    </div>
</div>



                <button type="submit" name="login" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
    const togglePassword = document.getElementById("toggle-password");
    const passwordField = document.getElementById("password");

    togglePassword.addEventListener("click", function () {
        const type = passwordField.type === "password" ? "text" : "password";
        passwordField.type = type;

        // Alterar o ícone com a cor preta
        this.innerHTML =
            type === "password"
                ? `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" class="bi bi-eye" viewBox="0 0 16 16">
                     <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5 8-5.5 8-5.5zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.166 2.457A13.133 13.133 0 0 1 14.828 8c-.058.077-.122.167-.195.267-.02.025-.047.054-.07.081a13.133 13.133 0 0 1-1.66 2.043C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.166-2.457A13.133 13.133 0 0 1 1.173 8z"/>
                     <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM8 6.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/>
                   </svg>`
                : `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" class="bi bi-eye-slash" viewBox="0 0 16 16">
                     <path d="M13.359 11.238 15 13l-1.5 1.5-1.9-1.9a9.028 9.028 0 0 1-3.6.9c-2.5 0-4.88-1.119-6.359-2.762C1.139 9.88 0 8.62 0 8s1.139-1.88 2.641-3.138l-.972-.972L3.5 2.5 5 4l.768.768C6.64 4.119 7.269 4 8 4c2.5 0 4.88 1.119 6.359 2.762C14.861 6.12 16 7.38 16 8c0 .429-.119.92-.359 1.238ZM1.641 5.381 3.074 6.813C2.478 7.202 2 7.579 2 8c0 .421.478.798 1.074 1.187C3.986 9.441 5.851 10.5 8 10.5c.323 0 .642-.017.959-.05L10 11.49c-.646.216-1.325.352-2 .41V13c-3.12-.113-5.401-2.167-6.359-3.762C1.001 8.601.5 8 .5 8s.5-.601 1.641-1.619Z"/>
                     <path d="M12.768 6.682 11.5 8 9 5.5 6.5 3 5 4.5l.5 2 1.5 1.5.5 1 1.5 1.5 1 1 .5.5 2-2 2 2-1.732-1.732Z"/>
                   </svg>`;
    });
});


</script>

</body>
</html>
