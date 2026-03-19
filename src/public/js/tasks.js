import { getTasks, createTaskAPI, deleteTaskAPI, updateTaskAPI } from "./api.js";
import { isAuthenticated } from "./auth.js";
import { validateTaskFormData } from "./validators.js";

const STATUS_OPTIONS = ["pendente", "em andamento", "concluido"];

const state = {
  tasks: []
};
let feedbackTimeoutId = null;
let feedbackHideTimeoutId = null;

function normalizeDateInput(value) {
  if (!value) {
    return "";
  }

  return String(value).slice(0, 10);
}

function formatDate(value) {
  const normalized = normalizeDateInput(value);

  if (!normalized) {
    return "-";
  }

  const [year, month, day] = normalized.split("-");
  return `${day}/${month}/${year}`;
}

function updateDashboardStats(tasks) {
  const total = tasks.length;
  const pending = tasks.filter(task => task.status === "pendente").length;
  const inProgress = tasks.filter(task => task.status === "em andamento").length;
  const done = tasks.filter(task => task.status === "concluido").length;

  const totalCount = document.getElementById("totalCount");
  const pendingCount = document.getElementById("pendingCount");
  const doneCount = document.getElementById("doneCount");
  const activeCountBadge = document.getElementById("activeCountBadge");

  if (totalCount) {
    totalCount.textContent = String(total);
  }

  if (pendingCount) {
    pendingCount.textContent = String(pending);
  }

  if (doneCount) {
    doneCount.textContent = String(done);
  }

  if (activeCountBadge) {
    activeCountBadge.textContent = `${inProgress} em andamento`;
  }
}

function getStatusColor(status) {
  if (status === "pendente") {
    return "warning";
  }

  if (status === "em andamento") {
    return "primary";
  }

  return "success";
}

function getStatusLabel(status) {
  if (status === "pendente") {
    return "Pendente";
  }

  if (status === "em andamento") {
    return "Em andamento";
  }

  return "Concluído";
}

function showFeedback(message, type = "success") {
  const feedback = document.getElementById("feedback");

  if (!feedback) {
    return;
  }

  feedback.textContent = message;
  feedback.className = `alert alert-${type} feedback-alert`;
  feedback.classList.remove("feedback-hiding");
  feedback.classList.remove("d-none");
  requestAnimationFrame(() => {
    feedback.classList.add("feedback-visible");
  });

  if (feedbackTimeoutId) {
    clearTimeout(feedbackTimeoutId);
  }

  if (feedbackHideTimeoutId) {
    clearTimeout(feedbackHideTimeoutId);
  }

  feedbackTimeoutId = setTimeout(() => {
    clearFeedback();
  }, 3500);
}

