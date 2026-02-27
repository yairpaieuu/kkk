<?php
if (!class_exists('AuthController')) {
    class AuthController {
        private $db;

        public function __construct() {
            if (!class_exists('Database')) require_once dirname(__DIR__) . '/../config/database.php';
            $this->db = (new Database())->getConnection();
            if (session_status() === PHP_SESSION_NONE) session_start();
        }

        // --- REGISTER ---
        public function showRegister() {
            require_once dirname(__DIR__) . '/views/auth/register.php';
        }

        public function register() {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            // 1. Check if email exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already exists";
                require_once dirname(__DIR__) . '/views/auth/register.php';
                return;
            }

            // 2. Generate OTP
            $otp = rand(100000, 999999);
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // 3. Insert User (Not Verified)
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password, role, is_verified, otp_code) VALUES (?, ?, ?, 'customer', 0, ?)");
            
            if ($stmt->execute([$username, $email, $hash, $otp])) {
                // 4. Send Email
                $this->sendOtpEmail($email, $otp);

                // 5. Store email in session for verification page
                $_SESSION['verify_email'] = $email;
                header("Location: /verify-otp");
                exit;
            } else {
                $error = "Registration failed";
                require_once dirname(__DIR__) . '/views/auth/register.php';
            }
        }

        // --- VERIFY OTP ---
        public function showVerifyOtp() {
            if (!isset($_SESSION['verify_email'])) {
                header("Location: /register");
                exit;
            }
            require_once dirname(__DIR__) . '/views/auth/verify_otp.php';
        }

        public function verifyOtp() {
            $email = $_SESSION['verify_email'] ?? null;
            $inputOtp = implode('', $_POST['otp']); // Combining array inputs if using multiple boxes, or simple string

            if (!$email) {
                header("Location: /register");
                exit;
            }

            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['otp_code'] == $inputOtp) {
                // Verify Success
                $this->db->prepare("UPDATE users SET is_verified = 1, otp_code = NULL WHERE id = ?")->execute([$user['id']]);
                
                // Log them in
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                unset($_SESSION['verify_email']);
                header("Location: /");
                exit;
            } else {
                $error = "Invalid OTP Code";
                require_once dirname(__DIR__) . '/views/auth/verify_otp.php';
            }
        }

        // --- LOGIN ---
        public function showLogin() {
            require_once dirname(__DIR__) . '/views/auth/login.php';
        }

        public function login() {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Check Verification
                if ($user['is_verified'] == 0) {
                    // Resend OTP and redirect to verify
                    $otp = rand(100000, 999999);
                    $this->db->prepare("UPDATE users SET otp_code = ? WHERE id = ?")->execute([$otp, $user['id']]);
                    $this->sendOtpEmail($email, $otp);
                    
                    $_SESSION['verify_email'] = $email;
                    $error = "Please verify your email first. A new code has been sent.";
                    require_once dirname(__DIR__) . '/views/auth/verify_otp.php';
                    return;
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: /admin");
                } else {
                    header("Location: /");
                }
                exit;
            } else {
                $error = "Invalid email or password";
                require_once dirname(__DIR__) . '/views/auth/login.php';
            }
        }

        public function logout() {
            session_destroy();
            header("Location: /login");
            exit;
        }

        // --- HELPER: SEND EMAIL ---
        private function sendOtpEmail($to, $otp) {
            $subject = "Verify Your Account";
            $message = "Your verification code is: " . $otp;
            $headers = "From: no-reply@" . $_SERVER['SERVER_NAME'];
            
            // Try sending using PHP mail()
            // Note: For production, use PHPMailer or an SMTP library.
            @mail($to, $subject, $message, $headers);
        }
    }
}
?>