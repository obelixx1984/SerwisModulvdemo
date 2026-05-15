<?php
// ============================================================
// app/Models/Models.php — wszystkie modele
// ============================================================
namespace App\Models;

use App\Helpers\Database;

// ────────────────────────────────────────────────────────────
abstract class BaseModel
{
    protected \PDO $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::get();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $r = $st->fetch();
        return $r ?: null;
    }

    protected function execute(string $sql, array $params = []): int
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return (int) $this->db->lastInsertId();
    }
}

// ────────────────────────────────────────────────────────────
class UserModel extends BaseModel
{
    public function findByNickname(string $nickname): ?array
    {
        return $this->fetchOne(
            "SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.login = ? AND u.is_active = 1",
            [strtolower($nickname)]
        );
    }

    public function changePassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->execute(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [$hash, $userId]
        );
    }

    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT u.*, r.name AS role_name, r.label AS role_label
             FROM users u JOIN roles r ON r.id = u.role_id
             ORDER BY r.id, u.name"
        );
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO users (role_id, name, login, email, password_hash, is_active)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $d['role_id'],
                $d['name'],
                strtolower($d['nickname']),
                $d['email'],
                password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 10]),
                $d['is_active']
            ]
        );
    }

    public function update(int $id, array $d): void
    {
        $sets   = ['name = ?', 'login = ?', 'email = ?', 'role_id = ?', 'is_active = ?'];
        $params = [$d['name'], strtolower($d['nickname']), $d['email'], $d['role_id'], $d['is_active']];
        if (!empty($d['password'])) {
            $sets[]   = 'password_hash = ?';
            $params[] = password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $params[] = $id;
        $this->execute("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?", $params);
    }

    public function updateLastLogin(int $id): void
    {
        $this->execute("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$id]);
    }

    public function nicknameExists(string $nickname, int $excludeId = 0): bool
    {
        return (bool)$this->fetchOne(
            "SELECT id FROM users WHERE login = ? AND id != ?",
            [strtolower($nickname), $excludeId]
        );
    }

    public function delete(int $id): void
    {
        // Znajdź innego aktywnego użytkownika do przejęcia raportów DUR
        // (pierwszego admina innego niż usuwany, lub pierwszego dowolnego innego)
        $fallback = $this->fetchOne(
            "SELECT id FROM users WHERE id != ? AND is_active = 1 AND role_id = 1 LIMIT 1",
            [$id]
        );
        if (!$fallback) {
            $fallback = $this->fetchOne(
                "SELECT id FROM users WHERE id != ? AND is_active = 1 LIMIT 1",
                [$id]
            );
        }
        $fallbackId = $fallback ? $fallback['id'] : null;

        // Przenieś raporty DUR na innego użytkownika (lub zostaw jeśli brak innych)
        if ($fallbackId) {
            $this->execute(
                "UPDATE maintenance_reviews SET performed_by = ? WHERE performed_by = ?",
                [$fallbackId, $id]
            );
        }

        // Wyzeruj user_id w komentarzach i historii (FK ustawione ON DELETE SET NULL)
        $this->execute("UPDATE failure_comments SET user_id = NULL WHERE user_id = ?", [$id]);
        $this->execute("UPDATE failure_history  SET user_id = NULL WHERE user_id = ?", [$id]);

        // Usuń użytkownika
        $this->execute("DELETE FROM users WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
class EmployeeModel extends BaseModel
{
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->fetchAll("SELECT * FROM employees $where ORDER BY name");
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO employees (acronym, name, position, is_active) VALUES (?, ?, ?, ?)",
            [strtoupper($d['acronym']), $d['name'], $d['position'] ?? null, $d['is_active'] ?? 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE employees SET acronym = ?, name = ?, position = ?, is_active = ? WHERE id = ?",
            [strtoupper($d['acronym']), $d['name'], $d['position'] ?? null, $d['is_active'], $id]
        );
    }

    public function findByAcronym(string $ak): ?array
    {
        return $this->fetchOne("SELECT * FROM employees WHERE acronym = ?", [strtoupper($ak)]);
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM employees WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
class ProductionLineModel extends BaseModel
{
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE pl.is_active = 1' : '';
        return $this->fetchAll(
            "SELECT pl.*,
                    GROUP_CONCAT(ls.name ORDER BY ls.sort_order SEPARATOR '|||') AS subsystems_str,
                    GROUP_CONCAT(ls.id   ORDER BY ls.sort_order SEPARATOR ',')   AS subsystem_ids
             FROM production_lines pl
             LEFT JOIN line_subsystems ls ON ls.production_line_id = pl.id AND ls.is_active = 1
             $where
             GROUP BY pl.id
             ORDER BY pl.name"
        );
    }

    public function getById(int $id): ?array
    {
        $line = $this->fetchOne("SELECT * FROM production_lines WHERE id = ?", [$id]);
        if (!$line) return null;
        $line['subsystems'] = $this->fetchAll(
            "SELECT * FROM line_subsystems WHERE production_line_id = ? AND is_active = 1 ORDER BY sort_order",
            [$id]
        );
        return $line;
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO production_lines (name, prefix, description, is_active) VALUES (?, ?, ?, ?)",
            [$d['name'], strtoupper($d['prefix']), $d['description'] ?? null, $d['is_active'] ?? 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE production_lines SET name = ?, prefix = ?, description = ?, is_active = ? WHERE id = ?",
            [$d['name'], strtoupper($d['prefix']), $d['description'] ?? null, $d['is_active'], $id]
        );
    }

    public function addSubsystem(int $lineId, string $name, int $order = 0): int
    {
        return $this->execute(
            "INSERT INTO line_subsystems (production_line_id, name, sort_order) VALUES (?, ?, ?)",
            [$lineId, $name, $order]
        );
    }

    public function deleteSubsystemsForLine(int $lineId): void
    {
        $this->execute("DELETE FROM line_subsystems WHERE production_line_id = ?", [$lineId]);
    }

    public function prefixExists(string $prefix, int $excludeId = 0): bool
    {
        $row = $this->fetchOne(
            "SELECT id FROM production_lines WHERE prefix = ? AND id != ?",
            [strtoupper($prefix), $excludeId]
        );
        return (bool) $row;
    }

    public function countFailures(int $id): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM failures WHERE production_line_id = ?");
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM production_lines WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
class CategoryModel extends BaseModel
{
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->fetchAll("SELECT * FROM failure_categories $where ORDER BY sort_order, id");
    }

    public function create(array $d): int
    {
        $name = strtolower(preg_replace('/\s+/', '_', trim($d['label']))) . '_' . time();
        return $this->execute(
            "INSERT INTO failure_categories (name, label, color, sort_order, is_active) VALUES (?, ?, ?, ?, ?)",
            [$name, $d['label'], $d['color'] ?? '#6c757d', $d['sort_order'] ?? 0, 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failure_categories SET label = ?, color = ?, sort_order = ?, is_active = ? WHERE id = ?",
            [$d['label'], $d['color'], $d['sort_order'] ?? 0, $d['is_active'], $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM failure_categories WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
class DictionaryModel extends BaseModel
{
    public function getActive(?int $categoryId = null): array
    {
        $where  = 'WHERE fd.is_active = 1';
        $params = [];
        if ($categoryId) {
            $where .= ' AND fd.category_id = ?';
            $params[] = $categoryId;
        }
        return $this->fetchAll(
            "SELECT fd.*, fc.label AS cat_label, fc.color AS cat_color
             FROM failure_dictionary fd
             JOIN failure_categories fc ON fc.id = fd.category_id
             $where
             ORDER BY fc.sort_order, fd.title",
            $params
        );
    }

    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT fd.*, fc.label AS cat_label, fc.color AS cat_color
             FROM failure_dictionary fd
             JOIN failure_categories fc ON fc.id = fd.category_id
             ORDER BY fc.sort_order, fd.title"
        );
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO failure_dictionary (category_id, title, description, is_active) VALUES (?, ?, ?, ?)",
            [$d['category_id'], $d['title'], $d['description'] ?? null, 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failure_dictionary SET title = ?, category_id = ?, description = ?, is_active = ? WHERE id = ?",
            [$d['title'], $d['category_id'], $d['description'] ?? null, $d['is_active'] ?? 1, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM failure_dictionary WHERE id = ?", [$id]);
    }

    public function countUsages(int $id): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM failures WHERE dictionary_item_id = ?");
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }
}

// ────────────────────────────────────────────────────────────
// Zmiana 1: model objawów awarii — wybieranych przez zgłaszającego
// ────────────────────────────────────────────────────────────
class SymptomModel extends BaseModel
{
    public function getActive(): array
    {
        return $this->fetchAll(
            "SELECT * FROM failure_symptoms WHERE is_active = 1 ORDER BY sort_order, name"
        );
    }

    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT * FROM failure_symptoms ORDER BY sort_order, name"
        );
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO failure_symptoms (name, sort_order, is_active) VALUES (?, ?, ?)",
            [trim($d['name']), (int)($d['sort_order'] ?? 0), 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failure_symptoms SET name = ?, sort_order = ?, is_active = ? WHERE id = ?",
            [trim($d['name']), (int)($d['sort_order'] ?? 0), (int)$d['is_active'], $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM failure_symptoms WHERE id = ?", [$id]);
    }

    public function countUsages(int $id): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM failures WHERE symptom_id = ?");
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }
}

// ────────────────────────────────────────────────────────────
class StatusModel extends BaseModel
{
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->fetchAll("SELECT * FROM failure_statuses $where ORDER BY sort_order");
    }

    public function getInitial(): ?array
    {
        return $this->fetchOne("SELECT * FROM failure_statuses WHERE is_initial = 1 AND is_active = 1 LIMIT 1");
    }

    public function getFinal(): array
    {
        return $this->fetchAll("SELECT * FROM failure_statuses WHERE is_final = 1 AND is_active = 1");
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne("SELECT * FROM failure_statuses WHERE id = ?", [$id]);
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO failure_statuses (label, color, sort_order, is_initial, is_final, is_active) VALUES (?, ?, ?, ?, ?, ?)",
            [$d['label'], $d['color'] ?? '#6c757d', $d['sort_order'] ?? 99, 0, 0, 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failure_statuses SET label = ?, color = ?, sort_order = ?, is_initial = ?, is_final = ?, is_active = ? WHERE id = ?",
            [
                $d['label'],
                $d['color'],
                $d['sort_order'],
                $d['is_initial'] ?? 0,
                $d['is_final'] ?? 0,
                $d['is_active'],
                $id
            ]
        );
    }

    public function countUsages(int $id): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM failures WHERE status_id = ?");
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM failure_statuses WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
class FailureModel extends BaseModel
{
    // Zmiana 1: LEFT JOIN failure_categories (category_id jest teraz NULL dla nowych zgłoszeń)
    // Zmiana 1: LEFT JOIN failure_symptoms (nowa tabela objawów)
    private function baseSelect(): string
    {
        return "SELECT f.*,
            pl.name AS line_name, pl.prefix AS line_prefix,
            ls.name AS subsystem_name,
            fc.label AS cat_label, fc.color AS cat_color,
            fs.label AS status_label, fs.color AS status_color, fs.is_final AS status_is_final,
            fd.title AS dict_title,
            fsym.name AS symptom_name
         FROM failures f
         JOIN production_lines pl ON pl.id = f.production_line_id
         LEFT JOIN line_subsystems ls ON ls.id = f.subsystem_id
         LEFT JOIN failure_categories fc ON fc.id = f.category_id
         JOIN failure_statuses fs ON fs.id = f.status_id
         LEFT JOIN failure_dictionary fd ON fd.id = f.dictionary_item_id
         LEFT JOIN failure_symptoms fsym ON fsym.id = f.symptom_id";
    }

    public function getList(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['status_id'])) {
            $where[] = 'f.status_id = ?';
            $params[] = $filters['status_id'];
        }
        if (!empty($filters['line_id'])) {
            $where[] = 'f.production_line_id = ?';
            $params[] = $filters['line_id'];
        }
        if (!empty($filters['category_id'])) {
            if ($filters['category_id'] === 'none') {
                $where[] = 'f.category_id IS NULL';
            } else {
                $where[] = 'f.category_id = ?';
                $params[] = $filters['category_id'];
            }
        }
        if (!empty($filters['search'])) {
            $where[] = '(f.ticket_number LIKE ? OR f.description LIKE ? OR fsym.name LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        if (!empty($filters['reporter_user_id'])) {
            $where[]  = 'f.reporter_user_id = ?';
            $params[] = (int)$filters['reporter_user_id'];
        }
        $sql = $this->baseSelect()
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY f.created_at DESC'
            . " LIMIT $limit OFFSET $offset";
        return $this->fetchAll($sql, $params);
    }

    public function countList(array $filters = []): int
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['status_id'])) {
            $where[] = 'f.status_id = ?';
            $params[] = $filters['status_id'];
        }
        if (!empty($filters['line_id'])) {
            $where[] = 'f.production_line_id = ?';
            $params[] = $filters['line_id'];
        }
        if (!empty($filters['category_id'])) {
            if ($filters['category_id'] === 'none') {
                $where[] = 'f.category_id IS NULL';
            } else {
                $where[] = 'f.category_id = ?';
                $params[] = $filters['category_id'];
            }
        }
        if (!empty($filters['search'])) {
            $where[] = '(f.ticket_number LIKE ? OR f.description LIKE ? OR fsym.name LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        if (!empty($filters['reporter_user_id'])) {
            $where[]  = 'f.reporter_user_id = ?';
            $params[] = (int)$filters['reporter_user_id'];
        }
        // Zmiana 1: LEFT JOIN failure_symptoms potrzebny też w COUNT gdy filtrujemy po symptom_name
        $st = $this->db->prepare(
            "SELECT COUNT(*) FROM failures f
             LEFT JOIN failure_symptoms fsym ON fsym.id = f.symptom_id
             WHERE " . implode(' AND ', $where)
        );
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne($this->baseSelect() . ' WHERE f.id = ?', [$id]);
    }

    public function getByTicket(string $ticket): ?array
    {
        return $this->fetchOne($this->baseSelect() . ' WHERE f.ticket_number = ?', [$ticket]);
    }

    // Zmiana 1: symptom_id zamiast category_id + dictionary_item_id od zgłaszającego
    // category_id i dictionary_item_id ustawia mechanik (Zmiana 2)
    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO failures
            (ticket_number, production_line_id, subsystem_id, symptom_id, other_symptom, category_id, status_id,
            dictionary_item_id, reporter_acronym, reporter_name, reporter_user_id, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $d['ticket_number'],
                $d['production_line_id'],
                $d['subsystem_id'] ?? null,
                $d['symptom_id'] ?? null,
                $d['other_symptom'] ?? 0,      // ← NOWE
                $d['category_id'] ?? null,
                $d['status_id'],
                $d['dictionary_item_id'] ?? null,
                $d['reporter_acronym'] ?? null,
                $d['reporter_name'] ?? null,
                $d['reporter_user_id'] ?? null,
                $d['description'] ?? null,
            ]
        );
    }

    public function getByReporterUserId(int $userId, string $reporterName = ''): array
    {
        if ($userId > 0) {
            // Właściwe filtrowanie po user_id (po migracji)
            return $this->fetchAll(
                $this->baseSelect() .
                    " WHERE f.reporter_user_id = ?
                  ORDER BY f.created_at DESC
                  LIMIT 200",
                [$userId]
            );
        }
        // Fallback: filtrowanie po imieniu i nazwisku (dla starych rekordów)
        return $this->fetchAll(
            $this->baseSelect() .
                " WHERE f.reporter_name = ?
              ORDER BY f.created_at DESC
              LIMIT 200",
            [$reporterName]
        );
    }

    public function changeStatus(int $id, int $newStatusId, bool $isFinal): void
    {
        $closed = $isFinal ? 'closed_at = NOW(),' : '';
        $this->execute(
            "UPDATE failures SET status_id = ?, $closed updated_at = NOW() WHERE id = ?",
            [$newStatusId, $id]
        );
    }

    /**
     * Aktualizuje objaw awarii (symptom_id) zgłoszenia.
     * Używane przez użytkownika z modala edycji w "Moje zgłoszenia".
     * Nowa metoda — Poprawka błąd 1.
     */
    public function updateSymptom(int $id, ?int $symptomId, int $otherSymptom = 0, ?string $description = null): void
    {
        $this->execute(
            "UPDATE failures
             SET symptom_id = ?, other_symptom = ?, description = ?, updated_at = NOW()
             WHERE id = ?",
            [$symptomId, $otherSymptom, $description, $id]
        );
    }

    // Zmiana 2: mechanik ustawia kategorie i usterkę
    public function setCategory(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failures
             SET category_id = ?, dictionary_item_id = ?, other_failure = ?, mechanic_note = ?,
                 updated_at = NOW()
             WHERE id = ?",
            [
                $d['category_id'] ?: null,
                $d['dictionary_item_id'] ?: null,
                $d['other_failure'] ? 1 : 0,
                $d['mechanic_note'] ?: null,
                $id
            ]
        );
    }

    public function addHistory(
        int $failureId,
        ?int $userId,
        string $action,
        ?int $oldStatusId,
        ?int $newStatusId,
        string $actorName,
        ?string $note = null
    ): void {
        $this->execute(
            "INSERT INTO failure_history
             (failure_id, user_id, actor_name, action, old_status_id, new_status_id, note)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$failureId, $userId, $actorName, $action, $oldStatusId, $newStatusId, $note]
        );
    }

    public function getHistory(int $failureId): array
    {
        return $this->fetchAll(
            "SELECT fh.*,
                fs_old.label AS old_status_label, fs_old.color AS old_status_color,
                fs_new.label AS new_status_label, fs_new.color AS new_status_color
             FROM failure_history fh
             LEFT JOIN failure_statuses fs_old ON fs_old.id = fh.old_status_id
             LEFT JOIN failure_statuses fs_new ON fs_new.id = fh.new_status_id
             WHERE fh.failure_id = ?
             ORDER BY fh.created_at ASC",
            [$failureId]
        );
    }

    public function getComments(int $failureId): array
    {
        return $this->fetchAll(
            "SELECT * FROM failure_comments WHERE failure_id = ? ORDER BY created_at ASC",
            [$failureId]
        );
    }

    public function addComment(int $failureId, ?int $userId, string $author, string $comment): int
    {
        return $this->execute(
            "INSERT INTO failure_comments (failure_id, user_id, author, comment) VALUES (?, ?, ?, ?)",
            [$failureId, $userId, $author, $comment]
        );
    }

    /** Historia linii do widoku publicznego i historii linii */
    public function getLineHistory(int $lineId, int $days = 30, int $limit = 9999, int $offset = 0): array
    {
        return $this->fetchAll(
            $this->baseSelect() . "
             WHERE f.production_line_id = ?
               AND f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             ORDER BY f.created_at DESC
             LIMIT $limit OFFSET $offset",
            [$lineId, $days]
        );
    }

    /**
     * Zlicza zgłoszenia dla linii w danym przedziale czasowym.
     * Używane do paginacji na stronie Historia linii.
     */
    public function countLineHistory(int $lineId, int $days = 30): int
    {
        $st = $this->db->prepare(
            "SELECT COUNT(*)
             FROM failures f
             WHERE f.production_line_id = ?
               AND f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $st->execute([$lineId, $days]);
        return (int) $st->fetchColumn();
    }

    /** Statystyki linii — POPRAWKA 9: zwraca dane do obliczenia śr. czasu naprawy */
    public function getLineStats(int $lineId, int $days = 30): array
    {
        $empty = [
            'total' => 0,
            'open_count' => 0,
            'closed_count' => 0,
            'avg_repair_minutes' => null,
            'avg_repair_str' => '—'
        ];

        $row = $this->fetchOne(
            "SELECT
               COUNT(*) AS total,
               SUM(CASE WHEN fs.is_final = 0 THEN 1 ELSE 0 END) AS open_count,
               SUM(CASE WHEN fs.is_final = 1 THEN 1 ELSE 0 END) AS closed_count,
               AVG(CASE WHEN f.closed_at IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, f.created_at, f.closed_at)
                   END) AS avg_repair_minutes
             FROM failures f
             JOIN failure_statuses fs ON fs.id = f.status_id
             WHERE f.production_line_id = ?
               AND f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$lineId, $days]
        );

        if (!is_array($row)) {
            return $empty;
        }

        $row['avg_repair_str'] = '—';
        if (!empty($row['avg_repair_minutes'])) {
            $min = (float) $row['avg_repair_minutes'];
            if ($min < 60)       $row['avg_repair_str'] = round($min) . ' min';
            elseif ($min < 1440) $row['avg_repair_str'] = round($min / 60, 1) . 'h';
            else                 $row['avg_repair_str'] = round($min / 1440, 1) . ' dni';
        }
        return $row;
    }

    /** Usuń zgłoszenie wraz z historią i komentarzami (kaskadowo przez FK) */
    public function deleteFailure(int $id): void
    {
        $this->execute("DELETE FROM failures WHERE id = ?", [$id]);
    }

    // Zmiana 1+5: sprawdza duplikat po symptom_id (nie dictionary_item_id)
    public function findOpenDuplicate(int $lineId, int $symptomId): ?array
    {
        return $this->fetchOne(
            "SELECT f.ticket_number FROM failures f
             JOIN failure_statuses fs ON fs.id = f.status_id
             WHERE f.production_line_id = ? AND f.symptom_id = ? AND fs.is_final = 0
             LIMIT 1",
            [$lineId, $symptomId]
        );
    }

    /** ZMIANA 3: Zlicz awarie zgłoszone w bieżącym miesiącu i roku */
    public function getMonthlyFailureCount(): int
    {
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM failures
             WHERE YEAR(created_at)  = YEAR(NOW())
               AND MONTH(created_at) = MONTH(NOW())"
        );
        return (int)($row['cnt'] ?? 0);
    }

    /** Globalny średni czas naprawy (wszystkie linie, zamknięte zgłoszenia) */
    public function getGlobalAvgRepairTime(): string
    {
        $row = $this->fetchOne(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, closed_at)) AS avg_min
             FROM failures
             WHERE closed_at IS NOT NULL AND created_at IS NOT NULL"
        );
        if (!$row || $row['avg_min'] === null) return '—';
        $min = (float) $row['avg_min'];
        if ($min < 60)       return round($min) . ' min';
        if ($min < 1440)     return round($min / 60, 1) . 'h';
        return round($min / 1440, 1) . ' dni';
    }

    /** Dashboard: statystyki ogólne */
    public function getDashboardStats(): array
    {
        return $this->fetchOne(
            "SELECT
               SUM(CASE WHEN fs.is_initial = 1 THEN 1 ELSE 0 END)        AS new_count,
               SUM(CASE WHEN fs.label = 'W trakcie naprawy' THEN 1 ELSE 0 END) AS progress_count,
               SUM(CASE WHEN fs.is_final = 0 THEN 1 ELSE 0 END)          AS open_count,
               SUM(CASE WHEN fs.is_final = 1 AND DATE(f.updated_at) = CURDATE() THEN 1 ELSE 0 END) AS closed_today
             FROM failures f JOIN failure_statuses fs ON fs.id = f.status_id"
        ) ?? [];
    }
}

