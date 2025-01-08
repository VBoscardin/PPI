<?php
// Conectar ao banco de dados
include 'config.php';

// Verificar a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Consulta para buscar os setores
$sql = "SELECT id, nome, local, email FROM setores";
$result = $conn->query($sql);

// Organizar os dados em um array
$setores = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $setores[] = $row; // Armazena os setores no array
    }
}

// Fechar a conexão
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Listar Setores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 sidebar">
                <!-- Menu da barra lateral -->
                <div class="separator mb-3"></div>
                <div class="signe-text">SIGNE</div>
                <div class="separator mt-3 mb-3"></div>
                <button onclick="location.href='f_pagina_adm.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#expandable-menu" aria-expanded="false" aria-controls="expandable-menu">
                    <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar
                </button>
                <div id="expandable-menu" class="collapse expandable-container">
                    <div class="expandable-menu">
                        <button onclick="location.href='cadastrar_adm.php'">
                            <i class="fas fa-plus"></i> Cadastrar Administrador
                        </button>
                        <button onclick="location.href='cadastrar_setor.php'">
                            <i class="fas fa-plus"></i> Cadastrar Setor
                        </button>
                    </div>
                </div>
                <button onclick="location.href='meu_perfil.php'">
                    <i class="fas fa-user"></i> Meu Perfil
                </button>
                <button class="btn btn-danger" onclick="location.href='sair.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>

            <div class="col-md-9 main-content">
                <div class="container">
                    <div class="header-container">
                        <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                        <div class="title ms-3">Listar Setores</div>
                    </div>
                </div>
                
                <div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-hover table-sm align-middle">
                    <thead class="table-dark">
                            <tr>
                                <th>Nome</th>
                                <th>Local</th>
                                <th>Email</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Verificar se há setores
                            if (count($setores) > 0) {
                                foreach ($setores as $setor) {
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($setor['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($setor['local']); ?></td>
                                        <td><?php echo htmlspecialchars($setor['email']); ?></td>
                                        <td class="text-center">
                                                    <div class="d-flex gap-2 justify-content-center ">                            
                                            <!-- Botões Editar e Excluir -->
                                            <button class="btn btn-warning btn-sm custom-btn" onclick="editSetor(<?php echo $setor['id']; ?>)">
                                                <i class="fas fa-edit me-2"></i> Editar
                                            </button>
                                            <button class="btn btn-danger btn-sm custom-btn" onclick="deleteSetor(<?php echo $setor['id']; ?>)">
                                                <i class="fas fa-trash-alt me-2"></i> Excluir
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Linha de detalhes -->
                                    <tr id="detalhes-<?php echo $setor['id']; ?>" class="detalhes-linha" style="display:none;">
                                        <td colspan="4">
                                            <div class="card mt-2">
                                                <div class="card-body">
                                                    <h5 class="card-title">Informações Detalhadas</h5>
                                                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($setor['nome']); ?></p>
                                                    <p><strong>Local:</strong> <?php echo htmlspecialchars($setor['local']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($setor['email']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='4'>Nenhum setor encontrado.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

            </div>
        </div>
    </div>

    <script>
        function showDetails(id) {
            var detailsRow = document.getElementById("detalhes-" + id);
            if (detailsRow.style.display === "none") {
                detailsRow.style.display = "block";
            } else {
                detailsRow.style.display = "none";
            }
        }
    </script>
</body>
</html>
