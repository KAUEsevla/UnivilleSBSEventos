# UnivilleSBSEventos

Sistema de gestão de eventos da universidade: solicitação, aprovação e divulgação de eventos, com controle de ambientes e horários.

## Tecnologias utilizadas

| Camada | Tecnologia | Uso no sistema |
|---|---|---|
| **Servidor web** | Apache (XAMPP) | Serve as páginas PHP e os arquivos estáticos |
| **Back-end** | PHP 8 | Regras de negócio, sessões, validação dos formulários e geração do CSV |
| **Acesso a dados** | PDO (consultas preparadas) | Conexão com o banco e proteção contra SQL Injection |
| **Banco de dados** | MySQL / MariaDB | Usuários, cadastros e solicitações (`database/schema.sql`) |
| **Front-end** | HTML5 + CSS3 | Layout responsivo (computador e celular) com CSS Grid/Flexbox e variáveis de cor |
| **Interatividade** | JavaScript puro (ES6+, sem frameworks) | Pop-ups, ordenação das tabelas, horários por período e consulta de horários ocupados via Fetch API |
| **Ícones** | SVG embutido | Ícone de lixeira dos botões de exclusão (sem bibliotecas externas) |
| **Segurança** | `password_hash`, token CSRF, cookies `HttpOnly` | Senhas criptografadas e proteção dos formulários |
| **Versionamento** | Git + GitHub | Controle de versão e atualização do sistema |

Não há dependências externas para instalar (sem Composer, npm ou CDN): basta o XAMPP.

---

## Sumário

