<?php
// Shared bootstrap: opens the database, starts the session, and defines reusable safeguards.
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/models.php';

// Use an HttpOnly session cookie so browser scripts cannot read the login identifier.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Escape dynamic text at output time to prevent user content from becoming executable HTML.
function h(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
// Keep authentication checks consistent across all member-only pages.
function signed_in(): bool { return isset($_SESSION['user']['id']); }
function require_login(): void {
    if (!signed_in()) { flash('Please log in to continue.'); header('Location: login.php'); exit; }
}
// Store one-time notices in the session and consume them when a page displays them.
function flash(?string $message = null): ?string {
    if ($message !== null) { $_SESSION['flash'] = $message; return null; }
    $value = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $value;
}
// Issue and verify a session token for forms and JavaScript requests that change data.
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void {
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) { http_response_code(419); exit('Your session token expired. Refresh and try again.'); }
}
// Load category options and check category IDs against the database before saving recipes.
function categories(PDO $pdo): array { return $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(); }
function category_exists(PDO $pdo, int $id): bool {
    $q = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?'); $q->execute([$id]); return (bool)$q->fetchColumn();
}
// Normalize and validate all recipe fields on the server before any insert or update.
function recipe_input(PDO $pdo): array {
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $instructions = trim((string)($_POST['instructions'] ?? ''));
    $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
    $servings = filter_var($_POST['servings'] ?? 4, FILTER_VALIDATE_INT);
    $minutes = filter_var($_POST['cook_minutes'] ?? 30, FILTER_VALIDATE_INT);
    $raw = $_POST['ingredients'] ?? [];
    $ingredients = is_array($raw) ? array_values(array_filter(array_map(static fn($v) => trim((string)$v), $raw), static fn($v) => $v !== '')) : [];
    $errors = [];
    if (mb_strlen($title) < 3 || mb_strlen($title) > 140) $errors[] = 'Title must be 3–140 characters.';
    if (mb_strlen($description) < 10 || mb_strlen($description) > 500) $errors[] = 'Description must be 10–500 characters.';
    if ($categoryId === false || !category_exists($pdo, (int)$categoryId)) $errors[] = 'Choose a valid category.';
    if (!$ingredients || count($ingredients) > 40) $errors[] = 'Add between 1 and 40 ingredients.';
    foreach ($ingredients as $ingredient) if (mb_strlen($ingredient) > 180) $errors[] = 'Each ingredient must be 180 characters or fewer.';
    if (mb_strlen($instructions) < 10 || mb_strlen($instructions) > 10000) $errors[] = 'Steps must be 10–10,000 characters.';
    if ($servings === false || $servings < 1 || $servings > 30) $errors[] = 'Servings must be from 1 to 30.';
    if ($minutes === false || $minutes < 1 || $minutes > 1440) $errors[] = 'Cooking time must be from 1 to 1,440 minutes.';
    return [['title'=>$title, 'description'=>$description, 'category_id'=>(int)$categoryId, 'instructions'=>$instructions, 'servings'=>(int)$servings, 'cook_minutes'=>(int)$minutes, 'ingredients'=>$ingredients], $errors];
}
