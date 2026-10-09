USE barangay_kusina;

-- Import this optional sample data after at least one member account exists.
-- Recipes are assigned to the first registered member and duplicate titles are skipped.
SET @sample_user_id = (SELECT id FROM users ORDER BY id LIMIT 1);

-- Add three Filipino home-cooking examples for the first member.
INSERT INTO recipes (user_id, category_id, title, description, instructions, servings, cook_minutes)
SELECT @sample_user_id, c.id, 'Chicken Adobo', 'A savory-sour family classic with garlic, soy, and vinegar.',
       'Brown the chicken with garlic.\nAdd soy sauce, vinegar, bay leaves, pepper, and water.\nSimmer covered until tender, then uncover and reduce the sauce.', 4, 50
FROM categories c
WHERE c.name = 'Ulam' AND @sample_user_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM recipes WHERE user_id = @sample_user_id AND title = 'Chicken Adobo');

INSERT INTO recipes (user_id, category_id, title, description, instructions, servings, cook_minutes)
SELECT @sample_user_id, c.id, 'Pork Sinigang', 'A comforting tamarind broth with pork and fresh vegetables.',
       'Boil the pork with onion and tomato until tender.\nAdd tamarind mix and radish, then simmer for a few minutes.\nAdd sitaw, kangkong, and green chili. Cook until the vegetables are tender.', 6, 75
FROM categories c
WHERE c.name = 'Sabaw' AND @sample_user_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM recipes WHERE user_id = @sample_user_id AND title = 'Pork Sinigang');

INSERT INTO recipes (user_id, category_id, title, description, instructions, servings, cook_minutes)
SELECT @sample_user_id, c.id, 'Banana Turon', 'Crispy saba banana rolls with a caramelized sugar coating.',
       'Roll each banana piece in brown sugar, then wrap it in a lumpia wrapper.\nSeal the edge with a little water.\nPan-fry until golden and crisp, then drain before serving.', 6, 30
FROM categories c
WHERE c.name = 'Merienda' AND @sample_user_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM recipes WHERE user_id = @sample_user_id AND title = 'Banana Turon');

-- Add Chicken Adobo ingredients in display order.
INSERT INTO ingredients (recipe_id, position, ingredient)
SELECT r.id, seed.position, seed.ingredient
FROM recipes r
JOIN (
  SELECT 1 position, '1 kg chicken pieces' ingredient UNION ALL
  SELECT 2, '1/2 cup soy sauce' UNION ALL
  SELECT 3, '1/3 cup vinegar' UNION ALL
  SELECT 4, '1 cup water' UNION ALL
  SELECT 5, '6 garlic cloves, crushed' UNION ALL
  SELECT 6, '2 bay leaves and 1 tsp peppercorns'
) seed
WHERE r.user_id = @sample_user_id AND r.title = 'Chicken Adobo'
  AND NOT EXISTS (SELECT 1 FROM ingredients i WHERE i.recipe_id = r.id AND i.position = seed.position);

-- Add Pork Sinigang ingredients in display order.
INSERT INTO ingredients (recipe_id, position, ingredient)
SELECT r.id, seed.position, seed.ingredient
FROM recipes r
JOIN (
  SELECT 1 position, '1 kg pork ribs' ingredient UNION ALL
  SELECT 2, '1 onion, quartered' UNION ALL
  SELECT 3, '2 tomatoes, quartered' UNION ALL
  SELECT 4, '1 packet tamarind soup mix' UNION ALL
  SELECT 5, '1 radish, sliced' UNION ALL
  SELECT 6, '2 cups sitaw and kangkong'
) seed
WHERE r.user_id = @sample_user_id AND r.title = 'Pork Sinigang'
  AND NOT EXISTS (SELECT 1 FROM ingredients i WHERE i.recipe_id = r.id AND i.position = seed.position);

-- Add Banana Turon ingredients in display order.
INSERT INTO ingredients (recipe_id, position, ingredient)
SELECT r.id, seed.position, seed.ingredient
FROM recipes r
JOIN (
  SELECT 1 position, '6 saba bananas, halved' ingredient UNION ALL
  SELECT 2, '6 lumpia wrappers' UNION ALL
  SELECT 3, '1/2 cup brown sugar' UNION ALL
  SELECT 4, 'Cooking oil for frying'
) seed
WHERE r.user_id = @sample_user_id AND r.title = 'Banana Turon'
  AND NOT EXISTS (SELECT 1 FROM ingredients i WHERE i.recipe_id = r.id AND i.position = seed.position);
