<?php
session_start();

// Verificar se o usuário está autenticado e é um administrador
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Habilitar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$erro = ""; // Inicialize a variável de erro

// Processar a edição de uma disciplina
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_disciplina'])) {
    $id = intval($_POST['id']);
    $nome = $_POST['nome'] ?? ''; // Usar valor padrão se não estiver definido
    $turma_numero = intval($_POST['turma_numero']);
    $docente_ids = $_POST['docente_ids'] ?? []; // Captura o array de IDs dos docentes

    // Verificar se a turma existe
    $turma_check = $conn->prepare("SELECT numero FROM turmas WHERE numero = ?");
    $turma_check->bind_param("i", $turma_numero);
    $turma_check->execute();
    $turma_check->store_result();

    if ($turma_check->num_rows > 0) {
        echo "Turma existe.<br>"; // Log de verificação

        // Verificar se já existe uma disciplina com o mesmo nome na nova turma
        $disciplina_check = $conn->prepare("SELECT d.id FROM disciplinas d
            JOIN turmas_disciplinas td ON d.id = td.disciplina_id
            WHERE td.turma_numero = ? AND d.nome = ? AND d.id != ?");
        $disciplina_check->bind_param("ssi", $turma_numero, $nome, $id);
        $disciplina_check->execute();
        $disciplina_check->store_result();

        if ($disciplina_check->num_rows > 0) {
            $_SESSION['erro'] = "Já existe uma disciplina com o mesmo nome nesta turma.";
        } else {
            echo "Nenhuma disciplina existente nesta turma, prosseguindo com a atualização.<br>"; // Log de verificação

            // Atualizar a disciplina
            $stmt_update = $conn->prepare("UPDATE disciplinas SET nome = ? WHERE id = ?");
            $stmt_update->bind_param("si", $nome, $id);

            if ($stmt_update->execute()) {
                // Excluir a relação existente
                $stmt_delete = $conn->prepare("DELETE FROM turmas_disciplinas WHERE disciplina_id = ?");
                $stmt_delete->bind_param("i", $id);
                $stmt_delete->execute();
                $stmt_delete->close();

                // Inserir a nova relação com a turma
                $stmt_insert_turma = $conn->prepare("INSERT INTO turmas_disciplinas (disciplina_id, turma_numero) VALUES (?, ?)");
                $stmt_insert_turma->bind_param("ii", $id, $turma_numero);
                $stmt_insert_turma->execute();
                $stmt_insert_turma->close();

                // Atualizar docentes relacionados
                $stmt_delete_docentes = $conn->prepare("DELETE FROM docentes_disciplinas WHERE disciplina_id = ?");
                $stmt_delete_docentes->bind_param("i", $id);
                $stmt_delete_docentes->execute();
                $stmt_delete_docentes->close();

                // Inserir novos docentes
                foreach ($docente_ids as $docente_id) {
                    $stmt_insert_docente = $conn->prepare("INSERT INTO docentes_disciplinas (disciplina_id, docente_id) VALUES (?, ?)");
                    $stmt_insert_docente->bind_param("ii", $id, $docente_id);
                    $stmt_insert_docente->execute();
                    $stmt_insert_docente->close();
                }

                $_SESSION['mensagem'] = "Disciplina atualizada com sucesso.";
            } else {
                $_SESSION['erro'] = "Erro ao atualizar disciplina: " . $stmt_update->error;
            }
            $stmt_update->close();
        }

        $disciplina_check->close();
    } else {
        $_SESSION['erro'] = "Turma não encontrada.";
    }
    $turma_check->close();
}

// Processar a exclusão de uma disciplina
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_disciplina'])) {
    $id = intval($_POST['id']);
    
    // Verificar se a disciplina existe antes de tentar excluir
    $stmt_check = $conn->prepare("SELECT id FROM disciplinas WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Excluir da tabela turmas_disciplinas
        $stmt_delete_turma = $conn->prepare("DELETE FROM turmas_disciplinas WHERE disciplina_id = ?");
        $stmt_delete_turma->bind_param("i", $id);
        $stmt_delete_turma->execute();
        $stmt_delete_turma->close();

        // Excluir da tabela docentes_disciplinas
        $stmt_delete_docente = $conn->prepare("DELETE FROM docentes_disciplinas WHERE disciplina_id = ?");
        $stmt_delete_docente->bind_param("i", $id);
        $stmt_delete_docente->execute();
        $stmt_delete_docente->close();

        // Se a disciplina existir, proceder para a exclusão
        $stmt_delete = $conn->prepare("DELETE FROM disciplinas WHERE id = ?");
        $stmt_delete->bind_param("i", $id);
        if ($stmt_delete->execute()) {
            $_SESSION['mensagem'] = "Disciplina excluída com sucesso.";
        } else {
            $_SESSION['erro'] = "Erro ao excluir disciplina: " . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $_SESSION['erro'] = "Disciplina não encontrada.";
    }
    $stmt_check->close();
}

