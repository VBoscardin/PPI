
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
    `coordenador` VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO  `cursos` (`id`, `nome`, `coordenador`)
VALUES 
(0, 'Técnico em Informática', NULL);
INSERT INTO  `cursos` (`id`, `nome`, `coordenador`)
VALUES 
(0, 'Técnico em Aministração', NULL);
INSERT INTO  `cursos` (`id`, `nome`, `coordenador`)
VALUES 
(0, 'Técnico em Agropecuária', NULL);

CREATE TABLE `disciplinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO  `disciplinas` (`id`, `curso_id`, `nome`)
VALUES
(0, 1, 'Matemática');
INSERT INTO  `disciplinas` (`id`, `curso_id`, `nome`)
VALUES
(0, 2, 'Língua Portuguesa');
INSERT INTO  `disciplinas` (`id`, `curso_id`, `nome`)
VALUES
(0, 3, 'Contabilidade');

CREATE TABLE `setores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `local` varchar(100) NOT NULL,
  `nome` varchar(100) NOT NULL,
  
  `senha` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `siape` varchar(14) NOT NULL UNIQUE,
  `senha` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO   `docentes` (`id`, `nome`, `email`, `siape`, `senha`)
VALUES
(0, 'João', 'joao@gmail.com', '12345678901', '1234');
INSERT INTO   `docentes` (`id`, `nome`, `email`, `siape`, `senha`)
VALUES
(0, 'César', 'cesar@gmail.com', '12345678902', '1234');

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
  `professor_regente` INT NULL,
  `curso_id` INT NOT NULL,
  PRIMARY KEY (`numero`),
  FOREIGN KEY (`professor_regente`) REFERENCES `docentes` (`id`),
  FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `turmas` (`numero`, `ano`, `ano_ingresso`, `ano_oferta`, `professor_regente`, `curso_id`)
VALUES
(14, 2024, 2024, 2026, 1, 1),
(24, 2024, 2023, 2025, 1, 1),
(34, 2024, 2022, 2024, 1, 1);



CREATE TABLE `turmas_disciplinas` (
  `turma_numero` INT NOT NULL,
  `turma_ano` YEAR NOT NULL,
  `turma_ano_ingresso` YEAR NOT NULL,
  `disciplina_id` INT NOT NULL,
  FOREIGN KEY (`turma_numero`) REFERENCES `turmas` (`numero`),
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



CREATE TABLE `discentes_turmas` (
  `numero_matricula` INT NOT NULL,
  `turma_numero` INT NOT NULL,
  `turma_ano` YEAR NOT NULL,
  `turma_ano_ingresso` YEAR NOT NULL,
  FOREIGN KEY (`numero_matricula`) REFERENCES `discentes` (`numero_matricula`) ON DELETE CASCADE,
  FOREIGN KEY (`turma_numero`) REFERENCES `turmas` (`numero`),
  PRIMARY KEY (`numero_matricula`, `turma_numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE docentes_disciplinas ADD COLUMN turma_numero VARCHAR(50), ADD COLUMN turma_ano INT;

ALTER TABLE turmas
ADD COLUMN presidente_id INT NUll,
ADD FOREIGN KEY (presidente_id) REFERENCES discentes(numero_matricula);

CREATE TABLE `notas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `discente_id` INT NOT NULL,
  `disciplina_id` INT NOT NULL,
  `turma_numero` INT NOT NULL,
  `turma_ano` YEAR NOT NULL,

  -- Notas Parciais e Semestrais
  `parcial_1` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`parcial_1` >= 0 AND `parcial_1` <= 10),
  `nota_semestre_1` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`nota_semestre_1` >= 0 AND `nota_semestre_1` <= 10),
  `parcial_2` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`parcial_2` >= 0 AND `parcial_2` <= 10),
  `nota_semestre_2` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`nota_semestre_2` >= 0 AND `nota_semestre_2` <= 10),

  -- Nota Final e Exame
  `nota_final` DECIMAL(5, 2) DEFAULT NULL CHECK (`nota_final` >= 0 AND `nota_final` <= 10),
  `nota_exame` DECIMAL(5, 2) DEFAULT NULL CHECK (`nota_exame` >= 0 AND `nota_exame` <= 10),

  -- Frequência
  `faltas` INT NOT NULL DEFAULT 0 CHECK (`faltas` >= 0),

  -- Informações adicionais
  `observacoes` TEXT DEFAULT NULL,

  PRIMARY KEY (`id`),

  -- Relações e integridade referencial
  FOREIGN KEY (`discente_id`) REFERENCES `discentes` (`numero_matricula`) ON DELETE CASCADE,
  FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`turma_numero`) REFERENCES `turmas` (`numero`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_numero INT,
    discente_id INT,
    FOREIGN KEY (turma_numero) REFERENCES turmas(numero),
    FOREIGN KEY (discente_id) REFERENCES discentes(numero_matricula)
);


ALTER TABLE discentes_turmas
DROP FOREIGN KEY discentes_turmas_ibfk_2;

ALTER TABLE discentes_turmas
ADD CONSTRAINT discentes_turmas_ibfk_2
FOREIGN KEY (turma_numero) REFERENCES turmas(numero)
ON DELETE CASCADE
ON UPDATE CASCADE;

ALTER TABLE notas 
DROP FOREIGN KEY notas_ibfk_3;

ALTER TABLE notas 
ADD CONSTRAINT notas_ibfk_3 
FOREIGN KEY (turma_numero) 
REFERENCES turmas(numero) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

ALTER TABLE turmas DROP FOREIGN KEY turmas_ibfk_1;

ALTER TABLE turmas ADD CONSTRAINT turmas_ibfk_1 
FOREIGN KEY (professor_regente) REFERENCES docentes(id) ON DELETE SET NULL;
