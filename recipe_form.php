<?php
// Recipe create/edit page: load owned recipe data, validate input, and save recipe plus ingredients atomically.
require_once __DIR__.'/helpers.php';require_login();$id=filter_var($_GET['id']??$_POST['id']??null,FILTER_VALIDATE_INT);$editing=(bool)$id;$errors=[];$cats=categories($pdo);$form=['title'=>'','description'=>'','category_id'=>0,'instructions'=>'','servings'=>4,'cook_minutes'=>30,'ingredients'=>['']];
// Load existing recipe and ingredient data only when the owner opens edit mode.
if ($editing) {
    $q = $pdo->prepare('SELECT * FROM recipes WHERE id=? AND user_id=?');
    $q->execute([$id, (int)$_SESSION['user']['id']]);
    $existing = $q->fetch();
    if (!$existing) { http_response_code(404); exit('Recipe not found or you do not have permission to edit it.'); }
    $q = $pdo->prepare('SELECT ingredient FROM ingredients WHERE recipe_id=? ORDER BY position');
    $q->execute([$id]);
    $form = array_merge($existing, ['ingredients' => array_column($q->fetchAll(), 'ingredient')]);
}

// Validate the recipe, then save it and its ingredient rows in one transaction.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$form, $errors] = recipe_input($pdo);
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            if ($editing) {
                $q = $pdo->prepare('UPDATE recipes SET category_id=?,title=?,description=?,instructions=?,servings=?,cook_minutes=?,edited_at=NOW() WHERE id=? AND user_id=?');
                $q->execute([$form['category_id'], $form['title'], $form['description'], $form['instructions'], $form['servings'], $form['cook_minutes'], $id, (int)$_SESSION['user']['id']]);
                $recipeId = (int)$id;
                $pdo->prepare('DELETE FROM ingredients WHERE recipe_id=?')->execute([$recipeId]);
            } else {
                $q = $pdo->prepare('INSERT INTO recipes(user_id,category_id,title,description,instructions,servings,cook_minutes) VALUES(?,?,?,?,?,?,?)');
                $q->execute([(int)$_SESSION['user']['id'], $form['category_id'], $form['title'], $form['description'], $form['instructions'], $form['servings'], $form['cook_minutes']]);
                $recipeId = (int)$pdo->lastInsertId();
            }
            $q = $pdo->prepare('INSERT INTO ingredients(recipe_id,position,ingredient) VALUES(?,?,?)');
            foreach ($form['ingredients'] as $position => $ingredient) $q->execute([$recipeId, $position + 1, $ingredient]);
            $pdo->commit();
            flash($editing ? 'Recipe updated.' : 'Recipe shared!');
            header('Location: recipe.php?id=' . $recipeId);
            exit;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
$pageTitle=$editing?'Edit recipe':'Share a recipe';require __DIR__.'/header.php';?>
<section class="form-page"><a class="back-link" href="<?= $editing?'recipe.php?id='.(int)$id:'index.php' ?>">← Back</a><p class="eyebrow">PASS IT AROUND</p><h1><?= $editing?'Give it a little update.':'What’s on your table?' ?></h1><p class="muted">Recipes are even better when they come with a story.</p><?php if($errors): ?><div class="form-errors"><?php foreach($errors as $e): ?><p><?= h($e) ?></p><?php endforeach; ?></div><?php endif; ?><form method="post" class="recipe-form" data-ingredient-form><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><?php if($editing): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?><div class="form-section"><span class="step-no">01</span><div class="form-fields"><h2>The dish</h2><label>Recipe title<input name="title" required minlength="3" maxlength="140" value="<?= h((string)$form['title']) ?>" placeholder="e.g. Lola's Sunday adobo"></label><label>A little about it<textarea name="description" required minlength="10" maxlength="500" rows="3" placeholder="What makes this recipe special? (10–500 characters)"><?= h((string)$form['description']) ?></textarea></label><div class="two-fields"><label>Category<select name="category_id" required><option value="">Choose a category</option><?php foreach($cats as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)$form['category_id']===(int)$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option><?php endforeach; ?></select></label><label>Cooking time (minutes)<input type="number" name="cook_minutes" min="1" max="1440" required value="<?= h((string)$form['cook_minutes']) ?>"></label></div><label>Serves<input type="number" name="servings" min="1" max="30" required value="<?= h((string)$form['servings']) ?>"></label></div></div><div class="form-section"><span class="step-no">02</span><div class="form-fields"><h2>What you’ll need</h2><p class="muted">One ingredient per line. Add as many as you need.</p><div class="ingredient-list" data-ingredient-list><?php foreach((array)$form['ingredients'] as $ingredient): ?><div class="ingredient-row"><span class="ingredient-bullet">·</span><input name="ingredients[]" required maxlength="180" value="<?= h((string)$ingredient) ?>" placeholder="e.g. 1 kg chicken thighs"><button type="button" class="remove-ingredient" aria-label="Remove ingredient">×</button></div><?php endforeach; ?></div><button type="button" class="add-ingredient" data-add-ingredient>＋ Add an ingredient</button></div></div><div class="form-section"><span class="step-no">03</span><div class="form-fields"><h2>How to make it</h2><label>Cooking steps<textarea name="instructions" required minlength="10" maxlength="10000" rows="9" placeholder="Write each step on a new line…"><?= h((string)$form['instructions']) ?></textarea><small>Separate steps with a new line for easy reading.</small></label></div></div><div class="form-actions"><button class="button button-dark"><?= $editing?'Save changes':'Share recipe' ?> <span>→</span></button><span class="muted">You can always update your own recipes.</span></div></form></section>
<?php require __DIR__.'/footer.php';?>
