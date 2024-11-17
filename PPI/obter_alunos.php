<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Verificar se o usuário é um docente
if ($_SESSION['user_type'] !== 'docente') {
    echo json_encode(['success' => false, 'message' => 'Usuário não é um docente']);
    exit();
}

// Obter o docente_id da sessão
$docente_id = $_SESSION['user_id'] ?? null;

// Verificar se o docente_id existe
if (!$docente_id) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado como docente']);
    exit();
}

include 'config.php';



$disciplina_id = (int)$_GET['disciplina_id'];

// Debug: Verificar disciplina_id recebido
error_log("Disciplina ID recebido: " . $disciplina_id); // Log para verificação

// Consultar os alunos dessa disciplina
$sql = "
    SELECT 
        a.id AS aluno_id, 
        a.nome AS aluno_nome
    FROM 
        alunos a
    JOIN matriculas m ON a.id = m.aluno_id
    JOIN disciplinas d ON m.disciplina_id = d.id
    WHERE d.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $disciplina_id);
$stmt->execute();
$result = $stmt->get_result();

// Verificar se existem alunos para a disciplina
if ($result->num_rows > 0) {
    $alunos = [];
    while ($row = $result->fetch_assoc()) {
        $alunos[] = [
            'id' => $row['aluno_id'],
            'nome' => $row['aluno_nome']
        ];
    }
    echo json_encode(['success' => true, 'alunos' => $alunos]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhum aluno encontrado para esta disciplina']);
}

$stmt->close();
?>
