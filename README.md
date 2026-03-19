# TaskFlow - Lista de Tarefas com JWT

Aplicação full stack para gerenciamento de tarefas com backend em PHP, autenticação JWT, frontend modular ES6, Docker e testes de integração.

## Demonstração (Fluxo completo)

1. Suba a aplicação com Docker.
2. Faça login em `POST /auth/login` para obter o `access_token`.
3. Crie uma tarefa autenticada em `POST /tasks`.
4. Liste tarefas em `GET /tasks`.
5. Atualize tarefa em `PUT /tasks/{id}`.
6. Exclua tarefa em `DELETE /tasks/{id}`.
7. Verifique saúde do sistema em `GET /health`.

## Pré-requisitos

- Docker e Docker Compose
- PHP 8.2+ (para rodar testes locais sem Docker)
- PowerShell (Windows) ou terminal compatível

## Instalação e Configuração

1. Copie o arquivo de exemplo:

```bash
cp .env.example .env
```

2. Ajuste os valores sensíveis no `.env`:
- `JWT_SECRET`
- `AUTH_PASSWORD_HASH`
- `AUTH_SECRET`
- `REFRESH_TOKEN_SECRET`

3. Suba os containers:

```bash
docker compose up -d --build
```

4. Abra no navegador:
- Frontend: `http://localhost:9000/public/`
- API: `http://localhost:9000/`

## Variáveis de Ambiente

Veja todas as variáveis em [.env.example](file:///d:/PROJETOS/TESTES%20PARA%20TRABALHO/lista-de-tarefas/.env.example).

Seções:
- Banco de dados
- JWT
- Autenticação

## Endpoints Principais

### Autenticação

- `POST /auth/login`

Body:

```json
{
  "username": "admin",
  "password": "admin123"
}
```

### Tarefas

- `GET /tasks` (público)
- `POST /tasks` (Bearer Token)
- `PUT /tasks/{id}` (Bearer Token)
- `DELETE /tasks/{id}` (Bearer Token)

### Healthcheck

- `GET /health`
- `GET /api/health`

Resposta inclui:
- status geral
- timestamp
- tempo de resposta
- checks de banco/JWT/auth
- métricas de memória/CPU/disco

## Exemplos cURL

### Login

```bash
curl -X POST http://localhost:9000/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"username\":\"admin\",\"password\":\"admin123\"}"
```

### Criar tarefa autenticada

```bash
curl -X POST http://localhost:9000/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d "{\"title\":\"Estudar arquitetura\",\"description\":\"SOLID e JWT\",\"status\":\"pendente\",\"created_at\":\"2026-03-19\"}"
```

### Listar tarefas

```bash
curl http://localhost:9000/tasks
```

### Healthcheck

```bash
curl http://localhost:9000/health
```

## Testes

### Unitários (sanidade estática / sintaxe)

```bash
Get-ChildItem -Path ".\src" -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

### Integração

```bash
php .\tests\integration\auth_tasks_test.php
php .\tests\integration\healthcheck_test.php
```

### E2E (manual guiado)

1. Acesse `http://localhost:9000/public/`
2. Clique em **Logar** e autentique.
3. Crie/edite/exclua tarefa.
4. Valide comportamento de sessão expirada e relogin.

## Arquitetura e Decisões de Design

- **Router/Controller/Service/Repository** para separação de responsabilidades.
- **JWT HS256** para autenticação stateless.
- **Validação backend + frontend** para garantir consistência e segurança.
- **Sanitização de entrada** com limpeza de campos textuais.
- **Frontend modular ES6** (`api.js`, `auth.js`, `validators.js`, `tasks.js`, `main.js`).
- **Healthcheck observável** para diagnóstico operacional.

## Estrutura de Pastas

```text
lista-de-tarefas/
├── docker/
│   ├── apache/
│   └── mysql/
├── src/
│   ├── config/
│   ├── controllers/
│   ├── models/
│   ├── monitoring/
│   ├── public/
│   │   ├── css/
│   │   └── js/
│   ├── routes/
│   ├── security/
│   ├── .htaccess
│   └── index.php
├── tests/
│   └── integration/
├── .env.example
├── docker-compose.yml
└── README.md
```

## Segurança

- Credenciais sensíveis via variáveis de ambiente.
- Senha de autenticação armazenada como hash bcrypt.
- JWT obrigatório para rotas mutáveis.
- Interceptor de 401 no frontend com relogin automático.

## Observações Operacionais

- Em produção, nunca use segredos de exemplo.
- Use `HTTPS` para proteger tokens em trânsito.
- Rotacione segredos (`JWT_SECRET`, `REFRESH_TOKEN_SECRET`) periodicamente.
