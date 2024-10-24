<?php
/**
 * Handle encryption/decryption of sensitive data
 */
class YLBI_Encryption {
    /**
     * Encryption key
     *
     * @var string
     */
    private $key;

    /**
     * Constructor
     */
    public function __construct() {
        $this->key = $this->get_encryption_key();
    }

    /**
     * Get or generate encryption key
     *
     * @return string
     */
    private function get_encryption_key() {
        $key = get_option('ylbi_encryption_key');
        
        if (!$key) {
            $key = bin2hex(random_bytes(32));
            update_option('ylbi_encryption_key', $key);
        }
        
        return $key;
    }

    /**
     * Encrypt data
     *
     * @param string $data
     * @return string|bool Encrypted data or false on failure
     */
    public function encrypt($data) {
        if (empty($data)) {
            return false;
        }

        $method = "AES-256-CBC";
        $iv = random_bytes(16); // Generate random IV
        
        try {
            $encrypted = openssl_encrypt(
                $data,
                $method,
                hex2bin($this->key),
                0,
                $iv
            );

            if ($encrypted === false) {
                YLBI_YouTube_Live_Bricks_Integration::log_error('Encryption failed: ' . openssl_error_string());
                return false;
            }

            // Combine IV and encrypted data
            return base64_encode($iv . $encrypted);
        } catch (Exception $e) {
            YLBI_YouTube_Live_Bricks_Integration::log_error('Encryption error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrypt data
     *
     * @param string $encrypted_data
     * @return string|bool Decrypted data or false on failure
     */
    public function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return false;
        }

        $method = "AES-256-CBC";
        
        try {
            $decoded = base64_decode($encrypted_data);
            
            // Extract IV and encrypted data
            $iv = substr($decoded, 0, 16);
            $encrypted = substr($decoded, 16);

            $decrypted = openssl_decrypt(
                $encrypted,
                $method,
                hex2bin($this->key),
                0,
                $iv
            );

            if ($decrypted === false) {
                YLBI_YouTube_Live_Bricks_Integration::log_error('Decryption failed: ' . openssl_error_string());
                return false;
            }

            return $decrypted;
        } catch (Exception $e) {
            YLBI_YouTube_Live_Bricks_Integration::log_error('Decryption error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a string is encrypted
     *
     * @param string $data
     * @return bool
     */
    public function is_encrypted($data) {
        if (empty($data)) {
            return false;
        }

        // Try to decode and check if it matches our encryption pattern
        $decoded = base64_decode($data, true);
        return $decoded !== false && strlen($decoded) > 16;
    }
}
