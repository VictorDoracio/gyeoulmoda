-- ============================================================
-- Gyeoul Moda — schema.sql
-- Recria o banco de forma idempotente.
-- Compatível com MySQL 5.7+ / MariaDB 10.4+ (XAMPP).
-- Charset: utf8mb4.
-- Uso: importar via phpMyAdmin OU `mysql -u root < schema.sql`.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS pedido_itens;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- usuarios
-- ------------------------------------------------------------
CREATE TABLE usuarios (
  id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  nome        VARCHAR(120)     NOT NULL,
  email       VARCHAR(190)     NOT NULL,
  senha_hash  VARCHAR(255)     NOT NULL,
  perfil      ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
  criado_em   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- categorias
-- ------------------------------------------------------------
CREATE TABLE categorias (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome  VARCHAR(80)  NOT NULL,
  slug  VARCHAR(80)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- produtos
-- ------------------------------------------------------------
CREATE TABLE produtos (
  id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  categoria_id  INT UNSIGNED  NOT NULL,
  nome          VARCHAR(160)  NOT NULL,
  descricao     TEXT          NOT NULL,
  preco         DECIMAL(10,2) NOT NULL,
  estoque       INT           NOT NULL DEFAULT 0,
  imagem        VARCHAR(255)  NOT NULL,
  ativo         TINYINT(1)    NOT NULL DEFAULT 1,
  criado_em     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_produtos_categoria (categoria_id),
  KEY idx_produtos_ativo     (ativo),
  KEY idx_produtos_nome      (nome),
  CONSTRAINT fk_produtos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- pedidos
-- ------------------------------------------------------------
CREATE TABLE pedidos (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  usuario_id       INT UNSIGNED  NOT NULL,
  data             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  forma_pagamento  ENUM('pix','boleto','cartao') NOT NULL,
  total            DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pedidos_usuario (usuario_id),
  KEY idx_pedidos_data    (data),
  CONSTRAINT fk_pedidos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- pedido_itens
-- ------------------------------------------------------------
CREATE TABLE pedido_itens (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  pedido_id       INT UNSIGNED  NOT NULL,
  produto_id      INT UNSIGNED  NOT NULL,
  quantidade      INT           NOT NULL,
  preco_unitario  DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_itens_pedido  (pedido_id),
  KEY idx_itens_produto (produto_id),
  CONSTRAINT fk_itens_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_itens_produto
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED
-- ============================================================

INSERT INTO categorias (id, nome, slug) VALUES
  (1, 'Casacos',   'casacos'),
  (2, 'Cachecóis', 'cachecois'),
  (3, 'Gorros',    'gorros'),
  (4, 'Botas',     'botas');

-- IMPORTANTE: hashes placeholder. Rode database/seed_usuarios.php após importar.
INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
  ('Administrador',  'admin@gyeoulmoda.local',   'PLACEHOLDER_EXECUTE_SEED_USUARIOS_PHP', 'admin'),
  ('Cliente Teste',  'cliente@gyeoulmoda.local', 'PLACEHOLDER_EXECUTE_SEED_USUARIOS_PHP', 'cliente');

INSERT INTO produtos
  (categoria_id, nome, descricao, preco, estoque, imagem, ativo) VALUES
  (1, 'Casaco Longo Alfaiataria',
      'Casaco alongado em lã fria, corte reto e forro acetinado. Peça-chave para looks elegantes de inverno.',
      489.90, 8,  'casaco-01.jpg', 1),
  (1, 'Casaco Puffer Nylon',
      'Casaco puffer com enchimento térmico e capuz destacável. Leve, quente e resistente ao vento.',
      399.90, 12, 'casaco-02.jpg', 1),
  (1, 'Casaco Trench Bege',
      'Trench coat clássico em gabardine com cinto removível e botões de madrepérola.',
      549.90, 5,  'casaco-03.jpg', 1),
  (1, 'Casaco Cropped Xadrez',
      'Cropped em padrão xadrez inglês, com botões dourados. Sofisticado e versátil.',
      329.90, 0,  'casaco-04.jpg', 1),
  (2, 'Cachecol Lã Merino',
      'Cachecol em lã merino, macio e térmico, com franjas nas pontas.',
      129.90, 20, 'cachecol-01.jpg', 1),
  (2, 'Cachecol Tricô Grosso',
      'Cachecol de tricô largo, toque rústico e aconchegante para dias frios.',
      89.90,  30, 'cachecol-02.jpg', 1),
  (2, 'Cachecol Xadrez Clássico',
      'Cachecol xadrez em tom neutro, ideal para o dia a dia.',
      79.90,  25, 'cachecol-03.jpg', 1),
  (2, 'Estola Seda & Lã',
      'Estola elegante em mistura de seda e lã, acabamento premium.',
      189.90, 10, 'cachecol-04.jpg', 1),
  (3, 'Gorro Tricô com Pompom',
      'Gorro em tricô com pompom removível e forro interno em fleece.',
      59.90,  40, 'gorro-01.jpg', 1),
  (3, 'Gorro Beanie Canelado',
      'Beanie canelado, unissex, em tom de azul-claro. Caimento confortável.',
      49.90,  35, 'gorro-02.jpg', 1),
  (3, 'Gorro de Lã Merino',
      'Gorro fino em lã merino, discreto e aquecido. Combina com qualquer look.',
      69.90,  18, 'gorro-03.jpg', 1),
  (3, 'Gorro com Orelhas',
      'Modelo divertido com orelhas, forro em fleece. Perfeito para o frio intenso.',
      54.90,  22, 'gorro-04.jpg', 1),
  (4, 'Bota Couro Cano Médio',
      'Bota de couro legítimo, cano médio e salto bloco. Elegância e conforto.',
      419.90, 6,  'bota-01.jpg', 1),
  (4, 'Bota Coturno Feminina',
      'Coturno em couro com cadarço, solado antiderrapante e acabamento premium.',
      349.90, 9,  'bota-02.jpg', 1),
  (4, 'Bota Chelsea Camurça',
      'Chelsea em camurça, elástico lateral e bico arredondado. Clássica e atemporal.',
      379.90, 7,  'bota-03.jpg', 1),
  (4, 'Bota Forrada Inverno',
      'Bota forrada em pelúcia interna, impermeável, ideal para baixas temperaturas.',
      459.90, 4,  'bota-04.jpg', 1);

INSERT INTO pedidos (usuario_id, data, forma_pagamento, total) VALUES
  (2, NOW(), 'pix', 619.80);

INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES
  (1, 1, 1, 489.90),
  (1, 5, 1, 129.90);