1. [Pré-requisitos](#1-pré-requisitos)
2. [Baixar o sistema](#2-baixar-o-sistema)
3. [Iniciar o Apache e o MySQL](#3-iniciar-o-apache-e-o-mysql)
4. [Criar o banco de dados](#4-criar-o-banco-de-dados)
5. [Configurar a conexão (se necessário)](#5-configurar-a-conexão-se-necessário)
6. [Acessar o sistema](#6-acessar-o-sistema)
7. [Primeiros passos no sistema](#7-primeiros-passos-no-sistema)
8. [Acessar pelo celular (mesma rede)](#8-acessar-pelo-celular-mesma-rede)
9. [Atualizar para a versão mais nova](#9-atualizar-para-a-versão-mais-nova)
10. [Problemas comuns](#10-problemas-comuns)
11. [Funcionalidades e regras](#11-funcionalidades-e-regras)
12. [Estrutura de pastas](#12-estrutura-de-pastas)

---

## 1. Pré-requisitos

| Programa | Para quê | Download |
|---|---|---|
| **XAMPP** (com PHP 8.0 ou superior) | Apache + PHP + MySQL | https://www.apachefriends.org/pt_br/download.html |
| **Git** *(opcional, recomendado)* | Baixar e atualizar o sistema | https://git-scm.com/downloads |
| **MySQL Workbench** *(opcional)* | Executar o script do banco | https://dev.mysql.com/downloads/workbench/ |

> Sem o Workbench também dá: o próprio XAMPP traz o **phpMyAdmin**.

Durante a instalação do XAMPP, mantenha marcados **Apache**, **MySQL**, **PHP** e **phpMyAdmin**. O caminho padrão no Windows é `C:\xampp`.

---

## 2. Baixar o sistema

O sistema precisa ficar **dentro da pasta `htdocs`** do XAMPP.

| Sistema operacional | Pasta `htdocs` |
|---|---|
| Windows | `C:\xampp\htdocs` |
| Linux | `/opt/lampp/htdocs` |
| macOS | `/Applications/XAMPP/htdocs` |

### Opção A — com Git (recomendado)

Abra o **Prompt de Comando** (ou Git Bash / terminal) e execute:

```bash
cd C:\xampp\htdocs
git clone https://github.com/KAUEsevla/UnivilleSBSEventos.git eventos
```

Isso cria a pasta `C:\xampp\htdocs\eventos` com todo o sistema.

### Opção B — sem Git (arquivo ZIP)

1. Acesse https://github.com/KAUEsevla/UnivilleSBSEventos
2. Clique no botão verde **Code → Download ZIP**.
3. Extraia o ZIP dentro de `C:\xampp\htdocs`.
4. Renomeie a pasta extraída (`UnivilleSBSEventos-main`) para **`eventos`**.

> O nome da pasta define o endereço: pasta `eventos` → `http://localhost/eventos`. Pode usar outro nome; o sistema detecta automaticamente.

---

## 3. Iniciar o Apache e o MySQL

1. Abra o **XAMPP Control Panel**.
2. Clique em **Start** em **Apache** e em **MySQL** (os dois devem ficar verdes).

---

## 4. Criar o banco de dados

O script `database/schema.sql` cria o banco **`gestao_eventos`**, todas as tabelas e o **primeiro administrador**. Escolha **uma** das formas:

### Opção A — MySQL Workbench

1. Abra o Workbench e conecte em `localhost:3306`, usuário `root` (no XAMPP a senha vem em branco).
2. Menu **File → Open SQL Script…** → selecione `C:\xampp\htdocs\eventos\database\schema.sql`.
3. Clique no **raio ⚡ (Execute)**.
4. Atualize a lista de *Schemas*: deve aparecer `gestao_eventos`.

### Opção B — phpMyAdmin

1. Acesse http://localhost/phpmyadmin
2. Aba **Importar** → **Escolher arquivo** → `database/schema.sql` → **Importar**.

### Opção C — linha de comando

```bash
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\eventos\database\schema.sql
```

> O script pode ser executado mais de uma vez sem apagar dados (usa `IF NOT EXISTS`).

---

## 5. Configurar a conexão (se necessário)

Por padrão o sistema usa a configuração do XAMPP: usuário `root`, **sem senha**, porta `3306`.

Se o seu MySQL tiver senha ou outra porta, edite `config/config.php`:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'gestao_eventos');
define('DB_USER', 'root');
define('DB_PASS', '');   // coloque a senha aqui, se houver
```

---

## 6. Acessar o sistema

Abra no navegador: **http://localhost/eventos**

Clique no **ícone de usuário** no canto superior direito e entre com:

| Usuário | Senha |
|---|---|
| `admin` | `admin123` |

> ⚠️ **Troque essa senha no primeiro acesso:** menu **Usuários → Editar** (no usuário Administrador) → campo *Nova senha*.

---

## 7. Primeiros passos no sistema

Como administrador, cadastre nesta ordem (os formulários de solicitação usam essas listas):

1. **Cursos/Áreas** — nome e centro de custo.
2. **Ambientes** — nome e localização (ex.: *Auditório — 3º andar*).
3. **Temas**.
4. **Usuários** — solicitantes e outros administradores (não existe autocadastro).

Depois disso, os solicitantes já podem entrar e criar solicitações.

---

## 8. Acessar pelo celular (mesma rede)

1. No computador com o XAMPP, descubra o IP: abra o Prompt e digite `ipconfig` → anote o **Endereço IPv4** (ex.: `192.168.0.15`).
2. No celular, conectado **na mesma rede Wi-Fi**, acesse `http://192.168.0.15/eventos`.
3. Se não abrir, libere o Apache no **Firewall do Windows** (Painel de Controle → Firewall → *Permitir um aplicativo* → marque *Apache HTTP Server* em rede Privada).

---

## 9. Atualizar para a versão mais nova

Se instalou com Git:

```bash
cd C:\xampp\htdocs\eventos
git pull
```

Se instalou pelo ZIP, baixe novamente e substitua os arquivos (mantenha seu `config/config.php` se você o alterou).

---

## 10. Problemas comuns

| Sintoma | Solução |
|---|---|
| *"Erro ao conectar ao banco de dados"* | Verifique se o **MySQL** está iniciado no XAMPP, se o banco `gestao_eventos` foi criado (passo 4) e se usuário/senha em `config/config.php` estão corretos. |
| Página **404 / Not Found** | Confira se a pasta está em `htdocs` e o nome usado na URL (`/eventos`). |
| Apache não inicia (porta 80 ocupada) | Feche Skype/IIS ou mude a porta do Apache no XAMPP (*Config → httpd.conf → Listen 8080*) e acesse `http://localhost:8080/eventos`. |
| MySQL não inicia (porta 3306 ocupada) | Pare outro MySQL instalado na máquina (Serviços do Windows) ou altere a porta e ajuste `DB_PORT`. |
| Página sem cores/estilo | Atualize com **Ctrl + F5**; confira se a pasta `assets` foi copiada. |
| *"Usuário ou senha inválidos"* no primeiro acesso | Rode novamente o `database/schema.sql` — ele recria o `admin` se não existir. |
| CSV com acentos estranhos | Abra pelo Excel normalmente (o arquivo já sai em UTF-8 com BOM e separador `;`). |

---

## 11. Funcionalidades e regras

### Perfis
- **Administrador** — gerencia tudo; o primeiro é criado pelo script do banco, os próximos são cadastrados por outros administradores.
- **Solicitante** — cria e acompanha **apenas as próprias** solicitações.

### Página pública
- Lista os eventos **aprovados** do **mês atual até 2 meses à frente** (ex.: em 30/jan mostra janeiro, fevereiro e março).
- Colunas: Evento, Tema, Início (data e hora início/fim), Local, Palestrante/Ministrante.
- Login pelo ícone no canto superior direito.

### Administrador (menu lateral)
- **Solicitações** — tela inicial; mostra as *Pendentes* por ordem de criação, com filtros *Em revisão, Aprovados, Reprovados, Todos*. Botão **Decisão** abre pop-up: **Aprovar**, **Reprovar** ou **Ajustar** (motivo obrigatório para reprovar/ajustar). A decisão vai para todos os admins e **o primeiro que decidir se torna o aprovador**.
- Pode **cadastrar eventos direto** (já entram aprovados) e **editar qualquer solicitação**.
- **Usuários** — cadastrar, editar e inativar (admin e solicitante).
- **Cursos/Áreas**, **Ambientes**, **Temas** — cadastrar, editar e inativar.
- **Relatórios** — exporta **CSV** escolhendo período (data do evento ou de criação), situações e colunas.

### Solicitação
- Campos: nome do evento, curso/área, tema, palestrante (opcional), ambiente, data, período e horário.
- **Período** libera o horário dentro da faixa: Matutino 08:00–11:00 · Vespertino 13:00–17:00 · Noturno 19:00–22:30 (ajustável em `PERIODOS`, `includes/functions.php`).
- **Recursos:** Datashow/Projetor, Microfone, Som, Outros (abre campo descritivo).
- Solicitante só edita o que está **Pendente** ou **Em revisão**; em revisão vê a observação do admin e, ao reenviar, volta para Pendente.
- **Bloqueio de agenda:** um evento aprovado reserva o **período inteiro** (matutino, vespertino ou noturno) daquele ambiente naquela data. Outra solicitação só é bloqueada quando é para o mesmo ambiente, na mesma data e no mesmo período de um evento já aprovado; isso é conferido no envio e na aprovação. Solicitações pendentes ou em revisão não bloqueiam.

### Segurança
Senhas com `password_hash`, consultas preparadas (PDO), token CSRF nos formulários, usuários inativos não acessam.

---

## 12. Estrutura de pastas

```
index.php                   Agenda pública + login
login.php / logout.php
admin/
  solicitacoes.php          Lista, filtros e pop-up de decisão
  solicitacao_form.php      Admin cria evento (aprovado) ou edita qualquer solicitação
  usuarios.php              Usuários
  cursos.php                Cursos/Áreas
  ambientes.php             Ambientes
  temas.php                 Temas
  relatorios.php            Relatórios CSV
solicitante/
  minhas.php                Minhas solicitações
  form.php                  Nova solicitação / edição
api/ocupados.php            Horários reservados (usado no formulário)
config/config.php           Conexão com o banco
includes/                   Funções, layout e componentes compartilhados
assets/                     CSS, JavaScript e imagens
database/schema.sql         Script de criação do banco
```
