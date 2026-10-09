<?php
declare(strict_types=1);

// User model: encapsulates account creation and password authentication.
final class User
{
    public function __construct(private PDO $pdo) {}

    public function register(string $name, string $email, string $plainPassword): int
    {
        $check = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetchColumn()) {
            throw new DomainException('That email is already registered.');
        }

        $insert = $this->pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $insert->execute([$name, $email, password_hash($plainPassword, PASSWORD_DEFAULT)]);
        return (int)$this->pdo->lastInsertId();
    }

    public function authenticate(string $email, string $plainPassword): ?array
    {
        $query = $this->pdo->prepare('SELECT id, name, password FROM users WHERE email = ?');
        $query->execute([$email]);
        $row = $query->fetch();

        if (!$row || !password_verify($plainPassword, $row['password'])) {
            return null;
        }

        return ['id' => (int)$row['id'], 'name' => $row['name']];
    }
}

// Recipe model: keeps recipe queries, ownership rules, and ingredient persistence together.
final class Recipe
{
    public function __construct(private PDO $pdo) {}

    public function search(int $memberId, string $term = '', ?int $categoryId = null): array
    {
        $sql = 'SELECT r.*, c.name category, u.name author,
            (SELECT COUNT(*) FROM comments cm WHERE cm.recipe_id = r.id) comment_count,
            (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) favorite_count,
            EXISTS(SELECT 1 FROM favorites mine WHERE mine.recipe_id = r.id AND mine.user_id = ?) is_favorite
            FROM recipes r JOIN categories c ON c.id = r.category_id JOIN users u ON u.id = r.user_id';
        $where = [];
        $params = [$memberId];

        if ($term !== '') {
            $where[] = '(r.title LIKE ? OR r.description LIKE ? OR EXISTS(SELECT 1 FROM ingredients i WHERE i.recipe_id = r.id AND i.ingredient LIKE ?))';
            $like = '%' . $term . '%';
            array_push($params, $like, $like, $like);
        }
        if ($categoryId) {
            $where[] = 'r.category_id = ?';
            $params[] = $categoryId;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY r.created_at DESC, r.id DESC';
        $query = $this->pdo->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }

    public function findOwned(int $recipeId, int $memberId): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM recipes WHERE id = ? AND user_id = ?');
        $query->execute([$recipeId, $memberId]);
        return $query->fetch() ?: null;
    }

    public function findForMember(int $recipeId, int $memberId): ?array
    {
        $query = $this->pdo->prepare('SELECT r.*, c.name category, u.name author,
            EXISTS(SELECT 1 FROM favorites f WHERE f.recipe_id = r.id AND f.user_id = ?) is_favorite,
            (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) favorite_count
            FROM recipes r JOIN categories c ON c.id = r.category_id JOIN users u ON u.id = r.user_id
            WHERE r.id = ?');
        $query->execute([$memberId, $recipeId]);
        return $query->fetch() ?: null;
    }

    public function ingredients(int $recipeId): array
    {
        $query = $this->pdo->prepare('SELECT ingredient FROM ingredients WHERE recipe_id = ? ORDER BY position');
        $query->execute([$recipeId]);
        return $query->fetchAll();
    }

    public function save(array $data, array $ingredients, int $memberId, ?int $recipeId = null): int
    {
        // Recheck ownership inside the model before updating an existing recipe.
        if ($recipeId !== null && !$this->findOwned($recipeId, $memberId)) {
            throw new DomainException('Recipe not found or you do not have permission to edit it.');
        }

        $this->pdo->beginTransaction();
        try {
            if ($recipeId !== null) {
                $query = $this->pdo->prepare('UPDATE recipes SET category_id = ?, title = ?, description = ?, instructions = ?, servings = ?, cook_minutes = ?, edited_at = NOW() WHERE id = ? AND user_id = ?');
                $query->execute([$data['category_id'], $data['title'], $data['description'], $data['instructions'], $data['servings'], $data['cook_minutes'], $recipeId, $memberId]);
                $this->pdo->prepare('DELETE FROM ingredients WHERE recipe_id = ?')->execute([$recipeId]);
            } else {
                $query = $this->pdo->prepare('INSERT INTO recipes (user_id, category_id, title, description, instructions, servings, cook_minutes) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $query->execute([$memberId, $data['category_id'], $data['title'], $data['description'], $data['instructions'], $data['servings'], $data['cook_minutes']]);
                $recipeId = (int)$this->pdo->lastInsertId();
            }

            $insertIngredient = $this->pdo->prepare('INSERT INTO ingredients (recipe_id, position, ingredient) VALUES (?, ?, ?)');
            foreach ($ingredients as $position => $ingredient) {
                $insertIngredient->execute([$recipeId, $position + 1, $ingredient]);
            }

            $this->pdo->commit();
            return $recipeId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function deleteOwned(int $recipeId, int $memberId): bool
    {
        $query = $this->pdo->prepare('DELETE FROM recipes WHERE id = ? AND user_id = ?');
        $query->execute([$recipeId, $memberId]);
        return $query->rowCount() > 0;
    }
}
