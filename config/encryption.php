<?php
// Encryption key configuration.
// Copy to config/encryption.local.php and set a real key.
// Generate key: php cli/generate-key.php
return [
    'key'    => '',  // base64 encoded 32-byte key
    'cipher' => 'aes-256-gcm',
];
