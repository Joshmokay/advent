<?php
// login.php — Sign In Page
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php'; // bootstraps security.php

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF gate
    csrf_validate();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, name, password_hash, status FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'suspended') {
                $error = 'This account has been suspended.';
            } else {
                // Prevent session fixation
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                // Bind session to client fingerprint
                $_SESSION['_user_fingerprint'] = hash('sha256',
                    ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? '')
                );
                $redirect = filter_var($_GET['redirect'] ?? '', FILTER_SANITIZE_URL);
                $redirect = (str_starts_with($redirect, '/') && !str_starts_with($redirect, '//'))
                    ? $redirect : 'index.php';
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<?php
$pageTitle = 'Sign In — Advent Library';
include __DIR__ . '/includes/header.php';
?>
    <style type="text/tailwindcss">
        :root {
            --c-bg:          theme('colors.soft-white');
            --c-surface:     #ffffff;
            --c-border:      rgba(30, 58, 95, 0.1);
            --c-gold:        theme('colors.gold');
            --c-gold-pale:   theme('colors.gold/10');
            --c-gold-glow:   theme('colors.gold/20');

            --c-text-primary:   theme('colors.navy');
            --c-text-secondary: theme('colors.slate.500');
            --c-text-muted:     theme('colors.slate.400');
        }

        /* Hero Background for Auth Pages */
        .auth-container {
            flex-grow: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6rem 1rem 2rem;
            overflow: hidden;
        }
        .auth-bg {
            position: absolute; inset: 0;
            background-image: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1600&q=80&auto=format');
            background-size: cover; background-position: center top;
            opacity: .05; filter: grayscale(40%);
        }
        .auth-gradient {
            position: absolute; inset: 0;
            background: radial-gradient(circle at 50% 50%, rgba(201,149,58,.05) 0%, var(--c-bg) 80%);
        }

        /* Form Styles */
        .auth-card {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: 1rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 28rem;
            position: relative;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(201,149,58,.1);
            animation: fadeUp 0.6s ease-out forwards;
        }
        
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-title {
            font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.025em;
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--c-text-primary);
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .auth-subtitle {
            text-align: center;
            color: var(--c-text-secondary);
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .form-group { margin-bottom: 1.5rem; }
        .form-label {
            display: block;
            color: var(--c-text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .form-input {
            width: 100%;
            background: var(--c-bg);
            border: 1px solid var(--c-border);
            color: var(--c-text-primary);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.2s;
            outline: none;
        }
        .form-input:focus {
            border-color: var(--c-gold);
            box-shadow: 0 0 0 3px var(--c-gold-pale);
        }
        
        .btn-submit {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            background: var(--c-gold);
            color: #0b1120;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.85rem;
            border-radius: 0.5rem;
            transition: all 0.25s ease;
            margin-top: 2rem;
            border: 1px solid transparent;
        }
        .btn-submit:hover {
            background: var(--c-gold-light);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px var(--c-gold-glow);
        }

        .auth-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--c-text-secondary);
        }
        .auth-link {
            color: var(--c-gold);
            font-weight: 500;
            transition: color 0.2s;
        }
        .auth-link:hover { color: var(--c-gold-light); text-decoration: underline; }
        
        .error-message {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: theme('colors.red.600');
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="auth-container">
    <div class="auth-bg" aria-hidden="true"></div>
    <div class="auth-gradient" aria-hidden="true"></div>
    
    <div class="auth-card">
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Sign in to your Advent Library account</p>
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="on">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="you@example.com" required autocomplete="email">
            </div>
            
            <div class="form-group">
                <div class="flex justify-between items-center mb-2">
                    <label for="password" class="form-label !mb-0">Password</label>
                    <a href="forgot_password.php" class="auth-link text-xs">Forgot password?</a>
                </div>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-submit">
                Sign In
            </button>
        </form>
        
        <div class="flex items-center my-6">
            <div class="flex-grow border-t border-[var(--c-border)]"></div>
            <span class="px-4 text-xs text-[var(--c-text-muted)] uppercase tracking-wider font-medium">Or continue with</span>
            <div class="flex-grow border-t border-[var(--c-border)]"></div>
        </div>
        
        <a href="#" class="flex items-center justify-center w-full bg-[var(--c-bg)] hover:bg-[var(--c-surface-2)] border border-[var(--c-border)] text-[var(--c-text-primary)] py-3 rounded-lg transition-all duration-200 shadow-sm hover:border-[var(--c-text-muted)]">
            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Google
        </a>
        
        <div class="auth-footer">
            Don't have an account? <a href="signup.php" class="auth-link">Sign up</a>
        </div>
    </div>
</div>

</body>
</html>

