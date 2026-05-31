<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/includes/db.php';

$message = '';
$error = '';
$valid_token = false;

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($token) || empty($email)) {
    $error = 'Invalid or missing password reset token.';
} else {
    $db = getDB();
    
    // Check if token exists and is valid (e.g., within 1 hour)
    // We didn't enforce expiry in DB but let's check existence
    $stmt = $db->prepare('SELECT id, created_at FROM password_resets WHERE email = ? AND token = ?');
    $stmt->execute([$email, $token]);
    $reset_record = $stmt->fetch();
    
    if ($reset_record) {
        $valid_token = true;
        
        // Optional: Check expiry (e.g., 1 hour = 3600 seconds)
        if (strtotime($reset_record['created_at']) < (time() - 3600)) {
            $valid_token = false;
            $error = 'This password reset link has expired. Please request a new one.';
        }
    } else {
        $error = 'Invalid password reset link.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Update the user's password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        
        if ($stmt->execute([$hash, $email])) {
            // Delete the used token
            $db->prepare('DELETE FROM password_resets WHERE email = ? AND token = ?')->execute([$email, $token]);
            
            $message = 'Your password has been successfully reset.';
            $valid_token = false; // Hide the form after success
        } else {
            $error = 'An error occurred while resetting your password.';
        }
    }
}
?>
<?php
$pageTitle = 'Set New Password — Advent Library';
include __DIR__ . '/includes/header.php';
?>
    <style type="text/tailwindcss">
        :root {
            --c-bg: theme('colors.soft-white');
            --c-surface: #ffffff;
            --c-border: rgba(30, 58, 95, 0.1);
            --c-gold: theme('colors.gold');
            --c-gold-hover: #e8b84a;
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 1rem;
        }

        .glass-panel {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: 1.5rem;
            position: relative;
            z-index: 10;
        }

        .form-input {
            background: theme('colors.soft-white');
            border: 1px solid var(--c-border);
            color: theme('colors.navy');
            transition: all 0.3s ease;
        }
        .form-input:focus {
            border-color: var(--c-gold);
            outline: none;
            box-shadow: 0 0 0 1px var(--c-gold);
        }
    </style>

<div class="auth-container">

    <!-- Background Decoration -->
    <div class="absolute inset-0 z-0">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[var(--c-gold)] rounded-full mix-blend-multiply filter blur-[128px] opacity-10 animate-blob"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-blue-900 rounded-full mix-blend-multiply filter blur-[128px] opacity-20 animate-blob animation-delay-2000"></div>
    </div>

    <!-- Main Container -->
    <main class="w-full max-w-md relative z-10">
        
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <a href="index.php" class="inline-flex items-center gap-2 mb-6 group">
                <div class="w-10 h-10 flex items-center justify-center transition-transform duration-300 group-hover:scale-110">
                    <img src="includes/logo.png" alt="Advent Library Logo" class="w-full h-full object-contain rounded-lg" />
                </div>
                <span class="text-2xl font-bold tracking-tight text-navy font-display">Advent<span class="text-[var(--c-gold)]">Library</span></span>
            </a>
            <h1 class="text-2xl font-bold text-navy font-display mb-2">Create New Password</h1>
            <p class="text-slate-500 text-sm">Please enter your new password below.</p>
        </div>

        <div class="glass-panel p-8 shadow-2xl">
            
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 text-sm p-4 rounded-xl mb-6 text-center">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="bg-green-500/10 border border-green-500/50 text-green-400 text-sm p-4 rounded-xl mb-6 text-center">
                    <?= htmlspecialchars($message) ?>
                    <div class="mt-4">
                        <a href="login.php" class="inline-block bg-[var(--c-gold)] hover:bg-[var(--c-gold-hover)] text-[#0b1120] font-bold py-2 px-6 rounded-lg transition-colors">Go to Login</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <!-- Form -->
                <form action="reset_password.php?token=<?= urlencode($token) ?>&email=<?= urlencode($email) ?>" method="POST" class="space-y-5">
                    
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <input type="password" id="password" name="password" class="form-input w-full rounded-xl pl-12 pr-4 py-3.5 text-sm" placeholder="••••••••" required minlength="6">
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Confirm New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input w-full rounded-xl pl-12 pr-4 py-3.5 text-sm" placeholder="••••••••" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-[var(--c-gold)] hover:bg-[var(--c-gold-hover)] text-[#ffffff] font-bold py-3.5 px-4 rounded-xl transition-all duration-300 hover:-translate-y-0.5 mt-2 flex justify-center items-center gap-2">
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if (!$valid_token && !$message): ?>
                <div class="mt-8 text-center">
                    <a href="forgot_password.php" class="text-sm font-medium text-slate-500 hover:text-[var(--c-gold)] transition-colors">Request a new link</a>
                </div>
            <?php endif; ?>

        </div>
    </main>

</div>
</html>
