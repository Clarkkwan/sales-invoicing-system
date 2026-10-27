<?php
session_start();

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'All fields are required.';
    } else {
        try {
            require_once 'db_config.php';
            $stmt = $pdo->prepare(
                "SELECT id, name, username, password_hash, role, active FROM users WHERE username = ? LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['active'] && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id']        = $user['id'];
                $_SESSION['username']       = $user['username'];
                $_SESSION['user_name']      = $user['name'];
                $_SESSION['role']           = $user['role'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'System error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In - Sales &amp; Invoicing System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    /* Colors */
    --surface: #ffffff;
    --accent: #2563eb;
    --accent-dark: #1d4ed8;
    --accent-glow: rgba(37,99,235,.12);
    --border: #e2e8f0;
    --text: #0f172a;
    --text-2: #475569;
    --text-3: #64748b;
    --text-lo: #94a3b8;
    --heat: #dc2626;
    --heat-bg: #fef2f2;
    --heat-ring: #fecaca;
    --heat-dark: #b91c1c;
    --sh-sm: 0 1px 2px rgba(15,23,42,.05);
}

*, *::before, *::after {
    box-sizing: border-box;
    border-radius: 0 !important;
    margin: 0;
    padding: 0;
}

html, body {
    height: 100%;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
    background: #ffffff;
}

.login-page {
    display: flex;
    min-height: 100vh;
}

/* Form panel */
.form-panel {
    flex: 1;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
}

.form-card {
    width: 100%;
    max-width: 340px;
}

.form-header {
    margin-bottom: 32px;
}

.form-eyebrow {
    font-family: 'Roboto Mono', monospace;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 10px;
}

.form-heading {
    font-size: 22px;
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.5px;
    line-height: 1.15;
}

.form-sub {
    font-size: 12px;
    color: var(--text-3);
    margin-top: 5px;
}

.error-box {
    background: var(--heat-bg);
    border: 1px solid var(--heat-ring);
    border-left: 3px solid var(--heat);
    padding: 10px 14px;
    font-size: 12px;
    color: var(--heat-dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 9px;
}

.error-box svg {
    flex-shrink: 0;
    width: 13px;
    height: 13px;
}

.field {
    margin-bottom: 18px;
}

.field-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-3);
    margin-bottom: 7px;
}

.field-input {
    width: 100%;
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 11px 14px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    box-shadow: var(--sh-sm);
}

.field-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-glow);
}

.field-input::placeholder {
    color: var(--text-lo);
}

.field-pw {
    position: relative;
}

.field-pw .field-input {
    padding-right: 42px;
}

.pw-toggle {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 44px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-lo);
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s;
}

.pw-toggle:hover {
    color: var(--text-2);
}

.btn-login {
    width: 100%;
    padding: 13px;
    background: var(--accent);
    border: none;
    color: white;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
    font-family: 'Inter', sans-serif;
    margin-top: 6px;
    box-shadow: 0 2px 6px rgba(37,99,235,.28);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-login:not(:disabled):hover {
    background: var(--accent-dark);
    box-shadow: 0 4px 14px rgba(37,99,235,.38);
    transform: translateY(-1px);
}

.btn-login:active {
    transform: translateY(0);
}

.btn-login:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.btn-arrow {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    transition: transform 0.15s;
}

.btn-login:not(:disabled):hover .btn-arrow {
    transform: translateX(3px);
}

.form-note {
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
    font-size: 11px;
    color: var(--text-lo);
    text-align: center;
}

/* Keyboard focus outlines */
.field-input:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 0;
}

.pw-toggle:focus-visible, .btn-login:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }

    20% {
        transform: translateX(-6px);
    }

    40% {
        transform: translateX(6px);
    }

    60% {
        transform: translateX(-4px);
    }

    80% {
        transform: translateX(4px);
    }
}

.shake {
    animation: shake 0.35s ease;
}

/* Respect user's motion preference */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }

    .shake {
        animation: none;
    }
}

@media (max-width: 700px) {
    .form-panel {
        padding: 60px 24px 40px;
        align-items: flex-start;
    }
}
</style>
</head>
<body>
<div class="login-page">

    <!-- Form panel -->
    <main class="form-panel">
        <div class="form-card" id="form-card">
            <div class="form-header">
                <div class="form-eyebrow">Access</div>
                <div class="form-heading">Sign In</div>
                <div class="form-sub">Enter your credentials to continue</div>
            </div>

            <?php if ($error !== ''): ?>
            <div class="error-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="login-form" novalidate>

                <div class="field">
                    <label class="field-label" for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="field-input"
                        placeholder="Enter username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        spellcheck="false"
                        autofocus
                    >
                </div>

                <div class="field">
                    <label class="field-label" for="password">Password</label>
                    <div class="field-pw">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="field-input"
                            placeholder="Enter password"
                            autocomplete="current-password"
                        >
                        <button type="button" class="pw-toggle" title="Show / hide password" aria-label="Toggle password visibility">
                            <svg id="pw-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg id="pw-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Sign In
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>

            </form>

            <div class="form-note">
                Contact your administrator for account access
            </div>
        </div>
    </main>

</div>

<script>
const pwInput  = document.getElementById('password');
const eyeOpen  = document.getElementById('pw-eye');
const eyeOff   = document.getElementById('pw-eye-off');

document.querySelector('.pw-toggle').addEventListener('click', () => {
    const show = pwInput.type === 'password';
    pwInput.type       = show ? 'text'     : 'password';
    eyeOpen.style.display = show ? 'none'  : '';
    eyeOff.style.display  = show ? ''      : 'none';
    pwInput.focus();
});

<?php if ($error !== ''): ?>
const card = document.getElementById('form-card');
card.classList.add('shake');
card.addEventListener('animationend', () => card.classList.remove('shake'), { once: true });
<?php endif; ?>

document.getElementById('login-form').addEventListener('submit', function () {
    const btn = this.querySelector('.btn-login');
    btn.disabled = true;
    btn.innerHTML = 'Signing in&hellip;';
}, { once: true });
</script>

</body>
</html>
