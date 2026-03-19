const API_BASE = "http://localhost:9000";
const LOGIN_ENDPOINT = `${API_BASE}/auth/login`;
const TOKEN_KEY = "taskflow_access_token";
const USER_KEY = "taskflow_user";

export function getToken() {
  return localStorage.getItem(TOKEN_KEY) || "";
}

export function getCurrentUser() {
  return localStorage.getItem(USER_KEY) || "";
}

export function isAuthenticated() {
  return getToken() !== "";
}

export function clearSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

export function applyAuthHeader(headers = {}) {
  const token = getToken();

  if (!token) {
    return headers;
  }

  return {
    ...headers,
    Authorization: `Bearer ${token}`
  };
}

export async function login(username, password) {
  const response = await fetch(LOGIN_ENDPOINT, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ username, password })
  });

  const json = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(json?.error || "Falha ao autenticar.");
  }

  localStorage.setItem(TOKEN_KEY, json.access_token);
  localStorage.setItem(USER_KEY, json?.user?.username || username);
}
