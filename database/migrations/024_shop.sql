SET foreign_key_checks = 0;
CREATE TABLE shop_products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  category ENUM('odzież','sprzęt','akcesoria','gadżety','inne') NOT NULL DEFAULT 'inne',
  sizes JSON NULL,
  image_path VARCHAR(255) NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sp_club (club_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
);
CREATE TABLE shop_orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NULL,
  customer_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(120) NULL,
  customer_phone VARCHAR(20) NULL,
  total DECIMAL(10,2) NOT NULL,
  status ENUM('nowe','opłacone','w_realizacji','wysłane','odebrane','anulowane') NOT NULL DEFAULT 'nowe',
  shipping_address TEXT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_so_club (club_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
);
CREATE TABLE shop_order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  size VARCHAR(20) NULL,
  FOREIGN KEY (order_id) REFERENCES shop_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES shop_products(id) ON DELETE CASCADE
);
SET foreign_key_checks = 1;
