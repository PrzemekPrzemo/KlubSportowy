<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ClubCustomizationModel;
use App\Models\ShopOrderItemModel;
use App\Models\ShopOrderModel;
use App\Models\ShopProductModel;

class ShopController extends BaseController
{
    // ── Admin: Product Management ─────────────────────────────

    /**
     * Admin product list.
     */
    public function products(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $products = (new ShopProductModel())->findAll('name', 'ASC');

        $this->render('shop/products', [
            'title'    => 'Sklep — Produkty',
            'products' => $products,
        ]);
    }

    /**
     * Product create/edit form.
     */
    public function productForm(string $id = ''): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $product = null;
        if ($id !== '') {
            $product = (new ShopProductModel())->findById((int)$id);
            if (!$product) {
                Session::flash('error', 'Produkt nie istnieje.');
                $this->redirect('shop/products');
            }
        }

        $this->render('shop/product_form', [
            'title'   => $product ? 'Edytuj produkt' : 'Nowy produkt',
            'product' => $product,
        ]);
    }

    /**
     * Store/update product.
     */
    public function storeProduct(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        Csrf::verify();

        $model = new ShopProductModel();
        $id    = (int)($_POST['id'] ?? 0);

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Nazwa produktu jest wymagana.');
            $this->redirect($id ? "shop/products/{$id}/edit" : 'shop/products/create');
        }

        $sizes = trim($_POST['sizes'] ?? '');
        $sizesJson = null;
        if ($sizes !== '') {
            $sizesArr = array_map('trim', explode(',', $sizes));
            $sizesJson = json_encode($sizesArr, JSON_UNESCAPED_UNICODE);
        }

        $data = [
            'name'        => $name,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'price'       => (float)($_POST['price'] ?? 0),
            'category'    => $_POST['category'] ?? 'inne',
            'sizes'       => $sizesJson,
            'stock'       => (int)($_POST['stock'] ?? 0),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];

        // Handle image upload
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadProductImage($_FILES['image']);
            if ($imagePath !== null) {
                $data['image_path'] = $imagePath;
            }
        }

        if ($id > 0) {
            $model->update($id, $data);
            Session::flash('success', 'Produkt zaktualizowany.');
        } else {
            $model->insert($data);
            Session::flash('success', 'Produkt dodany.');
        }

        $this->redirect('shop/products');
    }

    /**
     * Delete product.
     */
    public function deleteProduct(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        Csrf::verify();

        $model   = new ShopProductModel();
        $product = $model->findById((int)$id);

        if ($product) {
            // Remove image
            if (!empty($product['image_path'])) {
                $filePath = ROOT_PATH . '/public/' . $product['image_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            $model->delete((int)$id);
            Session::flash('success', 'Produkt usuniety.');
        } else {
            Session::flash('error', 'Produkt nie istnieje.');
        }

        $this->redirect('shop/products');
    }

    // ── Public: Catalog ───────────────────────────────────────

    /**
     * Public product catalog (no login required).
     */
    public function catalog(string $slug): void
    {
        $this->view->setLayout('public');

        $club = $this->findClubBySlug($slug);
        if ($club === null) {
            http_response_code(404);
            echo '<h1>404 - Klub nie znaleziony</h1>';
            return;
        }

        $products = (new ShopProductModel())->findActiveForClub((int)$club['id']);

        $this->render('shop/catalog', [
            'title'    => $club['name'] . ' — Sklep',
            'club'     => $club,
            'products' => $products,
        ]);
    }

    // ── Cart (Session-based) ──────────────────────────────────

    /**
     * Add product to session cart.
     */
    public function addToCart(): void
    {
        Csrf::verify();
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));
        $size      = trim($_POST['size'] ?? '');
        $clubId    = (int)($_POST['club_id'] ?? 0);

        if ($productId <= 0 || $clubId <= 0) {
            Session::flash('error', 'Nieprawidlowe dane.');
            $this->redirect('shop/cart');
        }

        $product = (new ShopProductModel())->findByIdForClub($productId, $clubId);
        if (!$product) {
            Session::flash('error', 'Produkt nie istnieje.');
            $this->redirect('shop/cart');
        }

        $cart = Session::get('shop_cart', []);
        $cartKey = $productId . '_' . $size;

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => $productId,
                'club_id'    => $clubId,
                'name'       => $product['name'],
                'price'      => (float)$product['price'],
                'image_path' => $product['image_path'],
                'size'       => $size,
                'quantity'   => $quantity,
            ];
        }

        Session::set('shop_cart', $cart);
        Session::flash('success', 'Dodano do koszyka.');

        // Redirect back to catalog
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT cc.subdomain FROM club_customization cc WHERE cc.club_id = ? LIMIT 1");
        $stmt->execute([$clubId]);
        $row  = $stmt->fetch();
        $slug = $row ? $row['subdomain'] : '';
        if ($slug) {
            $this->redirect('pub/' . $slug . '/shop');
        } else {
            $this->redirect('shop/cart');
        }
    }

    /**
     * View cart.
     */
    public function cart(): void
    {
        $this->view->setLayout('public');

        $cart  = Session::get('shop_cart', []);
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $this->render('shop/cart', [
            'title' => 'Koszyk',
            'cart'  => $cart,
            'total' => $total,
        ]);
    }

    /**
     * Checkout form.
     */
    public function checkout(): void
    {
        $this->view->setLayout('public');

        $cart = Session::get('shop_cart', []);
        if (empty($cart)) {
            Session::flash('warning', 'Koszyk jest pusty.');
            $this->redirect('shop/cart');
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $this->render('shop/checkout', [
            'title' => 'Zamowienie',
            'cart'  => $cart,
            'total' => $total,
        ]);
    }

    /**
     * Store order from checkout.
     */
    public function storeOrder(): void
    {
        Csrf::verify();
        $cart = Session::get('shop_cart', []);
        if (empty($cart)) {
            Session::flash('warning', 'Koszyk jest pusty.');
            $this->redirect('shop/cart');
        }

        $customerName  = trim($_POST['customer_name'] ?? '');
        $customerEmail = trim($_POST['customer_email'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $address       = trim($_POST['shipping_address'] ?? '');
        $notes         = trim($_POST['notes'] ?? '');

        if ($customerName === '') {
            Session::flash('error', 'Imie i nazwisko jest wymagane.');
            $this->redirect('shop/checkout');
        }

        // Determine club_id from cart (all items should be same club)
        $clubId = 0;
        foreach ($cart as $item) {
            $clubId = (int)$item['club_id'];
            break;
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $orderModel = new ShopOrderModel();
        $orderId = $orderModel->withoutScope()->insert([
            'club_id'          => $clubId,
            'customer_name'    => $customerName,
            'customer_email'   => $customerEmail ?: null,
            'customer_phone'   => $customerPhone ?: null,
            'total'            => $total,
            'status'           => 'nowe',
            'shipping_address' => $address ?: null,
            'notes'            => $notes ?: null,
        ]);

        $itemModel   = new ShopOrderItemModel();
        $productModel = new ShopProductModel();

        foreach ($cart as $item) {
            $itemModel->insert([
                'order_id'   => $orderId,
                'product_id' => (int)$item['product_id'],
                'quantity'   => (int)$item['quantity'],
                'unit_price' => (float)$item['price'],
                'size'       => $item['size'] ?: null,
            ]);
            // Decrease stock
            $productModel->withoutScope()->decreaseStock((int)$item['product_id'], (int)$item['quantity']);
        }

        // Clear cart
        Session::remove('shop_cart');

        $this->redirect('shop/confirmation/' . $orderId);
    }

    /**
     * Order confirmation page.
     */
    public function orderConfirmation(string $id): void
    {
        $this->view->setLayout('public');

        $order = (new ShopOrderModel())->withoutScope()->findWithItems((int)$id);
        if (!$order) {
            Session::flash('error', 'Zamowienie nie istnieje.');
            $this->redirect('shop/cart');
        }

        $this->render('shop/order_confirmation', [
            'title' => 'Potwierdzenie zamowienia #' . $order['id'],
            'order' => $order,
        ]);
    }

    // ── Admin: Orders ─────────────────────────────────────────

    /**
     * Admin order list.
     */
    public function orders(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $page       = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new ShopOrderModel())->listOrders($page, 20);

        $this->render('shop/orders', [
            'title'      => 'Sklep — Zamowienia',
            'pagination' => $pagination,
        ]);
    }

    /**
     * Update order status (admin).
     */
    public function updateOrderStatus(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        Csrf::verify();

        $status = $_POST['status'] ?? '';
        $valid  = ['nowe', 'opłacone', 'w_realizacji', 'wysłane', 'odebrane', 'anulowane'];
        if (!in_array($status, $valid, true)) {
            Session::flash('error', 'Nieprawidlowy status.');
            $this->redirect('shop/orders');
        }

        (new ShopOrderModel())->updateStatus((int)$id, $status);
        Session::flash('success', 'Status zamowienia zaktualizowany.');
        $this->redirect('shop/orders');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function uploadProductImage(array $file): ?string
    {
        // Bezpieczna walidacja:
        //   1. UPLOAD_ERR_OK (klient mogl wyslac broken plik)
        //   2. MIME wykryty serwer-side przez finfo (nie ufamy $file['type'],
        //      ktory jest browser-supplied i mozna go sfalszowac)
        //   3. Extension wynika z verified MIME, nie z $file['name']
        //      (zeby atakujacy nie wgral "evil.php" z Content-Type: image/png)
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: '';
        if (!isset($mimeToExt[$mime])) {
            return null;
        }
        $ext = $mimeToExt[$mime];

        $clubId = ClubContext::current() ?: 0;
        $dir    = 'uploads/shop/' . $clubId;
        $absDir = ROOT_PATH . '/public/' . $dir;
        if (!is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }

        $filename = uniqid('prod_', true) . '.' . $ext;
        $dest     = $absDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return $dir . '/' . $filename;
    }

    private function findClubBySlug(string $slug): ?array
    {
        $db   = Database::pdo();
        $stmt = $db->prepare(
            "SELECT c.*, cc.logo_path, cc.subdomain, cc.motto, cc.primary_color
             FROM clubs c
             JOIN club_customization cc ON cc.club_id = c.id
             WHERE cc.subdomain = ? AND c.is_active = 1
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
