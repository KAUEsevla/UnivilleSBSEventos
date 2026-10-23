-- =========================================================
-- Sistema de Gestão de Eventos - Universidade
-- Banco: MySQL / MariaDB (XAMPP)
-- Execute este script no MySQL Workbench
-- =========================================================

CREATE DATABASE IF NOT EXISTS gestao_eventos
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE gestao_eventos;

-- ---------------------------------------------------------
-- Usuários (administrador / solicitante)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(150) NOT NULL,
  usuario       VARCHAR(60)  NOT NULL UNIQUE,
  email         VARCHAR(150) NULL,
  senha_hash    VARCHAR(255) NOT NULL,
  perfil        ENUM('admin','solicitante') NOT NULL DEFAULT 'solicitante',
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Cursos / Áreas
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS cursos_areas (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(150) NOT NULL,
  centro_custo  VARCHAR(50)  NOT NULL,
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Ambientes (ex.: Auditório - 3º andar)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS ambientes (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(150) NOT NULL,
  localizacao   VARCHAR(150) NOT NULL,
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Temas
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS temas (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(150) NOT NULL,
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Solicitações de eventos
-- status: pendente | aprovado | reprovado | revisao
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS solicitacoes (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  solicitante_id     INT NOT NULL,
  curso_area_id      INT NOT NULL,
  tema_id            INT NOT NULL,
  ambiente_id        INT NOT NULL,
  titulo             VARCHAR(200) NOT NULL,
  palestrante        VARCHAR(200) NULL,
  data_evento        DATE NOT NULL,
  periodo            ENUM('matutino','vespertino','noturno') NOT NULL,
  hora_inicio        TIME NOT NULL,
  hora_fim           TIME NOT NULL,
  rec_datashow       TINYINT(1) NOT NULL DEFAULT 0,
  rec_microfone      TINYINT(1) NOT NULL DEFAULT 0,
  rec_som            TINYINT(1) NOT NULL DEFAULT 0,
  rec_outros         TINYINT(1) NOT NULL DEFAULT 0,
  rec_outros_desc    VARCHAR(500) NULL,
  status             ENUM('pendente','aprovado','reprovado','revisao') NOT NULL DEFAULT 'pendente',
  observacao_admin   TEXT NULL,
  aprovador_id       INT NULL,
  decidido_em        DATETIME NULL,
  criado_em          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sol_solicitante FOREIGN KEY (solicitante_id) REFERENCES usuarios(id),
  CONSTRAINT fk_sol_curso       FOREIGN KEY (curso_area_id)  REFERENCES cursos_areas(id),
  CONSTRAINT fk_sol_tema        FOREIGN KEY (tema_id)        REFERENCES temas(id),
  CONSTRAINT fk_sol_ambiente    FOREIGN KEY (ambiente_id)    REFERENCES ambientes(id),
  CONSTRAINT fk_sol_aprovador   FOREIGN KEY (aprovador_id)   REFERENCES usuarios(id),
  INDEX idx_sol_status (status),
  INDEX idx_sol_agenda (ambiente_id, data_evento, status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Primeiro administrador (cadastrado via banco)
-- Usuário: admin   Senha: admin123   (troque após o 1º acesso)
-- ---------------------------------------------------------
INSERT INTO usuarios (nome, usuario, email, senha_hash, perfil, ativo)
SELECT 'Administrador', 'admin', NULL,
       '$2y$10$.jukD9WDZ13c5IPvd7JA.uKLT.Z3w.QK14swYgU9E1bg6ztlECn/y',
       'admin', 1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'admin');
