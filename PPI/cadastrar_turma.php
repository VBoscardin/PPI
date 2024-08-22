<?php
$host = 'localhost';
$db = 'bd_ppi';
$user = 'root'; // Seu usuário do banco de dados
$pass = ''; // Sua senha do banco de dados

// Conectar ao banco de dados
$mysqli = new mysqli($host, $user, $pass, $db);

// Verificar conexão
if ($mysqli->connect_error) {
    die('Conexão falhou: ' . $mysqli->connect_error);
}

// Função para cadastrar turma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_turma'])) {
    $numero = $_POST['numero'];
    $ano = $_POST['ano'];
    $ano_ingresso = $_POST['ano_ingresso'];
    $ano_oferta = $_POST['ano_oferta'];
    $professor_regente = $_POST['professor_regente'];

    // Verificar se os campos não estão vazios
    if (!empty($numero) && !empty($ano) && !empty($ano_ingresso) && !empty($ano_oferta) && !empty($professor_regente)) {
        // Preparar a consulta para evitar SQL Injection
        $stmt = $mysqli->prepare('INSERT INTO turmas (numero, ano, ano_ingresso, ano_oferta, professor_regente) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iiiii', $numero, $ano, $ano_ingresso, $ano_oferta, $professor_regente);

        if ($stmt->execute()) {
            echo 'Turma cadastrada com sucesso!';
        } else {
            echo 'Erro ao cadastrar turma: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        echo 'Todos os campos são obrigatórios!';
    }
}

// Obter lista de docentes para o menu suspenso
$docentes_result = $mysqli->query('SELECT id, nome FROM docentes');
$docentes = [];
if ($docentes_result) {
    while ($row = $docentes_result->fetch_assoc()) {
        $docentes[] = $row;
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Turma</title>
</head>
<body>
    <h1>Cadastrar Turma</h1>

    <form action="cadastrar_turma.php" method="post">
        <label for="numero">Número da Turma:</label>
        <input type="number" id="numero" name="numero" required>
        
        <label for="ano">Ano:</label>
        <input type="number" id="ano" name="ano" min="2000" max="2099" step="1" required>
        
        <label for="ano_ingresso">Ano de Ingresso:</label>
        <input type="number" id="ano_ingresso" name="ano_ingresso" min="2000" max="2099" step="1" required>
        
        <label for="ano_oferta">Ano de Oferta:</label>
        <input type="number" id="ano_oferta" name="ano_oferta" min="2000" max="2099" step="1" required>
        
        <label for="professor_regente">Professor Regente:</label>
        <select id="professor_regente" name="professor_regente" required>
            <option value="">Selecione um professor</option>
            <?php foreach ($docentes as $docente): ?>
                <option value="<?php echo htmlspecialchars($docente['id']); ?>">
                    <?php echo htmlspecialchars($docente['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <input type="submit" name="cadastrar_turma" value="Cadastrar Turma">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