function clearFeedback() {
  const feedback = document.getElementById("feedback");

  if (!feedback) {
    return;
  }

  feedback.classList.remove("feedback-visible");
  feedback.classList.add("feedback-hiding");

  if (feedbackHideTimeoutId) {
    clearTimeout(feedbackHideTimeoutId);
  }

  feedbackHideTimeoutId = setTimeout(() => {
    feedback.textContent = "";
    feedback.className = "alert d-none feedback-alert";
    feedbackHideTimeoutId = null;
  }, 350);

  if (feedbackTimeoutId) {
    clearTimeout(feedbackTimeoutId);
    feedbackTimeoutId = null;
  }
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function createStatusDropdown(taskId, status) {
  const statusColor = getStatusColor(status);

  return `
    <div class="dropdown">
      <button class="btn text-white btn-${statusColor} btn-sm dropdown-toggle status-btn" data-bs-toggle="dropdown">
        ${escapeHtml(getStatusLabel(status))}
      </button>
      <ul class="dropdown-menu">
        ${STATUS_OPTIONS.map(option => `
          <li>
            <button class="dropdown-item js-status" type="button" data-id="${taskId}" data-status="${option}">
              ${getStatusLabel(option)}
            </button>
          </li>
        `).join("")}
      </ul>
    </div>
  `;
}

function renderDesktopRow(task) {
  return `
    <tr class="d-none d-md-table-row align-middle">
      <td>${task.id}</td>
      <td class="fw-semibold">${escapeHtml(task.title)}</td>
      <td class="description-cell">${escapeHtml(task.description || "")}</td>
      <td>${createStatusDropdown(task.id, task.status)}</td>
      <td class="date-cell">${formatDate(task.created_at)}</td>
      <td>
        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-primary btn-sm js-edit" data-id="${task.id}">
            Editar
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm js-delete" data-id="${task.id}">
            Excluir
          </button>
        </div>
      </td>
    </tr>
  `;
}

function renderMobileRow(task) {
  return `
    <tr class="d-md-none">
      <td colspan="6">
        <div class="card mb-3 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
              <div>
                <h6 class="mb-1">${escapeHtml(task.title)}</h6>
                <p class="mb-0 text-muted small">${escapeHtml(task.description || "")}</p>
                <p class="mb-0 mt-2 text-muted small">Criação: ${formatDate(task.created_at)}</p>
              </div>
              <small class="text-muted">#${task.id}</small>
            </div>
            <div class="mt-2 d-flex justify-content-center">${createStatusDropdown(task.id, task.status)}</div>
            <div class="d-flex gap-2 mt-3">
              <button type="button" class="btn btn-outline-primary btn-sm w-100 js-edit" data-id="${task.id}">
                Editar
              </button>
              <button type="button" class="btn btn-outline-danger btn-sm w-100 js-delete" data-id="${task.id}">
                Excluir
              </button>
            </div>
          </div>
        </div>
      </td>
    </tr>
  `;
}

function renderTasks(tasks) {
  const table = document.getElementById("tasksTable");

  if (!table) {
    return;
  }

  if (tasks.length === 0) {
    table.innerHTML = `
      <tr>
        <td colspan="6" class="text-center py-5">
          <div class="d-flex flex-column align-items-center gap-2">
            <span class="fs-1">🗂️</span>
            <strong class="text-secondary">Nenhuma tarefa cadastrada</strong>
            <span class="text-muted small">Crie sua primeira tarefa no formulário ao lado.</span>
          </div>
        </td>
      </tr>
    `;
    return;
  }

  table.innerHTML = tasks
    .map(task => `${renderDesktopRow(task)}${renderMobileRow(task)}`)
    .join("");
}

function getFormData() {
  return {
    title: document.getElementById("title")?.value.trim() || "",
    description: document.getElementById("description")?.value.trim() || "",
    status: document.getElementById("status")?.value || "pendente",
    created_at: normalizeDateInput(document.getElementById("createdAt")?.value || "")
  };
}

function getEditFormData() {
  return {
    id: Number(document.getElementById("editTaskId")?.value || 0),
    title: document.getElementById("editTitle")?.value.trim() || "",
    description: document.getElementById("editDescription")?.value.trim() || "",
    status: document.getElementById("editStatus")?.value || "pendente",
    created_at: normalizeDateInput(document.getElementById("editCreatedAt")?.value || "")
  };
}

function ensureAuthenticated() {
  if (!isAuthenticated()) {
    throw new Error("Faça login para executar esta ação.");
  }
}

function openEditModal(task, modalInstance) {
  const createdAt = normalizeDateInput(task.created_at) || normalizeDateInput(new Date().toISOString());

  document.getElementById("editTaskId").value = task.id;
  document.getElementById("editTitle").value = task.title;
  document.getElementById("editDescription").value = task.description || "";
  document.getElementById("editStatus").value = task.status;
  document.getElementById("editCreatedAt").value = createdAt;
  modalInstance.show();
}

export function resetForm() {
  document.getElementById("taskForm")?.reset();
  document.getElementById("status").value = "pendente";
  document.getElementById("createdAt").value = normalizeDateInput(new Date().toISOString());
}

export async function loadTasks() {
  const tasks = await getTasks();
  state.tasks = tasks;
  updateDashboardStats(tasks);
  renderTasks(tasks);
}

async function handleFormSubmit() {
  ensureAuthenticated();
  const payload = getFormData();
  validateTaskFormData(payload);
  await createTaskAPI(payload);
  showFeedback("Tarefa criada com sucesso.");

  resetForm();
  await loadTasks();
}

async function handleEditSubmit(modalInstance) {
  ensureAuthenticated();
  const payload = getEditFormData();
  validateTaskFormData(payload);

  if (!payload.id) {
    throw new Error("Tarefa inválida para edição.");
  }

  await updateTaskAPI(payload.id, {
    title: payload.title,
    description: payload.description,
    status: payload.status,
    created_at: payload.created_at
  });

  modalInstance.hide();
  showFeedback("Tarefa atualizada com sucesso.");
  await loadTasks();
}

async function handleDelete(id) {
  ensureAuthenticated();
  await deleteTaskAPI(id);
  showFeedback("Tarefa removida com sucesso.");
  await loadTasks();
}

async function handleStatusUpdate(id, status) {
  ensureAuthenticated();
  const task = state.tasks.find(item => Number(item.id) === Number(id));

  if (!task) {
    throw new Error("Tarefa não encontrada para atualização de status.");
  }

  const createdAt = normalizeDateInput(task.created_at) || normalizeDateInput(new Date().toISOString());

  await updateTaskAPI(id, {
    title: task.title,
    description: task.description || "",
    status,
    created_at: createdAt
  });

  showFeedback("Status atualizado com sucesso.");
  await loadTasks();
}

function handleTableAction(event, modalInstance) {
  const editButton = event.target.closest(".js-edit");
  if (editButton) {
    if (!isAuthenticated()) {
      showFeedback("Faça login para editar tarefas.", "danger");
      return;
    }

    const taskId = Number(editButton.dataset.id);
    const task = state.tasks.find(item => Number(item.id) === taskId);

    if (task) {
      openEditModal(task, modalInstance);
      clearFeedback();
    }

    return;
  }

  const deleteButton = event.target.closest(".js-delete");
  if (deleteButton) {
    const taskId = Number(deleteButton.dataset.id);
    handleDelete(taskId).catch(error => {
      showFeedback(error.message, "danger");
    });
    return;
  }

  const statusButton = event.target.closest(".js-status");
  if (statusButton) {
    const taskId = Number(statusButton.dataset.id);
    const status = statusButton.dataset.status;
    handleStatusUpdate(taskId, status).catch(error => {
      showFeedback(error.message, "danger");
    });
  }
}

export function initializeTaskPage() {
  const form = document.getElementById("taskForm");
  const editForm = document.getElementById("editTaskForm");
  const table = document.getElementById("tasksTable");
  const btnCancel = document.getElementById("btnCancel");
  const modalElement = document.getElementById("editTaskModal");

  if (!form || !editForm || !table || !btnCancel || !modalElement || !window.bootstrap?.Modal) {
    return;
  }

  const modalInstance = new window.bootstrap.Modal(modalElement);
  resetForm();

  form.addEventListener("submit", async event => {
    event.preventDefault();
    clearFeedback();

    try {
      await handleFormSubmit();
    } catch (error) {
      showFeedback(error.message, "danger");
    }
  });

  editForm.addEventListener("submit", async event => {
    event.preventDefault();
    clearFeedback();

    try {
      await handleEditSubmit(modalInstance);
    } catch (error) {
      showFeedback(error.message, "danger");
    }
  });

  btnCancel.addEventListener("click", () => {
    resetForm();
    clearFeedback();
  });

  table.addEventListener("click", event => {
    handleTableAction(event, modalInstance);
  });
}
