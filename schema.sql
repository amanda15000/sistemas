CREATE DATABASE IF NOT EXISTS chamados CHARACTER SET utf8mb4;
USE chamados;

CREATE TABLE IF NOT EXISTS usuario (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nome_usuario VARCHAR(100) NOT NULL,
  email_usuario VARCHAR(100) NOT NULL UNIQUE,
  senha_usuario VARCHAR(255) NOT NULL,
  perfil_usuario ENUM('admin','tecnico','usuario') DEFAULT 'usuario'
);

CREATE TABLE IF NOT EXISTS categoria (
  id_categoria INT AUTO_INCREMENT PRIMARY KEY,
  nome_categoria VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS chamado (
  id_chamado INT AUTO_INCREMENT PRIMARY KEY,
  titulo_chamado VARCHAR(150) NOT NULL,
  descricao_chamado TEXT NOT NULL,
  prioridade_chamado ENUM('baixa','media','alta') DEFAULT 'media',
  status_chamado ENUM('aberto','em_andamento','resolvido','fechado') DEFAULT 'aberto',
  data_registro_chamado DATETIME DEFAULT CURRENT_TIMESTAMP,
  id_usuario INT NOT NULL,
  id_tecnico INT NULL,
  id_categoria INT NOT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  FOREIGN KEY (id_tecnico) REFERENCES usuario(id_usuario),
  FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);

CREATE TABLE IF NOT EXISTS historico (
  id_historico INT AUTO_INCREMENT PRIMARY KEY,
  descricao_historico TEXT NOT NULL,
  data_registro_historico DATETIME DEFAULT CURRENT_TIMESTAMP,
  id_chamado INT NOT NULL,
  FOREIGN KEY (id_chamado) REFERENCES chamado(id_chamado)
);

-- Categorias iniciais
INSERT INTO categoria (nome_categoria) VALUES
('Hardware'),
('Software'),
('Rede'),
('Impressora'),
('Acesso e senhas');