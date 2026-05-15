<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\SportModuleLoader;
use App\Helpers\SportTableIntrospector;
use App\Helpers\ValidatesRequest;
use App\Models\MemberModel;
use PDO;

/**
 * Generyczny CRUD controller dla 40+ sportów bez dedykowanych UI.
 *
 * Routes (rejestrowane w public/index.php):
 *   GET  /sport/:sportKey/:resourceKey
 *   GET  /sport/:sportKey/:resourceKey/create
 *   POST /sport/:sportKey/:resourceKey/store
 *   GET  /sport/:sportKey/:resourceKey/:id/edit
 *   POST /sport/:sportKey/:resourceKey/:id/update
 *   POST /sport/:sportKey/:resourceKey/:id/delete
 *
 * Bezpieczeństwo:
 *   - Nazwa tabeli pobierana wyłącznie z `sport_module_resources` (whitelist).
 *     Nigdy z user input — chroni przed SQL injection w identyfikatorach.
 *   - Wszystkie zapytania używają prepared statements; identyfikatory są
 *     backtickowane po walidacji w/g [a-zA-Z0-9_].
 *   - Multi-tenant: WHERE club_id = ClubContext::current() (gdy tabela ma club_id).
 *     INSERT/UPDATE wymusza club_id z kontekstu (ignoruje user input dla club_id).
 *   - RBAC: zarząd / trener / admin (super admin bypass).
 *   - CSRF: na wszystkich POST.
 *
 * Sport z dedykowanym controllerem (Football, Basketball, ...) NIE jest
 * zarejestrowany w `sport_module_resources` (CLI seed omija ich tabele
 * jeśli istnieje już Controllers/ dla tego resource), więc URL pozostaje
 * dla dedykowanej trasy w manifeście.
 */
