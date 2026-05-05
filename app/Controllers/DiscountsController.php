<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\ValidatesRequest;
use App\Models\FeeDiscountModel;

/**
 * Faza P.2 — CRUD zniżek klubowych.
 *
 * Każda zniżka:
 *   - typ: percent (0-100) lub fixed_amount (PLN)
 *   - conditions JSON: warunki auto-stosowania (multi-sport, junior, rodzinny)
 *   - is_stackable: czy łączyć z innymi
 *   - valid_from/to: czasowa ważność
 *
 * Pełna izolacja per klub przez FeeDiscountModel (ClubScopedModel).
 */
class DiscountsController extends BaseController
{
    use ValidatesRequest;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $discounts = (new FeeDiscountModel())->listForClub();
        $this->render('discounts/index', [
            'title'     => 'Zniżki i rabaty',
            'discounts' => $discounts,
            'types'     => FeeDiscountModel::$TYPES,
        ]);
    }

    public function create(): void
    {
        $this->render('discounts/edit', [
            'title'    => 'Nowa zniżka',
            'discount' => null, // create mode
            'types'    => FeeDiscountModel::$TYPES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $back = 'fees/discounts';

        $code = $this->validateString($_POST['code'] ?? '', 'code', 1, 40, $back);
        $code = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($code));

        $name = $this->validateString($_POST['name'] ?? '', 'name', 1, 120, $back);
        $type = $this->validateInList(
            $_POST['discount_type'] ?? '',
            FeeDiscountModel::$TYPES,
            'discount_type',
            $back
        );

        $valueRaw = $_POST['value'] ?? '';
        if (!is_numeric($valueRaw)) {
            Session::flash('error', "Pole 'value' musi być liczbą.");
            $this->redirect($back);
        }
        $value = (float)$valueRaw;
        if ($type === FeeDiscountModel::TYPE_PERCENT && ($value < 0 || $value > 100)) {
            Session::flash('error', 'Procent musi być w zakresie 0-100.');
            $this->redirect($back);
        }
        if ($type === FeeDiscountModel::TYPE_FIXED && $value < 0) {
            Session::flash('error', 'Kwota stała musi być >= 0.');
            $this->redirect($back);
        }

        // Sprawdź unique code
        if ((new FeeDiscountModel())->findByCode($code) !== null) {
            Session::flash('error', "Kod zniżki '{$code}' już istnieje w klubie.");
            $this->redirect($back);
        }

        $data = [
            'code'          => $code,
            'name'          => $name,
            'discount_type' => $type,
            'value'         => $value,
            'description'   => $this->validateOptionalString($_POST['description'] ?? null, 5000, $back),
            'conditions'    => $this->parseConditions($_POST['conditions_json'] ?? '', $back),
            'is_stackable'  => isset($_POST['is_stackable']) ? 1 : 0,
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
            'valid_from'    => $this->validateOptionalDate($_POST['valid_from'] ?? null, $back),
            'valid_to'      => $this->validateOptionalDate($_POST['valid_to'] ?? null, $back),
        ];

        (new FeeDiscountModel())->insert($data);
        Session::flash('success', 'Zniżka utworzona.');
        $this->redirect($back);
    }

    public function edit(string $id): void
    {
        $discount = (new FeeDiscountModel())->findById((int)$id);
        if (!$discount) {
            Session::flash('error', 'Nie znaleziono zniżki.');
            $this->redirect('fees/discounts');
        }
        $this->render('discounts/edit', [
            'title'    => 'Edytuj zniżkę',
            'discount' => $discount,
            'types'    => FeeDiscountModel::$TYPES,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $back = 'fees/discounts';
        $idInt = (int)$id;

        $existing = (new FeeDiscountModel())->findById($idInt);
        if (!$existing) {
            Session::flash('error', 'Nie znaleziono zniżki.');
            $this->redirect($back);
        }

        $name = $this->validateString($_POST['name'] ?? '', 'name', 1, 120, $back);
        $type = $this->validateInList(
            $_POST['discount_type'] ?? '',
            FeeDiscountModel::$TYPES,
            'discount_type',
            $back
        );

        $valueRaw = $_POST['value'] ?? '';
        if (!is_numeric($valueRaw)) {
            Session::flash('error', "Pole 'value' musi być liczbą.");
            $this->redirect($back);
        }
        $value = (float)$valueRaw;
        if ($type === FeeDiscountModel::TYPE_PERCENT && ($value < 0 || $value > 100)) {
            Session::flash('error', 'Procent musi być w zakresie 0-100.');
            $this->redirect($back);
        }

        $data = [
            'name'          => $name,
            'discount_type' => $type,
            'value'         => $value,
            'description'   => $this->validateOptionalString($_POST['description'] ?? null, 5000, $back),
            'conditions'    => $this->parseConditions($_POST['conditions_json'] ?? '', $back),
            'is_stackable'  => isset($_POST['is_stackable']) ? 1 : 0,
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
            'valid_from'    => $this->validateOptionalDate($_POST['valid_from'] ?? null, $back),
            'valid_to'      => $this->validateOptionalDate($_POST['valid_to'] ?? null, $back),
            // code NIE jest zmieniany (mógł być użyty w przyszłych odwołaniach)
        ];

        (new FeeDiscountModel())->update($idInt, $data);
        Session::flash('success', 'Zniżka zaktualizowana.');
        $this->redirect($back);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FeeDiscountModel())->delete((int)$id);
        Session::flash('success', 'Zniżka usunięta.');
        $this->redirect('fees/discounts');
    }

    public function toggleActive(string $id): void
    {
        Csrf::verify();
        $idInt = (int)$id;
        $d = (new FeeDiscountModel())->findById($idInt);
        if (!$d) {
            Session::flash('error', 'Nie znaleziono zniżki.');
            $this->redirect('fees/discounts');
        }
        $newVal = empty($d['is_active']) ? 1 : 0;
        (new FeeDiscountModel())->update($idInt, ['is_active' => $newVal]);
        Session::flash('success', $newVal ? 'Zniżka aktywowana.' : 'Zniżka dezaktywowana.');
        $this->redirect('fees/discounts');
    }

    /**
     * Parsuje JSON warunków — defensywnie, jeśli niepoprawny zwraca null.
     */
    private function parseConditions(?string $raw, string $back): ?string
    {
        $raw = trim((string)$raw);
        if ($raw === '' || $raw === '{}' || $raw === '[]') return null;
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            Session::flash('error', "Pole 'Warunki' musi być poprawnym JSON-em.");
            $this->redirect($back);
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }

    private function validateOptionalDate(mixed $value, string $back): ?string
    {
        $str = is_string($value) ? trim($value) : '';
        if ($str === '') return null;
        $d = \DateTime::createFromFormat('Y-m-d', $str);
        if (!$d || $d->format('Y-m-d') !== $str) {
            Session::flash('error', "Data musi być w formacie YYYY-MM-DD.");
            $this->redirect($back);
        }
        return $str;
    }
}
