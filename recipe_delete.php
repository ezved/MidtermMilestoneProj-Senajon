<?php
// Recipe delete endpoint: delete only a recipe owned by the current member.
require_once __DIR__.'/helpers.php';require_login();if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: index.php');exit;}verify_csrf();$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);if($id){$q=$pdo->prepare('DELETE FROM recipes WHERE id=? AND user_id=?');$q->execute([$id,(int)$_SESSION['user']['id']]);flash($q->rowCount()?'Recipe deleted.':'Recipe not found or you do not own it.');}header('Location: index.php');exit;
