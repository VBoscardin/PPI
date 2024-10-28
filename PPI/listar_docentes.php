<?php
session_start();

// Verificar se o usuário está autenticado e é um administrador
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Obter o nome e a foto do perfil do administrador logado
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Mensagens
$mensagem = '';

// Consulta SQL para selecionar todos os docentes, suas disciplinas e turmas
$sql = "
    SELECT d.id, d.nome, d.email, d.cpf, 
           disc.nome AS disciplina_nome,
           t.numero AS turma_numero,
           t.ano AS turma_ano
    FROM docentes d
    LEFT JOIN docentes_disciplinas dd ON d.id = dd.docente_id
    LEFT JOIN disciplinas disc ON dd.disciplina_id = disc.id
    LEFT JOIN turmas_disciplinas td ON td.disciplina_id = disc.id
    LEFT JOIN turmas t ON td.turma_numero = t.numero AND td.turma_ano = t.ano
    GROUP BY d.id, disc.id, t.numero, t.ano
    ORDER BY d.id, disc.nome
";

$result = $conn->query($sql);

// Verifica se a consulta foi bem-sucedida
if (!$result) {
    die("Erro na consulta: " . $conn->error);
}

// Verifica se há resultados
if ($result->num_rows > 0) {
    // Armazenar os dados dos docentes em um array
    $docentes = [];
    while ($row = $result->fetch_assoc()) {
        $docentes[] = $row;
    }
} else {
    $mensagem = "Nenhum docente encontrado.";
}

// Fecha a conexão
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Docentes</title>
</head>
<body>
    <h1>Lista de Docentes</h1>

    <?php if (!empty($mensagem)): ?>
        <p><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <?php if (!empty($docentes)): ?>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>CPF</th>
                <th>Disciplinas (Turma - Ano)</th>
                <th>Ações</th>
            </tr>

            <?php
            // Variável para armazenar o ID do último docente exibido
            $ultimoDocenteId = null;

            foreach ($docentes as $docente): 
                // Se o ID do docente mudar, exibir os dados do docente
                if ($ultimoDocenteId !== $docente['id']): 
                    $ultimoDocenteId = $docente['id'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($docente['id']); ?></td>
                    <td><?php echo htmlspecialchars($docente['nome']); ?></td>
                    <td><?php echo htmlspecialchars($docente['email']); ?></td>
                    <td><?php echo htmlspecialchars($docente['cpf']); ?></td>
                    <td>
                        <?php
                        // Exibir a disciplina com o número da turma e o ano
                        if ($docente['disciplina_nome']) {
                            echo htmlspecialchars($docente['disciplina_nome']) . ' (' . htmlspecialchars($docente['turma_numero'] ?? 'N/A') . ' - ' . htmlspecialchars($docente['turma_ano'] ?? 'N/A') . ')';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="editar_docente.php?id=<?php echo htmlspecialchars($docente['id']); ?>">Editar</a> |
                        <a href="deletar_docente.php?id=<?php echo htmlspecialchars($docente['id']); ?>">Deletar</a>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="4"></td>
                    <td>
                        <?php
                        // Exibir as disciplinas e as turmas correspondentes
                        echo htmlspecialchars($docente['disciplina_nome']) . ' - Turma: ' . htmlspecialchars($docente['turma_numero'] ?? 'N/A') . ' - Ano: ' . htmlspecialchars($docente['turma_ano'] ?? 'N/A');
                        ?>
                    </td>
                    <td></td>
                </tr>
            <?php endif; endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nenhum docente encontrado.</p>
    <?php endif; ?>
</body>
</html>
