const STATUS_OPTIONS = ["pendente", "em andamento", "concluido"];

export function validateTaskFormData(data) {
  if (!data.title) {
    throw new Error("Título é obrigatório.");
  }

  if (data.title.length > 120) {
    throw new Error("Título deve ter no máximo 120 caracteres.");
  }

  if (data.description.length > 1000) {
    throw new Error("Descrição deve ter no máximo 1000 caracteres.");
  }

  if (!STATUS_OPTIONS.includes(data.status)) {
    throw new Error("Status inválido.");
  }

  if (!data.created_at) {
    throw new Error("Data de criação é obrigatória.");
  }
}

export function validateLoginFormData(data) {
  if (!data.username || !data.password) {
    throw new Error("Usuário e senha são obrigatórios.");
  }

  if (data.username.length > 64 || data.password.length > 128) {
    throw new Error("Credenciais inválidas.");
  }
}
