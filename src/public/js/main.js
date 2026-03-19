import { initializeTaskPage, loadTasks } from "./tasks.js";
import { clearSession, getCurrentUser, isAuthenticated, login } from "./auth.js";
import { validateLoginFormData } from "./validators.js";

function updateAuthUI() {
  const badge = document.getElementById("authStatusBadge");
  const btnOpenLoginModal = document.getElementById("btnOpenLoginModal");
  const btnLogout = document.getElementById("btnLogout");
  const loggedIn = isAuthenticated();

  if (!badge || !btnOpenLoginModal || !btnLogout) {
    return;
  }

  if (loggedIn) {
    const user = getCurrentUser() || "admin";
    badge.className = "badge text-bg-success px-3 py-2";
    badge.textContent = `Conectado: ${user}`;
    btnOpenLoginModal.textContent = "Logar";
    btnLogout.disabled = false;
    return;
  }

  badge.className = "badge text-bg-secondary px-3 py-2";
  badge.textContent = "Desconectado";
  btnOpenLoginModal.textContent = "Logar";
  btnLogout.disabled = true;
}

function showAuthFeedback(message, type = "danger") {
  const feedback = document.getElementById("loginModalFeedback");

  if (!feedback) {
    return;
  }

  feedback.textContent = message;
  feedback.className = `alert alert-${type} mt-3 mb-0 py-2`;
  feedback.classList.remove("d-none");
}

function clearAuthFeedback() {
  const feedback = document.getElementById("loginModalFeedback");

  if (!feedback) {
    return;
  }

  feedback.textContent = "";
  feedback.className = "alert d-none mt-3 mb-0 py-2";
}

window.addEventListener("DOMContentLoaded", async () => {
  initializeTaskPage();
  const loginModalElement = document.getElementById("loginModal");
  const authForm = document.getElementById("loginModalForm");
  const btnOpenLoginModal = document.getElementById("btnOpenLoginModal");
  const btnLogout = document.getElementById("btnLogout");
  const loginModal = loginModalElement && window.bootstrap?.Modal
    ? new window.bootstrap.Modal(loginModalElement)
    : null;

  if (btnOpenLoginModal && loginModal) {
    btnOpenLoginModal.addEventListener("click", () => {
      clearAuthFeedback();
      loginModal.show();
    });
  }

  window.addEventListener("auth:required", () => {
    updateAuthUI();
    showAuthFeedback("Sua sessão expirou. Faça login novamente.", "warning");
    if (loginModal) {
      loginModal.show();
    }
  });

  if (authForm) {
    authForm.addEventListener("submit", async event => {
      event.preventDefault();
      clearAuthFeedback();

      const username = document.getElementById("modalUsername")?.value.trim() || "";
      const password = document.getElementById("modalPassword")?.value || "";

      try {
        validateLoginFormData({ username, password });
        await login(username, password);
        authForm.reset();
        updateAuthUI();
        showAuthFeedback("Login realizado com sucesso.", "success");
        if (loginModal) {
          setTimeout(() => loginModal.hide(), 500);
        }
      } catch (error) {
        showAuthFeedback(error.message, "danger");
      }
    });
  }

  if (btnLogout) {
    btnLogout.addEventListener("click", () => {
      clearSession();
      updateAuthUI();
      showAuthFeedback("Sessão encerrada.", "secondary");
    });
  }

  updateAuthUI();
  await loadTasks();
});
