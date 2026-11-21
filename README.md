# Gyeoul Moda — E-commerce de moda de inverno

Projeto final do 4º bimestre — aplicação web de e-commerce com fluxo de venda ponta a ponta (vitrine → carrinho → checkout → confirmação → painel administrativo).

Desenvolvido em **PHP puro (sem frameworks)** com PDO e MySQL/MariaDB, front-end em HTML5, CSS3, Bootstrap 5 e JavaScript vanilla.

---

## Sumário

1. [Tema e identidade visual](#tema-e-identidade-visual)
2. [Stack](#stack)
3. [Requisitos de ambiente](#requisitos-de-ambiente)
4. [Estrutura de pastas](#estrutura-de-pastas)
5. [Instalação passo a passo](#instalação-passo-a-passo)
6. [Credenciais de teste](#credenciais-de-teste)
7. [Funcionalidades](#funcionalidades)
8. [Segurança — decisões técnicas](#segurança--decisões-técnicas)
9. [HTML5 utilizado](#html5-utilizado)
10. [Limitações conhecidas](#limitações-conhecidas)
11. [Melhorias futuras](#melhorias-futuras)
12. [Checklist de aceite](#checklist-de-aceite)
13. [Histórico de commits planejados](#histórico-de-commits-planejados)

---

## Tema e identidade visual

- **Nome:** Gyeoul Moda
- **Segmento:** moda de inverno (casacos, cachecóis, gorros, botas)
- **Paleta:** azul claro `#bdd1f0` · dourado `#e3cd7d` · branco/cinza-claro
- **Estilo:** claro, chique, **bordas quadradas** (sem `border-radius`) e tipografia **Archivo** (Google Fonts, substituta gratuita da Archia).
- **Bootstrap:** 5.3.3 (via CDN)

---

## Stack

| Camada | Tecnologia |
|---|---|
| Back-end | PHP **8.2+** (testado em 8.2.12 do XAMPP) |
| Banco | **MariaDB 10.4+** (o "MySQL" do XAMPP) via **PDO** |
| Front-end | HTML5 · CSS3 · JavaScript vanilla |
| Framework CSS | Bootstrap 5.3.3 (CDN) |
| Tipografia | Archivo (Google Fonts) |
| Gerenciamento de dependências | **Nenhum** — parser próprio de `.env`, sem Composer |
| Servidor | **Apache 2.4** (XAMPP) |
| Versionamento | Git + GitHub |

> Não são usados frameworks PHP (Laravel, Symfony…), ORMs nem construtores de site (Wix, WordPress, Squarespace).

---

## Requisitos de ambiente

- **XAMPP** com PHP 8.2+ e Apache na porta 80
- **MariaDB/MySQL** rodando localmente
- **Navegador moderno** (Chrome, Firefox, Edge, Safari)

O projeto **não usa Composer** e **não requer nenhuma instalação adicional** além do XAMPP.

---

## Estrutura de pastas

```
gyeoulmoda/
├── index.php                 # Vitrine com busca e filtro
├── produto.php               # Detalhe do produto
├── carrinho.php              # Carrinho (add/update/remove/clear)
├── checkout.php              # Finalização (requer login)
├── confirmacao.php           # Resumo do pedido (dono ou admin)
├── login.php / cadastro.php / logout.php
├── meus-pedidos.php          # Histórico do cliente (bônus)
├── .env.example / .gitignore
├── README.md
├── config/
│   ├── env.php               # Parser próprio de .env + constantes
│   └── database.php          # Conexão PDO singleton
├── includes/
│   ├── header.php / footer.php
│   ├── produto_card.php      # Card reutilizável
│   ├── auth.php              # Sessão + login/logout + require_login/admin
│   ├── cart.php              # Helpers do carrinho em $_SESSION
│   ├── csrf.php              # Token CSRF por sessão
│   └── helpers.php           # e(), url(), redirect(), flash, old, preco_brl
├── admin/
│   ├── _nav.php
│   ├── index.php             # Dashboard
│   ├── produtos.php          # CRUD de produtos (com upload)
│   ├── pedidos.php           # Listagem de pedidos
│   └── pedido-itens.php      # Detalhe do pedido
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   └── img/
│       ├── sem-imagem.svg
│       └── produtos/         # ← coloque aqui suas imagens (.jpg)
└── database/
    ├── schema.sql            # DROP/CREATE + seed (4 cat, 16 produtos, 1 pedido)
    └── seed_usuarios.php     # Gera hashes reais com password_hash()
```

---

## Instalação passo a passo

### 1) Clonar e configurar o `.env`

```bash
git clone https://github.com/<seu-usuario>/ecommerce-moda.git gyeoulmoda
cd gyeoulmoda
cp .env.example .env
```

Edite `.env` se necessário (padrão do XAMPP: `DB_USER=root`, `DB_PASS=` vazio).

### 2) Criar o banco e importar o schema

```sql
CREATE DATABASE IF NOT EXISTS gyeoulmoda
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importe `database/schema.sql`:
- **phpMyAdmin:** selecione o banco `gyeoulmoda` → aba **Importar** → escolha `database/schema.sql` → **Executar**.
- **CLI:**
  ```bash
  mysql -u root gyeoulmoda < database/schema.sql
  ```

### 3) Gravar os hashes das senhas de teste

Abra no navegador:

```
http://localhost/gyeoulmoda/database/seed_usuarios.php
```

Saída esperada:

```
== seed_usuarios.php ==
[OK] admin@gyeoulmoda.local (perfil: admin)
[OK] cliente@gyeoulmoda.local (perfil: cliente)

Pronto. Senhas gravadas com password_hash(PASSWORD_DEFAULT).
```

> **Importante:** após rodar, **apague** `database/seed_usuarios.php` do servidor (não deve ir para produção).

### 4) Colocar as imagens dos produtos

Copie seus arquivos `.jpg` para `assets/img/produtos/` respeitando os nomes do seed:

```
casaco-01.jpg   casaco-02.jpg   casaco-03.jpg   casaco-04.jpg
cachecol-01.jpg cachecol-02.jpg cachecol-03.jpg cachecol-04.jpg
gorro-01.jpg    gorro-02.jpg    gorro-03.jpg    gorro-04.jpg
bota-01.jpg     bota-02.jpg     bota-03.jpg     bota-04.jpg
```

**Imagens ausentes não quebram a interface** — o `onerror` exibe `assets/img/sem-imagem.svg` automaticamente.

### 5) Acessar

```
http://localhost/gyeoulmoda/
```

---

## Credenciais de teste

| Perfil | E-mail | Senha |
|---|---|---|
| **Admin** | `admin@gyeoulmoda.local` | `Admin@123` |
| **Cliente** | `cliente@gyeoulmoda.local` | `Cliente@123` |

> As senhas são armazenadas com `password_hash(..., PASSWORD_DEFAULT)` (bcrypt).

---

## Funcionalidades

### Público
- Vitrine dinâmica com **busca** (nome/descrição) e **filtro por categoria** via GET
- Página de produto com disponibilidade (em estoque / esgotado)
- Carrinho em `$_SESSION` com adicionar, atualizar, remover e esvaziar
- Totais e contagem recalculados **no servidor** em cada renderização
- Quantidade sempre limitada ao estoque

### Autenticação
- Cadastro com validação server-side
- Login com `password_verify` e mensagens genéricas
- Logout com destruição de sessão
- Preservação de intenção via `?next=` (protegida contra open redirect)

### Checkout (usuário logado)
- 3 formas de pagamento **simuladas**: PIX, Boleto, Cartão
- Pedido criado em **transação PDO**:
  1. `SELECT … FOR UPDATE` para travar as linhas
  2. Preço congelado em `pedido_itens.preco_unitario`
  3. Baixa condicional de estoque (`WHERE estoque >= :qtd`)
  4. Carrinho limpo **somente após `commit`**
- Página de confirmação com resumo (dono ou admin)

### Cliente logado (bônus)
- **Meus pedidos** — histórico com data, forma, qtd., total e link para detalhe

### Administração (`/admin`)
- **Dashboard** com KPIs e últimas movimentações
- **CRUD de produtos** com upload validado (JPG/PNG/WEBP ≤ 2 MB via `finfo`), ativar/desativar e excluir
- **Listagem de pedidos** com cliente, forma, itens e total
- **Detalhe do pedido** com preço congelado

---

## Segurança — decisões técnicas

- **Credenciais em `.env`** (não versionado) e carregadas por **parser próprio** (sem Composer).
- **100% das queries** usam **prepared statements** (`PDO::ATTR_EMULATE_PREPARES = false`).
- **Senhas:** `password_hash(PASSWORD_DEFAULT)` + `password_verify`.
- **Sessão:** cookie `httponly`, `samesite=Lax`, `secure` sob HTTPS, `session_regenerate_id(true)` no login, timeout de 30 min.
- **CSRF:** token por sessão validado com `hash_equals` em **todos** os POSTs.
- **Rotas `/admin/`:** `require_admin()` no topo de cada arquivo.
- **Open redirect:** `next_seguro()` rejeita URLs absolutas e caminhos fora de `APP_URL`.
- **Escape de saída:** `e()` = `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- **Upload de imagem:** MIME real via `finfo_file`, whitelist de extensão, 2 MB, nome aleatório.
- **Erros:** mensagens genéricas fora de `APP_ENV=development`.
- **Checkout:** `SELECT … FOR UPDATE` + `UPDATE … WHERE estoque >= :qtd` + `rowCount()`.

---

## HTML5 utilizado

O projeto usa HTML5 **puro e semântico** intercalado com PHP. Sem template engine nem construtor.

| Recurso | Uso |
|---|---|
| `<!DOCTYPE html>` + `<meta viewport>` | Em todas as páginas |
| `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>` | Landmarks semânticos |
| `<input type="email|search|number">` + `required`/`min`/`max`/`autocomplete` | Teclado correto + acessibilidade |
| `aria-*` + `label` + `alt` + foco visível | Acessibilidade |
| `loading="lazy"` | Performance |

---

## Limitações conhecidas

1. **Pagamentos são simulados.**
2. **Frete não incluído.**
3. **Imagens dos produtos são colocadas manualmente.**
4. **O nome do produto não é congelado no pedido** (só o preço).
5. **Imagens antigas não são apagadas** ao substituir.
6. **Listagens sem paginação.**
7. **Sem rate limiting / captcha.**
8. **Logout via GET sem CSRF** (decisão consciente).
9. **Bootstrap via CDN** — em ambiente offline, o layout perde estilização.

---

## Melhorias futuras

- Gateway real (Mercado Pago, Stripe, PagSeguro)
- Recuperação de senha por e-mail
- Edição de dados do cliente
- Frete por CEP (Correios, Melhor Envio)
- Paginação nas listagens
- Rate limiting / captcha
- Empacotar Bootstrap e Archivo localmente
- Testes automatizados

---

## Checklist de aceite

| # | Critério | Status |
|---|---|---|
| 1 | Stack conforme | ✅ |
| 2 | `.env` em `.gitignore`, `.env.example` versionado | ✅ |
| 3 | 100% prepared statements | ✅ |
| 4 | `password_hash` / `password_verify` | ✅ |
| 5 | `/admin` protegida por sessão + perfil | ✅ |
| 6 | Catálogo dinâmico + filtro + busca | ✅ |
| 7 | Página de produto com disponibilidade | ✅ |
| 8 | Carrinho em `$_SESSION` com add/update/remove e totais | ✅ |
| 9 | Quantidade limitada ao estoque | ✅ |
| 10 | Checkout só para logado, 3 formas simuladas | ✅ |
| 11 | Transação: pedido + itens + baixa + limpeza | ✅ |
| 12 | Admin CRUD de produtos | ✅ |
| 13 | Admin lista pedidos com itens e total | ✅ |
| 14 | `schema.sql` idempotente + seed | ✅ |
| 15 | README e `.env.example` completos | ✅ |
| 16 | Roda no XAMPP sem warnings | ✅ |
| 17 | Commits incrementais por etapa | ✅ |

---

## Histórico de commits planejados

```
01. chore: estrutura inicial, .gitignore e README em branco
02. feat(db): schema.sql com DROP/CREATE, seed e script seed_usuarios.php
03. feat(config): parser .env, constantes da aplicação e conexão PDO singleton
04. feat(security): sessão segura, helpers de auth e CSRF token
05. feat(catalog): vitrine com busca e filtro por categoria
06. feat(product): página de detalhe do produto
07. feat(auth): cadastro, login e logout com hash e regeneração de sessão
08. feat(cart): carrinho em $_SESSION com validação de estoque
09. feat(checkout): pedido transacional com baixa de estoque
10. feat(order): página de confirmação do pedido
11. feat(admin): dashboard administrativo com KPIs
12. feat(admin): CRUD de produtos com upload de imagem
13. feat(admin): listagem e detalhe de pedidos
14. feat(customer): página 'meus pedidos' do cliente
15. docs: README completo e checklist de aceite
16. chore: QA final, ajustes de estilo e revisão
```

---

## Entrega

- **Repositório:** `https://github.com/<seu-usuario>/ecommerce-moda`
- **Link do projeto em execução:** `http://localhost/gyeoulmoda/`
- **Credenciais de teste:** ver seção [Credenciais de teste](#credenciais-de-teste)