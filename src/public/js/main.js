import { initializeTaskPage, loadTasks } from "./tasks.js";

window.addEventListener("DOMContentLoaded", async () => {
  initializeTaskPage();
  await loadTasks();
});
