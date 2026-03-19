CREATE TABLE IF NOT EXISTS tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('pendente', 'em andamento', 'concluido') NOT NULL DEFAULT 'pendente',
  created_at DATE NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE tasks
  MODIFY status ENUM('pendente', 'em andamento', 'concluída', 'concluido') NOT NULL DEFAULT 'pendente';

UPDATE tasks
SET status = 'concluido'
WHERE status IN ('concluída', 'concluida', 'concluído');

ALTER TABLE tasks
  MODIFY status ENUM('pendente', 'em andamento', 'concluido') NOT NULL DEFAULT 'pendente';
