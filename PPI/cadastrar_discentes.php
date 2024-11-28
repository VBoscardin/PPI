<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um setor
if ($_SESSION['user_type'] !== 'setor') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$success_message = ''; // Variável para a mensagem de sucesso
$error_message = ''; // Variável para mensagem de erro
$setor_nome = ''; // Inicializar a variável $setor_nome
$setor_foto = ''; // Inicializar a variável $setor_foto

// Consultar o nome do setor e a foto de perfil
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($setor_nome, $setor_foto);
$stmt->fetch();
$stmt->close();

// Função para cadastrar discente
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coletar os dados do formulário
    $numero_matricula = isset($_POST['numero_matricula']) ? $_POST['numero_matricula'] : '';
    $nome = isset($_POST['nome']) ? $_POST['nome'] : '';
    $cidade = isset($_POST['cidade']) ? $_POST['cidade'] : '';
    $uf = isset($_POST['uf']) ? $_POST['uf'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $genero = isset($_POST['genero']) ? $_POST['genero'] : '';
    $data_nascimento = isset($_POST['data_nascimento']) ? $_POST['data_nascimento'] : '';
    $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
    $reprovacoes = isset($_POST['reprovacoes']) ? $_POST['reprovacoes'] : 0;
    $acompanhamento = isset($_POST['acompanhamento']) ? $_POST['acompanhamento'] : '';
    $apoio_psicologico = isset($_POST['apoio_psicologico']) ? $_POST['apoio_psicologico'] : '';
    $auxilio_permanencia = isset($_POST['auxilio_permanencia']) ? $_POST['auxilio_permanencia'] : '';
    $cotista = isset($_POST['cotista']) ? $_POST['cotista'] : '';
    $estagio = isset($_POST['estagio']) ? $_POST['estagio'] : '';
    $acompanhamento_saude = isset($_POST['acompanhamento_saude']) ? $_POST['acompanhamento_saude'] : '';
    $projeto_pesquisa = isset($_POST['projeto_pesquisa']) ? $_POST['projeto_pesquisa'] : '';
    $projeto_extensao = isset($_POST['projeto_extensao']) ? $_POST['projeto_extensao'] : '';
    $projeto_ensino = isset($_POST['projeto_ensino']) ? $_POST['projeto_ensino'] : '';
    $turma = isset($_POST['turma_numero']) ? $_POST['turma_numero'] : ''; // Captura a turma selecionada

    // Inicializar o caminho da foto
    $foto_path = null;

    // Verificar se o campo foto está preenchido e processar o upload
    if (isset($_FILES['foto']) && !empty($_FILES['foto']['name'])) {
        $foto = $_FILES['foto']['name'];
        $foto_temp = $_FILES['foto']['tmp_name'];
        $foto_path = 'uploads/' . basename($foto);

        // Mover o arquivo para o diretório de uploads
        if (!move_uploaded_file($foto_temp, $foto_path)) {
            $_SESSION['mensagem_erro'] = 'Erro ao fazer upload da foto.';
        }
    }

    // Verificar se os campos obrigatórios estão preenchidos
    if (empty($nome) || empty($cidade) || empty($uf) || empty($email)  || empty($genero) || empty($data_nascimento) || empty($acompanhamento) || empty($apoio_psicologico) || empty($auxilio_permanencia) || empty($cotista) || empty($estagio) || empty($acompanhamento_saude) || empty($projeto_pesquisa) || empty($projeto_extensao) || empty($projeto_ensino) || empty($turma)) {
        $_SESSION['mensagem_erro'] = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        // Iniciar uma transação
        $conn->begin_transaction();
        
        try {
            // Inserir na tabela 'discentes'
            $stmt = $conn->prepare('INSERT INTO discentes (numero_matricula, nome, cidade, uf, email, genero, data_nascimento, observacoes, reprovacoes, acompanhamento, apoio_psicologico, auxilio_permanencia, cotista, estagio, acompanhamento_saude, projeto_pesquisa, projeto_extensao, projeto_ensino, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issssssssssssssssss', $numero_matricula, $nome, $cidade, $uf, $email, $genero, $data_nascimento, $observacoes, $reprovacoes, $acompanhamento, $apoio_psicologico, $auxilio_permanencia, $cotista, $estagio, $acompanhamento_saude, $projeto_pesquisa, $projeto_extensao, $projeto_ensino, $foto_path);

            if (!$stmt->execute()) {
                throw new Exception('Erro ao cadastrar discente: ' . $stmt->error);
            }

            // Obter o ID do discente recém-inserido
            $discente_id = $conn->insert_id;

            // Separar os dados da turma
            list($turma_numero, $turma_ano, $turma_ano_ingresso) = explode(',', $turma);

            // Inserir na tabela 'discentes_turmas'
            $stmt = $conn->prepare('INSERT INTO discentes_turmas (numero_matricula, turma_numero, turma_ano, turma_ano_ingresso) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiii', $discente_id, $turma_numero, $turma_ano, $turma_ano_ingresso);

            if (!$stmt->execute()) {
                throw new Exception('Erro ao atribuir turma ao discente: ' . $stmt->error);
            }

            // Confirmar a transação
            $conn->commit();
            $_SESSION['mensagem_sucesso'] = 'Discente cadastrado com sucesso!';
        } catch (Exception $e) {
            // Reverter a transação em caso de erro
            $conn->rollback();
            $_SESSION['mensagem_erro'] = 'Erro: ' . $e->getMessage();
        }

        // Fechar a declaração
        
    }

    // Redirecionar para a mesma página para exibir mensagens
    header('Location: cadastrar_discentes.php');
    exit();
}

