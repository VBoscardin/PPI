

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Inicial</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            margin: 0;
            min-height: 100vh; /* Definir altura mínima da janela de visualização */
        }
        #sidebar {
            width: 200px;
            background-color: #f0f0f0;
            padding: 20px;
        }
        #content {
            flex-grow: 1;
            padding: 20px;
        }
        ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        li {
            margin-bottom: 10px;
        }
        a {
            text-decoration: none;
            color: #333;
        }
        a:hover {
            color: #555;
        }
        #sidebar {
            height: 100%; /* Estender até o final da janela de visualização */
            position: fixed; /* Fixar a barra lateral */
        }
    </style>
</head>
<body>
    <div id="sidebar">
        <ul>
            <li><a href="#">Início</a></li>
            <li><a href="#">Turmas</a></li>
            <li><a href="#">Disciplinas</a></li>
            <li><a href="#">Cadastrar Notas</a></li>
            <li><a href="#">Meu Perfil</a></li>
            <li><a href="logout.php">Sair</a></li>
        </ul>
    </div>
    <div id="content">
        <h1>Bem-vindo, <?php echo $username; ?>!</h1>
        <p>Esta é a página inicial.</p>
    </div>
</body>
</html>