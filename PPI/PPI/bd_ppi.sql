CREATE DATABASE bd_ppi;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `tipo` ENUM('administrador', 'docente', 'setor') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`id`, `username`, `email`, `password_hash`, `reset_token`, `reset_expires`, `tipo`)
VALUES
(0, 'teste', 'teste@gmail.com', '$2y$10$QnEOOy0.g050eTuhFx2McOGuYxPLGJ0p31W8YPOiIIUsqjpoJvwG.', NULL, NULL, 'administrador');
INSERT INTO `usuarios` (`id`, `username`, `email`, `password_hash`, `reset_token`, `reset_expires`, `tipo`)
VALUES
(0, 'Vicente Boscardin', 'vicenteboscardin@gmail.com', '$2y$10$QnEOOy0.g050eTuhFx2McOGuYxPLGJ0p31W8YPOiIIUsqjpoJvwG.', NULL, NULL, 'administrador');

CREATE TABLE `cursos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `coordenador` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `disciplinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `setores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `local` varchar(100) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `senha` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `cpf` varchar(14) NOT NULL UNIQUE,
  `senha` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `docentes_disciplinas` (
  `docente_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  FOREIGN KEY (`docente_id`) REFERENCES `docentes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas`(`id`) ON DELETE CASCADE,
  PRIMARY KEY (`docente_id`, `disciplina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `turmas` (
  `numero` INT NOT NULL,
  `ano` YEAR NOT NULL,
  `ano_ingresso` YEAR NOT NULL,
  `ano_oferta` YEAR NOT NULL,
  `professor_regente` INT NOT NULL,
  PRIMARY KEY (`numero`, `ano`, `ano_ingresso`),
  FOREIGN KEY (`professor_regente`) REFERENCES `docentes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `turmas_disciplinas` (
  `turma_numero` INT NOT NULL,
  `turma_ano` YEAR NOT NULL,
  `turma_ano_ingresso` YEAR NOT NULL,
  `disciplina_id` INT NOT NULL,
  FOREIGN KEY (`turma_numero`, `turma_ano`, `turma_ano_ingresso`) REFERENCES `turmas` (`numero`, `ano`, `ano_ingresso`),
  FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE setores
ADD COLUMN email VARCHAR(100) NOT NULL;

CREATE TABLE discentes (
    numero_matricula INT(11) NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    genero ENUM('Masculino', 'Feminino', 'Outro') NOT NULL,
    data_nascimento DATE NOT NULL,
    observacoes TEXT DEFAULT NULL,
    PRIMARY KEY (numero_matricula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE discentes
ADD COLUMN uf VARCHAR(2) NOT NULL,
ADD COLUMN cpf VARCHAR(14) NOT NULL,
ADD COLUMN reprovacoes INT(11) DEFAULT 0,
ADD COLUMN acompanhamento ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN apoio_psicologico ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN auxilio_permanencia ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN cotista ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN estagio ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN acompanhamento_saude ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN projeto_pesquisa ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN projeto_extensao ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN projeto_ensino ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN foto VARCHAR(255) DEFAULT NULL;


ALTER TABLE usuarios 
ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL;

ALTER TABLE setores 
ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL;

ALTER TABLE `turmas`
ADD COLUMN `curso_id` INT NOT NULL,
ADD FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`);

CREATE TABLE `discentes_turmas` (
  `numero_matricula` INT NOT NULL,
  `turma_numero` INT NOT NULL,
  `turma_ano` YEAR NOT NULL,
  `turma_ano_ingresso` YEAR NOT NULL,
  FOREIGN KEY (`numero_matricula`) REFERENCES `discentes` (`numero_matricula`) ON DELETE CASCADE,
  FOREIGN KEY (`turma_numero`, `turma_ano`, `turma_ano_ingresso`) REFERENCES `turmas` (`numero`, `ano`, `ano_ingresso`),
  PRIMARY KEY (`numero_matricula`, `turma_numero`, `turma_ano`, `turma_ano_ingresso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


