<?php
// Shared document header: show navigation that matches the member's login state.
$pageTitle = $pageTitle ?? 'Kusina ng Barangay';
$notice = flash();
?>
<!-- Start the shared document shell used by every page. -->
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
  <title><?= h($pageTitle) ?> · Kusina ng Barangay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<!-- Show links for members or guest account entry points, depending on session state. -->
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark"><svg aria-hidden="true" viewBox="0 0 40 40"><path d="M13 17c-3 2-3 5 0 7M20 14c-3 2-3 5 0 7M27 17c-3 2-3 5 0 7M9 25h22l-2 9H11l-2-9ZM7 25h26"/></svg></span><span>Kusina <i>ng Barangay</i></span></a>
  <button class="nav-toggle" type="button" aria-label="Open navigation" aria-controls="primary-navigation" aria-expanded="false"><span></span><span></span><span></span></button>
  <nav id="primary-navigation" aria-label="Main navigation">
  <?php if (signed_in()): ?>
    <a href="index.php">Recipes</a><a href="favorites.php">My favorites</a><a class="nav-cta" href="recipe_form.php">＋ Share a recipe</a>
    <details class="account-menu"><summary>Hi, <?= h($_SESSION['user']['name']) ?><span aria-hidden="true">⌄</span></summary><div class="account-dropdown"><form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="account-logout" type="submit">Log out</button></form></div></details>
  <?php else: ?>
    <a href="login.php">Log in</a><a class="nav-cta" href="register.php">Join the table</a>
  <?php endif; ?>
  </nav>
</header>
<!-- Main content wrapper and one-time status message shared by member pages. -->
<main class="page">
<?php if ($notice): ?><div class="notice" role="status"><?= h($notice) ?></div><?php endif; ?>
