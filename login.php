<?php require 'config.php';
if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? ''); $pw = $_POST['password'] ?? '';
    if (!csrf_ok()) $error = 'Session expired. Please try again.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pw === '') $error = 'Enter your email and password.';
    else {
        $st = db()->prepare('SELECT user_id, name, password_hash FROM users WHERE email=?'); $st->execute([$email]);
        $u = $st->fetch();
        if ($u && password_verify($pw, $u['password_hash'])) {
            session_regenerate_id(true);               // prevents session fixation
            $_SESSION['user_id'] = (int)$u['user_id']; $_SESSION['user_name'] = $u['name'];
            header('Location: dashboard.php'); exit;
        }
        $error = 'Incorrect email or password.';       // same message for both cases
    }
}
$title = 'Log in'; $auth = true; include 'includes/header.php'; ?>
<div class="auth-wrap">
  <section class="auth-card card">
    <div class="logo big">₹</div>
    <h1>Welcome back</h1>
    <p class="muted">Log in to track your spending.</p>
    <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate data-validate="login">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <label>Email<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
      <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
      <div class="form-error" aria-live="polite"></div>
      <button class="btn btn-primary block" type="submit">Log in</button>
    </form>
    <p class="muted center">New here? <a href="register.php">Create an account</a></p>
    <p class="hint center">Demo: demo@example.com / demo1234 (after running seed.php)</p>
  </section>
</div>
<?php include 'includes/footer.php'; ?>