// ────────────────────────────────────────────────────────────
class MaintenanceModel extends BaseModel
{
    public function getAllReviews(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['line_id'])) {
            $where[] = 'mr.production_line_id = ?';
            $params[] = $filters['line_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'mr.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'mr.review_type = ?';
            $params[] = $filters['type'];
        }

        return $this->fetchAll(
            "SELECT mr.*,
            pl.name AS line_name,
            ls.name AS subsystem_name,
            u.name  AS performer_name, u.login AS performer_nick
            FROM maintenance_reviews mr
            JOIN production_lines pl ON pl.id = mr.production_line_id
            LEFT JOIN line_subsystems ls ON ls.id = mr.subsystem_id
            JOIN users u ON u.id = mr.performed_by
            WHERE " . implode(' AND ', $where) . "
            ORDER BY mr.review_date DESC, mr.id DESC
            LIMIT $limit OFFSET $offset",
            $params
        );
    }

    /**
     * Zlicza wszystkie raporty DUR spełniające filtry.
     * Używane do paginacji na stronie /route=dur.
     */
    public function countAllReviews(array $filters = []): int
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['line_id'])) {
            $where[] = 'mr.production_line_id = ?';
            $params[] = $filters['line_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'mr.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'mr.review_type = ?';
            $params[] = $filters['type'];
        }
        $st = $this->db->prepare(
            "SELECT COUNT(*)
             FROM maintenance_reviews mr
             WHERE " . implode(' AND ', $where)
        );
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    public function findScheduleByLineAndType(int $lineId, string $reviewType): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM maintenance_schedules
             WHERE production_line_id = ?
               AND review_type = ?
               AND is_active = 1
             ORDER BY next_due_date ASC
             LIMIT 1",
            [$lineId, $reviewType]
        );
    }

    public function updateScheduleNextDate(int $id, string $nextDate): void
    {
        $this->execute(
            "UPDATE maintenance_schedules SET next_due_date = ?, updated_at = NOW() WHERE id = ?",
            [$nextDate, $id]
        );
    }

    public function getReviewsByLine(int $lineId, int $limit = 5): array
    {
        return $this->fetchAll(
            "SELECT mr.*, pl.name AS line_name, ls.name AS subsystem_name,
                    u.name AS performer_name
             FROM maintenance_reviews mr
             JOIN production_lines pl ON pl.id = mr.production_line_id
             LEFT JOIN line_subsystems ls ON ls.id = mr.subsystem_id
             JOIN users u ON u.id = mr.performed_by
             WHERE mr.production_line_id = ?
             ORDER BY mr.review_date DESC LIMIT ?",
            [$lineId, $limit]
        );
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT mr.*, pl.name AS line_name, ls.name AS subsystem_name,
                    u.name AS performer_name, mt.name AS template_name
             FROM maintenance_reviews mr
             JOIN production_lines pl ON pl.id = mr.production_line_id
             LEFT JOIN line_subsystems ls ON ls.id = mr.subsystem_id
             JOIN users u ON u.id = mr.performed_by
             LEFT JOIN maintenance_templates mt ON mt.id = mr.template_id
             WHERE mr.id = ?",
            [$id]
        );
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO maintenance_reviews
             (production_line_id, subsystem_id, template_id, schedule_id, performed_by,
              review_type, review_date, duration_minutes, activities, parts_used, notes,
              status, next_review_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $d['production_line_id'],
                $d['subsystem_id'] ?? null,
                $d['template_id'] ?? null,
                $d['schedule_id'] ?? null,
                $d['performed_by'],
                $d['review_type'],
                $d['review_date'],
                $d['duration_minutes'] ?? null,
                $d['activities'],
                $d['parts_used'] ?? null,
                $d['notes'] ?? null,
                $d['status'] ?? 'completed',
                $d['next_review_date'] ?? null,
            ]
        );
    }

    /**
     * Aktualizuje istniejący raport DUR.
     * Używane przez DurController::editPost().
     */
    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE maintenance_reviews
             SET review_date      = ?,
                 duration_minutes = ?,
                 activities       = ?,
                 parts_used       = ?,
                 notes            = ?,
                 status           = ?,
                 next_review_date = ?,
                 updated_at       = NOW()
             WHERE id = ?",
            [
                $d['review_date'],
                $d['duration_minutes'] ?? null,
                $d['activities'],
                $d['parts_used'] ?? null,
                $d['notes'] ?? null,
                $d['status'] ?? 'completed',
                $d['next_review_date'] ?? null,
                $id,
            ]
        );
    }

    public function getUpcomingSchedules(int $days = 14): array
    {
        return $this->fetchAll(
            "SELECT ms.*, pl.name AS line_name,
                    DATEDIFF(ms.next_due_date, CURDATE()) AS days_left
             FROM maintenance_schedules ms
             JOIN production_lines pl ON pl.id = ms.production_line_id
             WHERE ms.is_active = 1
               AND ms.next_due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND NOT EXISTS (
                   SELECT 1 FROM maintenance_reviews mr
                   WHERE mr.production_line_id = ms.production_line_id
                     AND mr.review_type        = ms.review_type
                     AND mr.review_date >= DATE_SUB(ms.next_due_date, INTERVAL ms.interval_days DAY)
               )
             ORDER BY ms.next_due_date ASC",
            [$days]
        );
    }

    public function getTemplates(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->fetchAll("SELECT * FROM maintenance_templates $where ORDER BY name");
    }

    public function getSchedules(): array
    {
        return $this->fetchAll(
            "SELECT ms.*, pl.name AS line_name, mt.name AS template_name,
                    DATEDIFF(ms.next_due_date, CURDATE()) AS days_left
             FROM maintenance_schedules ms
             JOIN production_lines pl ON pl.id = ms.production_line_id
             JOIN maintenance_templates mt ON mt.id = ms.template_id
             WHERE ms.is_active = 1
             ORDER BY ms.next_due_date ASC"
        );
    }

    public function createTemplate(array $d): int
    {
        return $this->execute(
            "INSERT INTO maintenance_templates
             (name, review_type, checklist, is_active, created_by)
             VALUES (?, ?, ?, ?, ?)",
            [
                $d['name'],
                $d['review_type'],
                $d['checklist'] ?? null,
                $d['is_active'] ?? 1,
                $d['created_by'] ?? null
            ]
        );
    }

    public function updateTemplate(int $id, array $d): void
    {
        $this->execute(
            "UPDATE maintenance_templates
             SET name=?, review_type=?, checklist=?, is_active=?
             WHERE id=?",
            [
                $d['name'],
                $d['review_type'],
                $d['checklist'] ?? null,
                $d['is_active'] ?? 1,
                $id
            ]
        );
    }

    public function createSchedule(array $d): int
    {
        return $this->execute(
            "INSERT INTO maintenance_schedules
             (production_line_id, template_id, review_type, interval_days, next_due_date, is_active)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $d['production_line_id'],
                $d['template_id'],
                $d['review_type'],
                $d['interval_days'],
                $d['next_due_date'],
                $d['is_active'] ?? 1
            ]
        );
    }

    public function updateSchedule(int $id, array $d): void
    {
        $this->execute(
            "UPDATE maintenance_schedules
             SET production_line_id=?, template_id=?, review_type=?,
                 interval_days=?, next_due_date=?, is_active=?
             WHERE id=?",
            [
                $d['production_line_id'],
                $d['template_id'],
                $d['review_type'],
                $d['interval_days'],
                $d['next_due_date'],
                $d['is_active'] ?? 1,
                $id
            ]
        );
    }

    public function deleteTemplate(int $id): void
    {
        $this->execute("DELETE FROM maintenance_templates WHERE id = ?", [$id]);
    }

    public function deleteSchedule(int $id): void
    {
        $this->execute("DELETE FROM maintenance_schedules WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
class SettingsModel extends BaseModel
{
    public function getAll(): array
    {
        $rows = $this->fetchAll("SELECT * FROM settings ORDER BY id");
        $out  = [];
        foreach ($rows as $r) $out[$r['skey']] = $r;
        return $out;
    }

    public function get(string $key): ?string
    {
        $r = $this->fetchOne("SELECT svalue FROM settings WHERE skey = ?", [$key]);
        return $r ? $r['svalue'] : null;
    }

    public function set(string $key, string $value): void
    {
        $this->execute(
            "INSERT INTO settings (skey, svalue, label) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE svalue = ?",
            [$key, $value, $key, $value]
        );
    }
}

// ────────────────────────────────────────────────────────────
class RoleModel extends BaseModel
{
    public function getAll(): array
    {
        return $this->fetchAll("SELECT * FROM roles ORDER BY id");
    }

    public function nameExists(string $name): bool
    {
        return (bool) $this->fetchOne("SELECT id FROM roles WHERE name = ?", [$name]);
    }

    public function findByName(string $name): ?array
    {
        return $this->fetchOne("SELECT * FROM roles WHERE name = ?", [$name]);
    }

    public function deleteRole(string $name): void
    {
        // Przenieś użytkowników tej roli do roli 'operator' (id=3)
        $this->execute(
            "UPDATE users SET role_id = (SELECT id FROM roles WHERE name = 'operator' LIMIT 1)
             WHERE role_id = (SELECT id FROM roles WHERE name = ? LIMIT 1)",
            [$name]
        );
        // Usuń ustawienia uprawnień
        $this->execute("DELETE FROM settings WHERE skey = ?", ['role_perms_' . $name]);
        // Usuń rolę
        $this->execute("DELETE FROM roles WHERE name = ?", [$name]);
    }

    public function create(string $name, string $label): void
    {
        $this->execute("INSERT INTO roles (name, label) VALUES (?, ?)", [$name, $label]);
        // Domyślne uprawnienia dla nowej roli (tylko zgłaszanie)
        $this->execute(
            "INSERT IGNORE INTO settings (skey, svalue, label) VALUES (?, ?, ?)",
            ['role_perms_' . $name, json_encode(['report' => 1]), 'Uprawnienia roli: ' . $label]
        );
    }

    public function getAllPermissions(): array
    {
        $rows   = $this->fetchAll("SELECT skey, svalue FROM settings WHERE skey LIKE 'role_perms_%'");
        $result = [];
        foreach ($rows as $r) {
            $roleName = substr($r['skey'], strlen('role_perms_'));
            $result[$roleName] = json_decode($r['svalue'], true) ?? [];
        }
        // Domyślne uprawnienia dla wbudowanych ról jeśli brak w bazie
        if (!isset($result['admin']))    $result['admin']    = ['report' => 1, 'dashboard' => 1, 'failures' => 1, 'dur' => 1, 'statuses' => 1, 'admin' => 1];
        if (!isset($result['mechanic'])) $result['mechanic'] = ['dashboard' => 1, 'failures' => 1, 'dur' => 1, 'statuses' => 1];
        if (!isset($result['operator'])) $result['operator'] = ['report' => 1, 'dur' => 1];
        return $result;
    }

    public function updatePermissions(string $name, string $label, array $perms): void
    {
        // Aktualizuj etykietę roli
        $this->execute("UPDATE roles SET label = ? WHERE name = ?", [$label, $name]);
        // Zapisz uprawnienia w settings
        $this->execute(
            "INSERT INTO settings (skey, svalue, label) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)",
            ['role_perms_' . $name, json_encode($perms), 'Uprawnienia roli: ' . $label]
        );
    }
}
