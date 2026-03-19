const API_BASE = "http://localhost:9000";
const TASKS_ENDPOINT = `${API_BASE}/tasks`;

async function request(url, options = {}) {
  const response = await fetch(url, options);
  const hasBody = response.status !== 204;
  const json = hasBody ? await response.json().catch(() => ({})) : null;

  if (!response.ok) {
    const message = json?.error || "Erro na requisição da API.";
    throw new Error(message);
  }

  return json;
}

export async function getTasks() {
  return request(TASKS_ENDPOINT);
}

export async function createTaskAPI(data) {
  return request(TASKS_ENDPOINT, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data)
  });
}

export async function updateTaskAPI(id, data) {
  return request(`${TASKS_ENDPOINT}/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data)
  });
}

export async function deleteTaskAPI(id) {
  return request(`${TASKS_ENDPOINT}/${id}`, {
    method: "DELETE"
  });
}
