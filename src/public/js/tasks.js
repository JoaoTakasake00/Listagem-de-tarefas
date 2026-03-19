import { getTasks, createTaskAPI, deleteTaskAPI, updateTaskAPI } from "./api.js";

const STATUS_OPTIONS = ["pendente", "em andamento", "concluída"];

const state = {
  tasks: [],
  editingTaskId: null
};

function getStatusColor(status) {
  if (status === "pendente") {
    return "warning";
  }

  if (status === "em andamento") {
    return "primary";
  }

  return "success";
}

function showFeedback(message, type = "success") {
  const feedback = document.getElementById("feedback");

  if (!feedback) {
    return;
  }

  feedback.textContent = message;
  feedback.className = `alert alert-${type}`;
  feedback.classList.remove("d-none");
}

function clearFeedback() {
  const feedback = document.getElementById("feedback");

  if (!feedback) {
    return;
  }

  feedback.textContent = "";
  feedback.className = "alert d-none";
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function createStatusDropdown(taskId, status, mobile = false) {
  const statusColor = getStatusColor(status);
  const fullWidth = mobile ? "w-100" : "";
  const menuClass = mobile ? "dropdown-menu w-100" : "dropdown-menu";

  return `
    <div class="dropdown">
      <button class="btn text-white btn-${statusColor} btn-sm dropdown-toggle status-btn ${fullWidth}" data-bs-toggle="dropdown">
        ${escapeHtml(status)}
      </button>
      <ul class="${menuClass}">
        ${STATUS_OPTIONS.map(option => `
          <li>
            <button class="dropdown-item js-status" type="button" data-id="${taskId}" data-status="${option}">
              ${option.charAt(0).toUpperCase() + option.slice(1)}
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
      <td>${escapeHtml(task.title)}</td>
      <td class="description-cell">${escapeHtml(task.description || "")}</td>
      <td>${createStatusDropdown(task.id, task.status)}</td>
      <td>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 js-edit" data-id="${task.id}">
            Editar
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm w-100 js-delete" data-id="${task.id}">
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
      <td colspan="5">
        <div class="card mb-3 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
              <div>
                <h6 class="mb-1">${escapeHtml(task.title)}</h6>
                <p class="mb-0 text-muted small">${escapeHtml(task.description || "")}</p>
              </div>
              <small class="text-muted">#${task.id}</small>
            </div>
            <div class="mt-2">${createStatusDropdown(task.id, task.status, true)}</div>
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
        <td colspan="5" class="text-center py-4 text-muted">
          Nenhuma tarefa cadastrada.
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
    status: document.getElementById("status")?.value || "pendente"
  };
}

function validateFormData(data) {
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
}

function setEditingMode(task) {
  state.editingTaskId = task.id;
  document.getElementById("taskId").value = task.id;
  document.getElementById("title").value = task.title;
  document.getElementById("description").value = task.description || "";
  document.getElementById("status").value = task.status;
  document.getElementById("btnSubmit").textContent = "Atualizar";
}

export function resetForm() {
  state.editingTaskId = null;
  document.getElementById("taskForm")?.reset();
  document.getElementById("taskId").value = "";
  document.getElementById("status").value = "pendente";
  document.getElementById("btnSubmit").textContent = "Criar";
}

export async function loadTasks() {
  const tasks = await getTasks();
  state.tasks = tasks;
  renderTasks(tasks);
}

async function handleFormSubmit() {
  const payload = getFormData();
  validateFormData(payload);

  if (state.editingTaskId) {
    await updateTaskAPI(state.editingTaskId, payload);
    showFeedback("Tarefa atualizada com sucesso.");
  } else {
    await createTaskAPI(payload);
    showFeedback("Tarefa criada com sucesso.");
  }

  resetForm();
  await loadTasks();
}

async function handleDelete(id) {
  await deleteTaskAPI(id);
  showFeedback("Tarefa removida com sucesso.");
  await loadTasks();
}

async function handleStatusUpdate(id, status) {
  const task = state.tasks.find(item => Number(item.id) === Number(id));

  if (!task) {
    throw new Error("Tarefa não encontrada para atualização de status.");
  }

  await updateTaskAPI(id, {
    title: task.title,
    description: task.description || "",
    status
  });

  showFeedback("Status atualizado com sucesso.");
  await loadTasks();
}

function handleTableAction(event) {
  const editButton = event.target.closest(".js-edit");
  if (editButton) {
    const taskId = Number(editButton.dataset.id);
    const task = state.tasks.find(item => Number(item.id) === taskId);

    if (task) {
      setEditingMode(task);
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
  const table = document.getElementById("tasksTable");
  const btnCancel = document.getElementById("btnCancel");

  if (!form || !table || !btnCancel) {
    return;
  }

  form.addEventListener("submit", async event => {
    event.preventDefault();
    clearFeedback();

    try {
      await handleFormSubmit();
    } catch (error) {
      showFeedback(error.message, "danger");
    }
  });

  btnCancel.addEventListener("click", () => {
    resetForm();
    clearFeedback();
  });

  table.addEventListener("click", handleTableAction);
}