class SportModuleController extends BaseController
{
    use ValidatesRequest;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'trener', 'admin']);
    }

    public function index(string $sportKey, string $resourceKey): void
    {
        $res = $this->resolveResource($sportKey, $resourceKey);
        $intro = new SportTableIntrospector(Database::pdo());
        $fields = $intro->fields($res['table_name']);
        $pk = $intro->primaryKey($res['table_name']);

        $rows = $this->fetchAll($res['table_name'], $intro);

        // Mapy FK do labelów (member_id → "Kowalski Jan", belt_id → "Brązowy")
        $fkLabels = $this->buildFkLabels($fields, $rows);

        $this->render('sport_module/index', [
            'title'         => $res['resource_label'] . ' — ' . ucfirst($sportKey),
            'sportKey'      => $sportKey,
            'resource'      => $res,
            'fields'        => $fields,
            'rows'          => $rows,
            'primaryKey'    => $pk,
            'fkLabels'      => $fkLabels,
            'urlCreate'     => url('sport/' . $sportKey . '/' . $resourceKey . '/create'),
            'urlEdit'       => fn(int $id) => url('sport/' . $sportKey . '/' . $resourceKey . '/' . $id . '/edit'),
            'urlDelete'     => fn(int $id) => url('sport/' . $sportKey . '/' . $resourceKey . '/' . $id . '/delete'),
        ]);
    }

    public function create(string $sportKey, string $resourceKey): void
    {
        $res = $this->resolveResource($sportKey, $resourceKey);
        $intro = new SportTableIntrospector(Database::pdo());

        $this->render('sport_module/form', [
            'title'      => 'Dodaj — ' . $res['resource_label'],
            'sportKey'   => $sportKey,
            'resource'   => $res,
            'fields'     => $intro->fields($res['table_name']),
            'row'        => [],
            'members'    => $this->listMembers(),
            'fkOptions'  => $this->buildFkOptions($intro->fields($res['table_name'])),
            'formAction' => url('sport/' . $sportKey . '/' . $resourceKey . '/store'),
            'cancelUrl'  => url('sport/' . $sportKey . '/' . $resourceKey),
            'isEdit'     => false,
        ]);
    }

    public function store(string $sportKey, string $resourceKey): void
    {
        Csrf::verify();
        $res = $this->resolveResource($sportKey, $resourceKey);
        $intro = new SportTableIntrospector(Database::pdo());
        $fields = $intro->fields($res['table_name']);
        $listUrl = 'sport/' . $sportKey . '/' . $resourceKey;

        $data = $this->collectInput($fields, $listUrl);

        if ($intro->hasClubScope($res['table_name'])) {
            $data['club_id'] = (int)ClubContext::require();
        }

        $this->insert($res['table_name'], $data);
        Session::flash('success', $res['resource_label'] . ': dodano wpis.');
        $this->redirect($listUrl);
    }

    public function edit(string $sportKey, string $resourceKey, string $id): void
    {
        $res = $this->resolveResource($sportKey, $resourceKey);
        $intro = new SportTableIntrospector(Database::pdo());
        $listUrl = 'sport/' . $sportKey . '/' . $resourceKey;
        $row = $this->findRow($res['table_name'], (int)$id, $intro);

        if ($row === null) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect($listUrl);
        }

        $this->render('sport_module/form', [
            'title'      => 'Edytuj — ' . $res['resource_label'],
            'sportKey'   => $sportKey,
            'resource'   => $res,
            'fields'     => $intro->fields($res['table_name']),
            'row'        => $row,
            'members'    => $this->listMembers(),
            'fkOptions'  => $this->buildFkOptions($intro->fields($res['table_name'])),
            'formAction' => url('sport/' . $sportKey . '/' . $resourceKey . '/' . (int)$id . '/update'),
            'cancelUrl'  => url($listUrl),
            'isEdit'     => true,
        ]);
    }

    public function update(string $sportKey, string $resourceKey, string $id): void
    {
        Csrf::verify();
        $res = $this->resolveResource($sportKey, $resourceKey);
        $intro = new SportTableIntrospector(Database::pdo());
        $fields = $intro->fields($res['table_name']);
        $listUrl = 'sport/' . $sportKey . '/' . $resourceKey;

        $existing = $this->findRow($res['table_name'], (int)$id, $intro);
        if ($existing === null) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect($listUrl);
        }

        $data = $this->collectInput($fields, $listUrl);
        // Multi-tenant: nigdy nie pozwalamy zmienić club_id
        unset($data['club_id']);

        $this->updateRow($res['table_name'], (int)$id, $data, $intro);
        Session::flash('success', $res['resource_label'] . ': zaktualizowano.');
        $this->redirect($listUrl);
    }

    public function delete(string $sportKey, string $resourceKey, string $id): void
    {
        Csrf::verify();
        $res = $this->resolveResource($sportKey, $resourceKey);
        $intro = new SportTableIntrospector(Database::pdo());
        $listUrl = 'sport/' . $sportKey . '/' . $resourceKey;

        $existing = $this->findRow($res['table_name'], (int)$id, $intro);
        if ($existing === null) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect($listUrl);
        }

        $this->deleteRow($res['table_name'], (int)$id, $intro);
        Session::flash('success', $res['resource_label'] . ': usunięto.');
        $this->redirect($listUrl);
    }

    // ────────────────────────────────────────────────────────────
    // Internal helpers
    // ────────────────────────────────────────────────────────────

    /**
     * Walidacja sportKey + resourceKey przeciw whitelist `sport_module_resources`.
     * @return array{id:int, sport_key:string, resource_key:string, resource_label:string, table_name:string, icon:?string}
     */
    private function resolveResource(string $sportKey, string $resourceKey): array
    {
        // Walidacja formatu (defensywne — i tak nie trafia do SQL)
        if (!preg_match('/^[a-z][a-z0-9_]{0,39}$/', $sportKey)) {
            $this->notFound();
        }
        if (!preg_match('/^[a-z][a-z0-9_]{0,59}$/', $resourceKey)) {
            $this->notFound();
        }

        // Sprawdz czy sport istnieje w manifeście
        if (SportModuleLoader::get($sportKey) === null) {
            $this->notFound();
        }

        $stmt = Database::pdo()->prepare(
            'SELECT id, sport_key, resource_key, resource_label, table_name, icon
             FROM sport_module_resources
             WHERE sport_key = ? AND resource_key = ? AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([$sportKey, $resourceKey]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->notFound();
        }

        // Defensywna walidacja table_name (już zwalidowana przy seedzie, ale jeszcze raz)
        $table = (string)$row['table_name'];
        if (!preg_match('/^[a-z][a-z0-9_]{0,119}$/', $table)) {
            $this->notFound();
        }

        return [
            'id'             => (int)$row['id'],
            'sport_key'      => (string)$row['sport_key'],
            'resource_key'   => (string)$row['resource_key'],
            'resource_label' => (string)$row['resource_label'],
            'table_name'     => $table,
            'icon'           => $row['icon'] ?? null,
        ];
    }

    private function notFound(): never
    {
        http_response_code(404);
        $view = ROOT_PATH . '/app/Views/errors/404.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<h1>404 — moduł sportu nie istnieje</h1>';
        }
        exit;
    }

    /**
     * Walida nazwy identyfikatora (kolumna / tabela) — chroni backtick-injection.
     */
    private function safeIdent(string $name): string
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,127}$/', $name)) {
            throw new \RuntimeException('Niedozwolony identyfikator SQL: ' . $name);
        }
        return $name;
    }

    /**
     * Lista wierszy + scope na club_id (jeśli tabela ma kolumnę).
     */
    private function fetchAll(string $table, SportTableIntrospector $intro): array
    {
        $table = $this->safeIdent($table);
        $sql = "SELECT * FROM `{$table}`";
        $params = [];
        if ($intro->hasClubScope($table)) {
            $sql .= ' WHERE club_id = ?';
            $params[] = (int)ClubContext::require();
        }
        $sql .= ' ORDER BY ' . ($intro->columnExists($table, 'sort_order') ? '`sort_order` ASC, ' : '')
              . '`' . $intro->primaryKey($table) . '` DESC LIMIT 500';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function findRow(string $table, int $id, SportTableIntrospector $intro): ?array
    {
        $table = $this->safeIdent($table);
        $pk = $this->safeIdent($intro->primaryKey($table));
        $sql = "SELECT * FROM `{$table}` WHERE `{$pk}` = ?";
        $params = [$id];
        if ($intro->hasClubScope($table)) {
            $sql .= ' AND club_id = ?';
            $params[] = (int)ClubContext::require();
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function insert(string $table, array $data): int
    {
        $table = $this->safeIdent($table);
        $cols = [];
        foreach (array_keys($data) as $c) {
            $cols[] = $this->safeIdent($c);
        }
        $colSql = '`' . implode('`, `', $cols) . '`';
        $holds  = implode(', ', array_fill(0, count($data), '?'));
        $stmt   = Database::pdo()->prepare("INSERT INTO `{$table}` ({$colSql}) VALUES ({$holds})");
        $stmt->execute(array_values($data));
        return (int)Database::pdo()->lastInsertId();
    }

    private function updateRow(string $table, int $id, array $data, SportTableIntrospector $intro): void
    {
        if (empty($data)) return;
        $table = $this->safeIdent($table);
        $pk = $this->safeIdent($intro->primaryKey($table));

        $setParts = [];
        foreach (array_keys($data) as $c) {
            $col = $this->safeIdent($c);
            $setParts[] = "`{$col}` = ?";
        }
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE `{$pk}` = ?";
        $params = [...array_values($data), $id];
        if ($intro->hasClubScope($table)) {
            $sql .= ' AND club_id = ?';
            $params[] = (int)ClubContext::require();
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
    }

    private function deleteRow(string $table, int $id, SportTableIntrospector $intro): void
    {
        $table = $this->safeIdent($table);
        $pk = $this->safeIdent($intro->primaryKey($table));
        $sql = "DELETE FROM `{$table}` WHERE `{$pk}` = ?";
        $params = [$id];
        if ($intro->hasClubScope($table)) {
            $sql .= ' AND club_id = ?';
            $params[] = (int)ClubContext::require();
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Zbiera i waliduje dane formularza w/g schematu kolumn.
     *
     * @param list<array{name:string,input_type:string,required:bool,options:?array,max_length:?int,is_hidden_in_form:bool,fk_table:?string}> $fields
     */
    private function collectInput(array $fields, string $listUrl): array
    {
        $data = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            // Pomiń kolumny ukryte (id, club_id, timestamps) — wstawiane przez controller / DB
            if ($f['is_hidden_in_form']) continue;
            if ($f['input_type'] === 'blob_skip') continue;

            $raw = $_POST[$name] ?? null;
            $label = $f['name']; // używamy nazwy kolumny jako pole błędu

            switch ($f['input_type']) {
                case 'checkbox':
                    $data[$name] = !empty($raw) ? 1 : 0;
                    break;

                case 'date':
                    if (($raw === '' || $raw === null) && !$f['required']) {
                        $data[$name] = null;
                    } else {
                        $data[$name] = $this->validateDate((string)$raw, $label, $listUrl);
                    }
                    break;

                case 'datetime-local':
                    if (($raw === '' || $raw === null) && !$f['required']) {
                        $data[$name] = null;
                    } else {
                        $s = is_string($raw) ? trim($raw) : '';
                        // <input type=datetime-local> -> "YYYY-MM-DDTHH:MM"
                        $s = str_replace('T', ' ', $s);
                        if ($s !== '' && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $s)) {
                            Session::flash('error', "Pole '{$label}' musi być datą i czasem.");
                            $this->redirect($listUrl);
                        }
                        $data[$name] = $s !== '' ? $s : null;
                    }
                    break;

                case 'time':
                    if (($raw === '' || $raw === null) && !$f['required']) {
                        $data[$name] = null;
                    } else {
                        $s = is_string($raw) ? trim($raw) : '';
                        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $s)) {
                            Session::flash('error', "Pole '{$label}' musi być czasem (HH:MM).");
                            $this->redirect($listUrl);
                        }
                        $data[$name] = $s;
                    }
                    break;

                case 'number':
                case 'member_picker':
                case 'fk_select':
                    if (($raw === '' || $raw === null) && !$f['required']) {
                        $data[$name] = null;
                    } else {
                        $data[$name] = $this->validateInt((string)$raw, $label, null, null, $listUrl);
                    }
                    break;

                case 'number_decimal':
                    if (($raw === '' || $raw === null) && !$f['required']) {
                        $data[$name] = null;
                    } else {
                        if (!is_numeric($raw)) {
                            Session::flash('error', "Pole '{$label}' musi być liczbą.");
                            $this->redirect($listUrl);
                        }
                        $data[$name] = (float)$raw;
                    }
                    break;

                case 'enum':
                    if (($raw === '' || $raw === null) && !$f['required']) {
                        $data[$name] = null;
                    } else {
                        $data[$name] = $this->validateInList((string)$raw, $f['options'] ?? [], $label, $listUrl);
                    }
                    break;

                case 'textarea':
                    $data[$name] = $f['required']
                        ? $this->validateString((string)$raw, $label, 1, 65535, $listUrl)
                        : $this->validateOptionalString($raw, 65535, $listUrl);
                    break;

                case 'textarea_json':
                    if (($raw === '' || $raw === null) && !$f['required']) {
                        $data[$name] = null;
                    } else {
                        $s = (string)$raw;
                        if ($s !== '') {
                            $decoded = json_decode($s, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                Session::flash('error', "Pole '{$label}' musi być poprawnym JSON.");
                                $this->redirect($listUrl);
                            }
                            // Re-encode aby znormalizować i ograniczyć
                            $data[$name] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                        } else {
                            $data[$name] = null;
                        }
                    }
                    break;

                default: // text
                    $maxLen = $f['max_length'] ?? 255;
                    $data[$name] = $f['required']
                        ? $this->validateString((string)$raw, $label, 1, $maxLen, $listUrl)
                        : $this->validateOptionalString($raw, $maxLen, $listUrl);
                    break;
            }
        }
        return $data;
    }

    /**
     * Lista aktywnych członków klubu — dla member_picker.
     * Cache per-request.
     */
    private ?array $membersCache = null;
    private function listMembers(): array
    {
        if ($this->membersCache !== null) return $this->membersCache;
        try {
            $this->membersCache = (new MemberModel())->search('', 'aktywny', null, 1, 1000)['data'] ?? [];
        } catch (\Throwable) {
            $this->membersCache = [];
        }
        return $this->membersCache;
    }

    /**
     * Dla każdej kolumny FK (np. belt_id → judo_belts) zwraca listę opcji
     * [id => label] z tej tabeli (label = `name` jeśli istnieje, inaczej `code` / `id`).
     *
     * @param list<array{name:string,fk_table:?string,fk_column:?string,input_type:string}> $fields
     * @return array<string, array<int,string>>
     */
    private function buildFkOptions(array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            if ($f['input_type'] !== 'fk_select' || !$f['fk_table']) continue;
            $fk = $f['fk_table'];
            if (!preg_match('/^[a-z][a-z0-9_]{0,119}$/', $fk)) continue;

            $intro = new SportTableIntrospector(Database::pdo());
            $cols = array_column($intro->fields($fk), 'name');
            $labelCol = in_array('name', $cols, true) ? 'name'
                : (in_array('code', $cols, true) ? 'code'
                : (in_array('title', $cols, true) ? 'title' : null));
            $pk = $intro->primaryKey($fk);

            $sql = "SELECT `{$pk}` AS _id"
                 . ($labelCol ? ", `{$labelCol}` AS _label" : '')
                 . " FROM `{$fk}`";
            $params = [];
            if ($intro->hasClubScope($fk)) {
                $sql .= ' WHERE club_id = ?';
                $params[] = (int)ClubContext::require();
            }
            $sql .= " ORDER BY " . ($labelCol ? "`{$labelCol}`" : "`{$pk}`") . " ASC LIMIT 500";
            try {
                $stmt = Database::pdo()->prepare($sql);
                $stmt->execute($params);
                $opts = [];
                foreach ($stmt->fetchAll() as $r) {
                    $opts[(int)$r['_id']] = (string)($r['_label'] ?? '#' . $r['_id']);
                }
                $out[$f['name']] = $opts;
            } catch (\Throwable) {
                $out[$f['name']] = [];
            }
        }
        return $out;
    }

    /**
     * Mapy FK label do wyświetlenia w liście — member_id i fk_select dla każdej tabeli.
     *
     * @return array<string, array<int, string>>  (column_name → id → label)
     */
    private function buildFkLabels(array $fields, array $rows): array
    {
        $out = [];

        // member_id specifically
        $memberIds = [];
        foreach ($fields as $f) {
            if ($f['input_type'] === 'member_picker') {
                foreach ($rows as $r) {
                    if (!empty($r[$f['name']])) $memberIds[(int)$r[$f['name']]] = true;
                }
            }
        }
        if ($memberIds) {
            $ids = array_keys($memberIds);
            $place = implode(',', array_fill(0, count($ids), '?'));
            try {
                $stmt = Database::pdo()->prepare(
                    "SELECT id, first_name, last_name FROM members WHERE club_id = ? AND id IN ({$place})"
                );
                $stmt->execute([(int)ClubContext::require(), ...$ids]);
                $map = [];
                foreach ($stmt->fetchAll() as $m) {
                    $map[(int)$m['id']] = trim((string)$m['last_name'] . ' ' . (string)$m['first_name']);
                }
                foreach ($fields as $f) {
                    if ($f['input_type'] === 'member_picker') $out[$f['name']] = $map;
                }
            } catch (\Throwable) {}
        }

        // FK do innych per-sport tabel (np. belt_id → judo_belts)
        foreach ($fields as $f) {
            if ($f['input_type'] !== 'fk_select' || !$f['fk_table']) continue;
            $vals = [];
            foreach ($rows as $r) {
                if (!empty($r[$f['name']])) $vals[(int)$r[$f['name']]] = true;
            }
            if (!$vals) continue;
            try {
                $intro = new SportTableIntrospector(Database::pdo());
                $cols = array_column($intro->fields($f['fk_table']), 'name');
                $labelCol = in_array('name', $cols, true) ? 'name'
                    : (in_array('code', $cols, true) ? 'code'
                    : (in_array('title', $cols, true) ? 'title' : null));
                if (!$labelCol) continue;
                $pk = $intro->primaryKey($f['fk_table']);
                $ids = array_keys($vals);
                $place = implode(',', array_fill(0, count($ids), '?'));
                $stmt = Database::pdo()->prepare(
                    "SELECT `{$pk}` AS _id, `{$labelCol}` AS _label FROM `{$f['fk_table']}` WHERE `{$pk}` IN ({$place})"
                );
                $stmt->execute($ids);
                $map = [];
                foreach ($stmt->fetchAll() as $row) {
                    $map[(int)$row['_id']] = (string)$row['_label'];
                }
                $out[$f['name']] = $map;
            } catch (\Throwable) {}
        }
        return $out;
    }
}
