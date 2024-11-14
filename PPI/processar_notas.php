<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'docente') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verificar se a disciplina foi enviada pelo formulário
if (!isset($_POST['disciplina_id'])) {
    echo "Erro: disciplina não selecionada.";
    exit();
}

$disciplina_id = intval($_POST['disciplina_id']);

// Query para obter turma_numero e turma_ano com base na disciplina selecionada
$turmaQuery = $conn->prepare("
    SELECT turma_numero, turma_ano
    FROM turmas_disciplinas
    WHERE disciplina_id = ?
    LIMIT 1
");
$turmaQuery->bind_param("i", $disciplina_id);
$turmaQuery->execute();
$turmaQuery->bind_result($turma_numero, $turma_ano);
$turmaQuery->fetch();
$turmaQuery->close();

// Verificar se os dados das notas foram enviados
if (isset($_POST['notas']) && isset($_POST['data_avaliacao']) && isset($_POST['tipo_avaliacao'])) {
    foreach ($_POST['notas'] as $matricula => $nota) {
        $data_avaliacao = $_POST['data_avaliacao'][$matricula];
        $tipo_avaliacao = $_POST['tipo_avaliacao'][$matricula];
        $matricula = intval($matricula);
        $nota = floatval($nota);

        // Validar a nota
        if ($nota < 0 || $nota > 10) {
            echo "Erro: A nota deve estar entre 0 e 10 para o aluno com matrícula $matricula.";
            continue;
        }

        // Inserir a nota no banco de dados
        $stmt = $conn->prepare("
            INSERT INTO notas (discente_id, disciplina_id, turma_numero, turma_ano, nota, data_avaliacao, tipo_avaliacao)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        // Usando os valores obtidos para turma_numero e turma_ano
        $stmt->bind_param("iiiidss", $matricula, $disciplina_id, $turma_numero, $turma_ano, $nota, $data_avaliacao, $tipo_avaliacao);

        if ($stmt->execute()) {
            echo "Nota cadastrada para o aluno de matrícula $matricula.<br>";
        } else {
            echo "Erro ao cadastrar a nota para o aluno de matrícula $matricula: " . $stmt->error . "<br>";
        }

        $stmt->close();
    }
} else {
    echo "Erro: Dados de notas incompletos.";
}

$conn->close();
?>
