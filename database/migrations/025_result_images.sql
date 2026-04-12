CREATE TABLE result_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NULL,
  member_id INT UNSIGNED NULL,
  sport_id INT UNSIGNED NULL,
  image_path VARCHAR(255) NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  extracted_data JSON NULL,
  status ENUM('uploaded','processed','verified') NOT NULL DEFAULT 'uploaded',
  uploaded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ri_club (club_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
  FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE SET NULL,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);
