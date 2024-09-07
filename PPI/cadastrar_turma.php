<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    // Redirecionar para a página de login se o usuário não estiver autenticado
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um administrador
if ($_SESSION['user_type'] !== 'administrador') {
    // Redirecionar para uma página de acesso negado ou qualquer outra página
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Obter lista de docentes
$docentes_result = $conn->query('SELECT id, nome FROM docentes');
$docentes = [];
if ($docentes_result) {
    while ($row = $docentes_result->fetch_assoc()) {
        $docentes[] = $row;
    }
}

// Obter lista de disciplinas
$disciplinas_result = $conn->query('SELECT id, nome FROM disciplinas');
$disciplinas = [];
if ($disciplinas_result) {
    while ($row = $disciplinas_result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}

// Função para cadastrar turma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_turma'])) {
    $numero = $_POST['numero'];
    $ano = $_POST['ano'];
    $ano_ingresso = $_POST['ano_ingresso'];
    $ano_oferta = $_POST['ano_oferta'];
    $professor_regente = $_POST['professor_regente'];
    $disciplinas_selecionadas = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];

    // Verificar se os campos não estão vazios
    if (!empty($numero) && !empty($ano) && !empty($ano_ingresso) && !empty($ano_oferta) && !empty($professor_regente) && !empty($disciplinas_selecionadas)) {
        // Iniciar transação
        $conn->begin_transaction();

        try {
            // Inserir a turma na tabela turmas
            $stmt = $conn->prepare('INSERT INTO turmas (numero, ano, ano_ingresso, ano_oferta, professor_regente) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iiiii', $numero, $ano, $ano_ingresso, $ano_oferta, $professor_regente);
            $stmt->execute();
            $stmt->close();

            // Inserir as disciplinas selecionadas na tabela turmas_disciplinas
            $stmt = $conn->prepare('INSERT INTO turmas_disciplinas (turma_numero, turma_ano, turma_ano_ingresso, disciplina_id) VALUES (?, ?, ?, ?)');
            foreach ($disciplinas_selecionadas as $disciplina_id) {
                $stmt->bind_param('iiii', $numero, $ano, $ano_ingresso, $disciplina_id);
                $stmt->execute();
            }
            $stmt->close();

            // Confirmar transação
            $conn->commit();

            echo 'Turma cadastrada com sucesso!';
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $conn->rollback();
            echo 'Erro ao cadastrar turma: ' . $e->getMessage();
        }
    } else {
        echo 'Todos os campos são obrigatórios!';
    }
}

// Fechar conexão
$conn->close();
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

        <label for="disciplinas">Disciplinas:</label><br>
        <?php foreach ($disciplinas as $disciplina): ?>
            <input type="checkbox" id="disciplina_<?php echo htmlspecialchars($disciplina['id']); ?>" 
                   name="disciplinas[]" value="<?php echo htmlspecialchars($disciplina['id']); ?>">
            <label for="disciplina_<?php echo htmlspecialchars($disciplina['id']); ?>">
                <?php echo htmlspecialchars($disciplina['nome']); ?>
            </label><br>
        <?php endforeach; ?>
        
        <input type="submit" name="cadastrar_turma" value="Cadastrar Turma">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