// Fechar a conexão
$conn->close();
?>





<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Discente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Barra lateral -->
            <div class="col-md-3 sidebar">
                <div class="separator mb-3"></div>
                <div class="signe-text">SIGNE</div>
                <div class="separator mt-3 mb-3"></div>
                <button onclick="location.href='f_pagina_setor.php'" class="btn btn-primary btn-block mb-2">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn btn-light btn-block mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#expandable-menu" aria-expanded="false" aria-controls="expandable-menu">
                    <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar
                </button>
                <div id="expandable-menu" class="collapse expandable-container">
                    <div class="expandable-menu">
                        <button onclick="location.href='cadastrar_discentes.php'" class="btn btn-light btn-block">
                            <i class="fas fa-plus"></i> Cadastrar Discente
                        </button>
                    </div>
                </div>
                <button onclick="location.href='meu_perfil.php'" class="btn btn-light btn-block mb-2">
                    <i class="fas fa-user"></i> Meu Perfil
                </button>
                <button class="btn btn-danger btn-block" onclick="location.href='sair.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-9 main-content">
                <div class="container">
                    <div class="header-container d-flex align-items-center mb-4">
                        <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                        <div class="title ms-3">Cadastrar Discente</div>
                        <div class="ms-auto d-flex align-items-center">
                        <div class="profile-info d-flex align-items-center">
                            <div class="profile-details me-2">
                                <span><?php echo htmlspecialchars($setor_nome); ?></span>
                            </div>
                            <?php if (!empty($setor_foto) && file_exists('uploads/' . basename($setor_foto))): ?>
                                <img src="uploads/<?php echo htmlspecialchars(basename($setor_foto)); ?>" alt="Foto do Setor">
                            <?php else: ?>
                                <img src="imgs/setor-photo.png" alt="Foto do Setor">
                            <?php endif; ?>
                        </div>

                        </div>
                    </div>
                </div>

                <div class="container mt-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <form action="cadastrar_discentes.php" method="post" enctype="multipart/form-data">
                                <!-- Campos existentes -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="numero_matricula" class="form-label">Número da Matrícula:</label>
                                        <input type="text" id="numero_matricula" name="numero_matricula" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="nome" class="form-label">Nome:</label>
                                        <input type="text" id="nome" name="nome" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="cidade" class="form-label">Cidade:</label>
                                        <input type="text" id="cidade" name="cidade" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="uf" class="form-label">UF:</label>
                                        <input type="text" id="uf" name="uf" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">E-mail:</label>
                                        <input type="email" id="email" name="email" class="form-control" required>
                                    </div>
                                    
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="genero" class="form-label">Gênero:</label>
                                        <select id="genero" name="genero" class="form-select" required>
                                            <option value="Masculino">Masculino</option>
                                            <option value="Feminino">Feminino</option>
                                            <option value="Outro">Outro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="data_nascimento" class="form-label">Data de Nascimento:</label>
                                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="observacoes" class="form-label">Observações:</label>
                                        <textarea id="observacoes" name="observacoes" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="turma" class="form-label">Turma:</label>
                                        <select id="turma" name="turma_numero" class="form-select">
                                        <?php
                                        // Conectar ao banco de dados e buscar as turmas e seus respectivos cursos
                                        include 'config.php';
                                        $result = $conn->query("SELECT t.numero, t.ano, t.ano_ingresso, c.nome AS curso_nome 
                                                                FROM turmas t
                                                                JOIN cursos c ON t.curso_id = c.id");

                                        while ($row = $result->fetch_assoc()) {
                                            // Exibir a turma com o número e o nome do curso
                                            echo "<option value=\"{$row['numero']},{$row['ano']},{$row['ano_ingresso']}\">Turma {$row['numero']} ({$row['curso_nome']})</option>";
                                        }

                                        $conn->close(); // Fechar a conexão após o uso
                                        ?>
                                        </select>
                                    </div>               
                                </div>


                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="reprovacoes" class="form-label">Reprovações:</label>
                                        <input type="number" id="reprovacoes" name="reprovacoes" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="foto" class="form-label">Foto:</label>
                                        <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                                    </div>
                                </div>

                                <!-- Campos adicionais como checkboxes agrupados em colunas -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Acompanhamento:</label>
                                            <div class="form-check">
                                                <input type="radio" id="acompanhamento-sim" name="acompanhamento" value="Sim" class="form-check-input">
                                                <label for="acompanhamento-sim" class="form-check-label">Sim</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="acompanhamento-nao" name="acompanhamento" value="Não" class="form-check-input">
                                                <label for="acompanhamento-nao" class="form-check-label">Não</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Apoio Psicológico:</label>
                                            <div class="form-check">
                                                <input type="radio" id="apoio_psicologico-sim" name="apoio_psicologico" value="Sim" class="form-check-input">
                                                <label for="apoio_psicologico-sim" class="form-check-label">Sim</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="apoio_psicologico-nao" name="apoio_psicologico" value="Não" class="form-check-input">
                                                <label for="apoio_psicologico-nao" class="form-check-label">Não</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Auxílio Permanência:</label>
                                            <div class="form-check">
                                                <input type="radio" id="auxilio_permanencia-sim" name="auxilio_permanencia" value="Sim" class="form-check-input">
                                                <label for="auxilio_permanencia-sim" class="form-check-label">Sim</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="auxilio_permanencia-nao" name="auxilio_permanencia" value="Não" class="form-check-input">
                                                <label for="auxilio_permanencia-nao" class="form-check-label">Não</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Cotista:</label>
                                            <div class="form-check">
                                                <input type="radio" id="cotista-sim" name="cotista" value="Sim" class="form-check-input">
                                                <label for="cotista-sim" class="form-check-label">Sim</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="cotista-nao" name="cotista" value="Não" class="form-check-input">
                                                <label for="cotista-nao" class="form-check-label">Não</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Estágio:</label>
                                            <div class="form-check">
                                                <input type="radio" id="estagio-sim" name="estagio" value="Sim" class="form-check-input">
                                                <label for="estagio-sim" class="form-check-label">Sim</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="estagio-nao" name="estagio" value="Não" class="form-check-input">
                                                <label for="estagio-nao" class="form-check-label">Não</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Acompanhamento de Saúde:</label>
                                            <div class="form-check">
                                                <input type="radio" id="acompanhamento_saude-sim" name="acompanhamento_saude" value="Sim" class="form-check-input">
                                                <label for="acompanhamento_saude-sim" class="form-check-label">Sim</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="acompanhamento_saude-nao" name="acompanhamento_saude" value="Não" class="form-check-input">
                                                <label for="acompanhamento_saude-nao" class="form-check-label">Não</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Projeto de Pesquisa:</label>
                                            <div class="form-check">
                                                <input type="radio" id="projeto_pesquisa-sim" name="projeto_pesquisa" value="Sim" class="form-check-input">
                                                <label for="projeto_pesquisa-sim" class="form-check-label">Sim</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="projeto_pesquisa-nao" name="projeto_pesquisa" value="Não" class="form-check-input">
                                                <label for="projeto_pesquisa-nao" class="form-check-label">Não</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Projeto de Extensão:</label>
                                            <div class="form-check">
                                                <input type="radio" id="projeto_extensao-sim" name="projeto_extensao" value="Sim" class="form-check-input">
                                                <label for="projeto_extensao-sim" class="form-check-label">Sim</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="projeto_extensao-nao" name="projeto_extensao" value="Não" class="form-check-input">
                                                <label for="projeto_extensao-nao" class="form-check-label">Não</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Projeto de Ensino:</label>
                                            <div class="form-check">
                                                <input type="radio" id="projeto_ensino-sim" name="projeto_ensino" value="Sim" class="form-check-input">
                                                <label for="projeto_ensino-sim" class="form-check-label">Sim</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="projeto_ensino-nao" name="projeto_ensino" value="Não" class="form-check-input">
                                                <label for="projeto_ensino-nao" class="form-check-label">Não</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <button type="submit" class="btn btn-light">Cadastrar Discentes</button>

                                <!-- Exibir mensagem de sucesso ou erro abaixo do botão -->
                                <div class="mt-3">
                                    <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                                        <div id="mensagem-sucesso" class="alert alert-success">
                                            <?php echo $_SESSION['mensagem_sucesso']; ?>
                                        </div>
                                        <?php unset($_SESSION['mensagem_sucesso']); ?>
                                    <?php elseif (isset($_SESSION['mensagem_erro'])): ?>
                                        <div id="mensagem-erro" class="alert alert-danger">
                                            <?php echo $_SESSION['mensagem_erro']; ?>
                                        </div>
                                        <?php unset($_SESSION['mensagem_erro']); ?>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div> 
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Remover a mensagem de sucesso ou erro após 5 segundos
        setTimeout(function() {
            var mensagemSucesso = document.getElementById('mensagem-sucesso');
            var mensagemErro = document.getElementById('mensagem-erro');
            if (mensagemSucesso) {
                mensagemSucesso.style.display = 'none';
            }
            if (mensagemErro) {
                mensagemErro.style.display = 'none';
            }
        }, 10000); // 5 segundos
    </script>
</body>
</html>
