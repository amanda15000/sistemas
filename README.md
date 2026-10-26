# sistemas

# Sistema de Gerenciamento de Chamados

Sistema web em **PHP** para centralizar e acompanhar solicitações de suporte técnico. O usuário abre um chamado, o atendente assume e resolve, e o administrador gerencia o sistema.

Projeto da unidade curricular **Projeto de Back-End** (SENAI).
**Equipe:** (nomes dos integrantes)
**Repositório:** (link do GitHub)

---

## Como testar o sistema (passo a passo)

### O que você precisa

- **XAMPP** instalado (com PHP 7.4 ou superior). Download: https://www.apachefriends.org
- Um navegador (Chrome, Edge ou Firefox).
- **Git** ou **GitHub Desktop** (ou baixe o projeto em `.zip` pelo botão verde **Code** do GitHub).

### Passo 1: baixar o projeto

Coloque o projeto dentro da pasta `htdocs` do XAMPP, de modo que o caminho final seja exatamente:

```
C:\xampp\htdocs\sistema_chamados
```

- **Com Git:** abra o terminal em `C:\xampp\htdocs` e rode `git clone (link do repositório)`.
- **Com .zip:** extraia e renomeie a pasta para `sistema_chamados`. Dentro dela devem aparecer `api`, `config`, `database`, `includes`, `public` e `uploads`. Se aparecer outra pasta no meio (por exemplo `sistema_chamados-main`), mova o conteúdo para o nível certo.

### Passo 2: ligar o servidor

1. Abra o **XAMPP Control Panel**.
2. Clique em **Start** em **Apache** e em **MySQL**. Os dois devem ficar verdes.

### Passo 3: criar o banco de dados

1. No navegador, abra `http://localhost/phpmyadmin`. Se o seu Apache usa outra porta, acrescente, por exemplo `http://localhost:8080/phpmyadmin`.
2. Clique na aba **Importar**.
3. Clique em **Escolher arquivo** e selecione `C:\xampp\htdocs\sistema_chamados\database\schema.sql`.
4. Role até o fim e clique em **Importar**.
5. Confira se o banco **chamados** apareceu na lista da esquerda com as tabelas `usuario`, `categoria`, `chamado`, `historico` e `anexo`.

> Se você já tiver um banco chamado `chamados` de outro projeto, apague ou renomeie esse antes de importar.

### Passo 4: abrir o sistema

No navegador, acesse (use a porta do **seu** Apache: `80` é o padrão e não aparece na URL, e `8080` é comum quando a 80 está ocupada):

```
http://localhost/sistema_chamados/public/
```

ou, se o seu Apache estiver na porta 8080:

```
http://localhost:8080/sistema_chamados/public/
```

Para saber a porta, veja a coluna **Port(s)** do Apache no XAMPP Control Panel.

### Passo 5: criar o primeiro usuário (ele vira administrador)

1. Na tela de login, clique em **Cadastre-se**.
2. Preencha nome, e-mail e senha.
3. **O primeiro cadastro do sistema vira automaticamente o Administrador.** Os cadastros seguintes feitos por essa tela entram como **Usuário**.
4. Volte para o login e entre com a conta criada.

### Passo 6: preparar o sistema como administrador

1. Menu **Categorias**: cadastre algumas categorias (por exemplo Hardware, Software, Rede).
2. Menu **Usuários**: cadastre pelo menos **um Atendente** (perfil "Atendente (técnico)").

### Passo 7: testar o fluxo completo

1. Clique em **Sair** e, em **Cadastre-se**, crie um **usuário comum**. Entre com ele.
2. Em **Abrir chamado**, preencha o formulário (se quiser, anexe um print) e clique em **Registrar chamado**.
3. Saia e entre com o **atendente**. Em **Fila de chamados**, abra o chamado e clique em **Assumir este chamado**.
4. Como atendente, altere o **status** (Em andamento, Pendente, Resolvido) e envie uma **mensagem**.
5. Entre de novo como o **usuário comum** e veja o novo status e a resposta do atendente.
6. Entre como **administrador** e use **Todos os chamados**: filtros por número, status e categoria, e atribuição de atendente.
7. Teste um chamado **Fechado**: o campo de mensagem deve sumir.

### Contas de teste (preencha com as suas)

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | (e-mail) | (senha) |
| Atendente | (e-mail) | (senha) |
| Usuário | (e-mail) | (senha) |

---

## Quer testar no computador de outra pessoa (rede local)?

Se as outras equipes forem acessar o **seu** computador em vez de instalar o projeto:

1. O seu computador precisa estar ligado, com Apache e MySQL ativos, na **mesma rede** deles.
2. No Windows, abra o **Prompt de Comando**, digite `ipconfig` e anote o **Endereço IPv4** (algo como `192.168.0.15`).
3. As outras pessoas acessam:

```
http://192.168.0.15:8080/sistema_chamados/public/
```

(troque pelo seu IP e pela sua porta)

4. Se não abrir, o **Firewall do Windows** pode estar bloqueando o Apache. Permita o acesso quando o Windows perguntar, ou libere a porta do Apache.

---

## Problemas comuns

| O que aparece | O que fazer |
|---|---|
| "Falha na conexão com o banco de dados" | O MySQL não está ligado, ou o banco `chamados` não foi importado. Confira o passo 2 e o passo 3. |
| Página não encontrada (404) | A pasta não está em `C:\xampp\htdocs\sistema_chamados`, ou a porta da URL está errada. |
| Erro dizendo que uma tabela não existe | O `schema.sql` não foi importado por completo. Importe de novo. |
| O Apache não inicia | Outra aplicação usa a porta 80. Mude a porta do Apache no XAMPP (por exemplo para 8080) e use essa porta na URL. |
| "E-mail ou senha inválidos" |