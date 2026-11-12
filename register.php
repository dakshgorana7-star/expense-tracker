<?php require 'config.php';
if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$error = ''; $name = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
    $pw = $_POST['password'] ?? ''; $pw2 = $_POST['confirm'] ?? '';
    if (!csrf_ok()) $error = 'Session expired. Please try again.';
    elseif ($name === '' || len($name) > 80)        $error = 'Enter your name.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))   $error = 'Enter a valid email address.';
    elseif (strlen($pw) < 8)                              $error = 'Password must be at least 8 characters.';
    elseif ($pw !== $pw2)                                 $error = 'Passwords do not match.';
    else {
        $chk = db()->prepare('SELECT 1 FROM users WHERE email=?'); $chk->execute([$email]);
        if ($chk->fetch()) $error = 'An account with this email already exists.';
        else {
            db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?,?,?)')
                ->execute([$name, $email, password_hash($pw, PASSWORD_BCRYPT)]);   // bcrypt hash
            $uid = (int)db()->lastInsertId();
            default_categories($uid);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $uid; $_SESSION['user_name'] = $name;
            header('Location: dashboard.php'); exit;
        }
    }
}
$title = 'Create account'; $auth = true; include 'includes/header.php'; ?>
<div class="auth-wrap">
  <section class="auth-card card">
    <div class="logo big">₹</div>
    <h1>Create your account</h1>
    <p class="muted">Start tracking expenses in a minute.</p>
    <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate data-validate="register">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <label>Name<input type="text" name="name" value="<?= e($name) ?>" required maxlength="80" autocomplete="name"></label>
      <label>Email<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
      <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <label>Confirm password<input type="password" name="confirm" required autocomplete="new-password"></label>
      <div class="form-error" aria-live="polite"></div>
      <button class="btn btn-primary block" type="submit">Create account</button>
    </form>
    <p class="muted center">Already registered? <a href="login.php">Log in</a></p>
  </section>
</div>
<?php include 'includes/footer.php'; ?>
