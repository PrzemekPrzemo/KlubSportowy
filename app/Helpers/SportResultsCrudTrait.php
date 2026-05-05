<?php

namespace App\Helpers;

use App\Models\BaseModel;
use App\Models\MemberModel;

/**
 * Generic show/edit/update implementation dla stub-sportow.
 *
 * Zamiast pisac per-sport edit/update method'y w 9 controllerach, kazdy
 * stub-controller deklaruje:
 *
 *   use SportResultsCrudTrait;
 *   protected function crudConfig(): array {
 *       return [
 *           'model'        => new BadmintonResultModel(),
 *           'table'        => 'badminton_results',
 *           'index_route'  => 'badminton/results',
 *           'view_prefix'  => 'badminton/results',
 *           'title_show'   => 'Szczegóły wyniku — Badminton',
 *           'title_edit'   => 'Edytuj wynik — Badminton',
 *           'extra_selects' => [  // opcjonalnie — ENUM z humane labels
 *               'category' => ['label' => 'Kategoria', 'options' => BadmintonResultModel::$CATEGORIES],
 *           ],
 *       ];
 *   }
 *
 * Trait dostarcza show($id), edit($id), update($id) — uniwersalne,
 * uzywajace SportResultIntrospector do auto-generowania form fields.
 */
trait SportResultsCrudTrait
{
    use ValidatesRequest;

    /**
     * Per-sport configuration. Each stub controller MUST implement.
     * @return array{model:BaseModel, table:string, index_route:string,
     *               view_prefix:string, title_show?:string, title_edit?:string,
     *               extra_selects?:array<string,array{label:string,options:array<string,string>}>}
     */
    abstract protected function crudConfig(): array;

    public function show(string $id): void
    {
        $cfg = $this->crudConfig();
        /** @var \App\Models\BaseModel $model */
        $model = $cfg['model'];
        $row = $model->findById((int)$id);
        if (!$row) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect($cfg['index_route']);
        }

        $member = isset($row['member_id']) ? (new MemberModel())->findById((int)$row['member_id']) : null;
        $fields = (new SportResultIntrospector(Database::pdo()))->fields($cfg['table']);

        $this->render($cfg['view_prefix'] . '/show', [
            'title'        => $cfg['title_show'] ?? 'Szczegóły',
            'row'          => $row,
            'member'       => $member,
            'fields'       => $fields,
            'editUrl'      => url($cfg['index_route'] . '/' . (int)$id . '/edit'),
            'listUrl'      => url($cfg['index_route']),
            'extraLabels'  => $cfg['extra_selects'] ?? [],
        ]);
    }

    public function edit(string $id): void
    {
        $cfg = $this->crudConfig();
        $model = $cfg['model'];
        $row = $model->findById((int)$id);
        if (!$row) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect($cfg['index_route']);
        }

        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $fields  = (new SportResultIntrospector(Database::pdo()))->fields($cfg['table']);

        $this->render($cfg['view_prefix'] . '/edit', [
            'title'        => $cfg['title_edit'] ?? 'Edytuj',
            'row'          => $row,
            'fields'       => $fields,
            'members'      => $members,
            'formAction'   => url($cfg['index_route'] . '/' . (int)$id . '/update'),
            'cancelUrl'    => url($cfg['index_route']),
            'extraSelects' => $cfg['extra_selects'] ?? [],
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $cfg = $this->crudConfig();
        $model = $cfg['model'];
        $idInt = (int)$id;
        $back  = $cfg['index_route'];

        $row = $model->findById($idInt);
        if (!$row) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect($back);
        }

        $fields = (new SportResultIntrospector(Database::pdo()))->fields($cfg['table']);

        $data = ['member_id' => $this->validateInt($_POST['member_id'] ?? '', 'member_id', 1, null, $back)];
        foreach ($fields as $f) {
            $name = $f['name'];
            if ($name === 'member_id') continue;
            $raw  = $_POST[$name] ?? null;

            if ($f['input_type'] === 'enum') {
                if ($raw === '' || $raw === null) {
                    if (!$f['required']) { $data[$name] = null; continue; }
                    $this->validationFailRedirect("Pole '{$f['label']}' jest wymagane.", $back);
                }
                $data[$name] = $this->validateInList($raw, $f['options'], $f['label'], $back);
            } elseif ($f['input_type'] === 'date') {
                if (($raw === '' || $raw === null) && !$f['required']) { $data[$name] = null; continue; }
                $data[$name] = $this->validateDate($raw ?? '', $f['label'], $back);
            } elseif ($f['input_type'] === 'number') {
                if (($raw === '' || $raw === null) && !$f['required']) { $data[$name] = null; continue; }
                $data[$name] = $this->validateInt($raw ?? '', $f['label'], null, null, $back);
            } elseif ($f['input_type'] === 'number_decimal') {
                if (($raw === '' || $raw === null) && !$f['required']) { $data[$name] = null; continue; }
                if (!is_numeric($raw)) {
                    $this->validationFailRedirect("Pole '{$f['label']}' musi być liczbą.", $back);
                }
                $data[$name] = (float)$raw;
            } elseif ($f['input_type'] === 'checkbox') {
                $data[$name] = !empty($raw) ? 1 : 0;
            } elseif ($f['input_type'] === 'textarea') {
                $data[$name] = $f['required']
                    ? $this->validateString($raw ?? '', $f['label'], 1, 65535, $back)
                    : $this->validateOptionalString($raw, 65535, $back);
            } else {
                $maxLen = $f['max_length'] ?? 255;
                $data[$name] = $f['required']
                    ? $this->validateString($raw ?? '', $f['label'], 1, $maxLen, $back)
                    : $this->validateOptionalString($raw, $maxLen, $back);
            }
        }

        $model->update($idInt, $data);
        Session::flash('success', 'Zaktualizowano.');
        $this->redirect($back);
    }

    /** Pomoc dla traita — używa private metody validationFail z ValidatesRequest. */
    private function validationFailRedirect(string $msg, string $redirectTo): never
    {
        Session::flash('error', $msg);
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        header('Location: ' . $base . '/' . ltrim($redirectTo, '/'));
        exit;
    }
}