// Recuperar disciplinas com suas turmas e docentes
$result = $conn->query("
    SELECT d.id AS disciplina_id, d.nome AS disciplina_nome, td.turma_numero, 
           GROUP_CONCAT(doc.id ORDER BY doc.nome SEPARATOR ', ') AS docentes_ids,
           GROUP_CONCAT(doc.nome ORDER BY doc.nome SEPARATOR ', ') AS docentes_nomes
    FROM turmas_disciplinas td
    JOIN disciplinas d ON td.disciplina_id = d.id
    LEFT JOIN docentes_disciplinas dd ON d.id = dd.disciplina_id
    LEFT JOIN docentes doc ON dd.docente_id = doc.id
    GROUP BY d.id, td.turma_numero
    ORDER BY d.nome
");


// Verifique se a consulta foi bem-sucedida
if ($result === false) {
    die("Erro na consulta: " . $conn->error);
} else {
    echo "Consulta executada com sucesso!<br>"; // Adicione isso
    if ($result->num_rows == 0) {
        echo "Nenhuma disciplina encontrada.<br>"; // Adicione isso para verificar se não há resultados
    }
}


// Preencher o array $disciplinas_array com os dados da consulta
$disciplinas_array = [];
while ($row = $result->fetch_assoc()) {
    $row['docente_ids'] = !empty($row['docentes_ids']) ? explode(',', $row['docentes_ids']) : [];
    $disciplinas_array[] = $row;
    echo "Disciplina adicionada: " . htmlspecialchars($row['disciplina_nome']) . "<br>"; // Adicione isso
}


// Liberar o resultado após usar todos os dados
$result->free();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Disciplinas</title>
</head>
<body>
    <h1>Listar Disciplinas</h1>

    <!-- Exibir mensagens de erro ou sucesso -->
    <?php if (!empty($_SESSION['erro'])): ?>
    <div style="color: red;">
        <?php echo htmlspecialchars($_SESSION['erro']); ?>
        <?php unset($_SESSION['erro']); ?>
    </div>
<?php endif; ?>
<?php if (!empty($_SESSION['mensagem'])): ?>
    <div style="color: green;">
        <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
        <?php unset($_SESSION['mensagem']); ?>
    </div>
<?php endif; ?>


    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome da Disciplina</th>
                <th>Turma</th>
                <th>Docentes</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($disciplinas_array as $disciplina): ?>
                <tr>
                    <td><?php echo htmlspecialchars($disciplina['disciplina_id']); ?></td>
                    <td><?php echo htmlspecialchars($disciplina['disciplina_nome']); ?></td>
                    <td><?php echo htmlspecialchars($disciplina['turma_numero']); ?></td>
                    <td><?php echo htmlspecialchars($disciplina['docentes_nomes']); ?></td>
                    <td>
                        <form method="POST" action="editar_disciplina.php">
                            <input type="hidden" name="id" value="<?php echo $disciplina['disciplina_id']; ?>">
                            <input type="submit" name="editar" value="Editar">
                        </form>
                        <form method="POST" action="">
                            <input type="hidden" name="id" value="<?php echo $disciplina['disciplina_id']; ?>">
                            <input type="submit" name="delete_disciplina" value="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta disciplina?');">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Criar Nova Disciplina</h2>
    <form method="POST" action="criar_disciplina.php">
        <label for="nome">Nome da Disciplina:</label>
        <input type="text" id="nome" name="nome" required>
        <label for="turma_numero">Número da Turma:</label>
        <input type="number" id="turma_numero" name="turma_numero" required>
        <label for="docente_ids">Docentes:</label>
        <select id="docente_ids" name="docente_ids[]" multiple>
            <!-- Aqui você deve popular o select com os docentes disponíveis -->
            <?php
            // Consulta para obter docentes
            $docentes_result = $conn->query("SELECT id, nome FROM docentes");
            while ($docente = $docentes_result->fetch_assoc()): ?>
                <option value="<?php echo $docente['id']; ?>"><?php echo htmlspecialchars($docente['nome']); ?></option>
            <?php endwhile; ?>
        </select>
        <input type="submit" value="Criar Disciplina">
    </form>

    <a href="logout.php">Sair</a>
</body>
</html>

<?php
// Fechar a conexão
$conn->close();
?>
