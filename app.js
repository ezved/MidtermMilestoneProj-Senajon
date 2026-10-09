// Bind interactive controls after the page markup is available.
document.addEventListener('DOMContentLoaded', () => {
  // Read the per-session token rendered in the shared page header for write requests.
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

  // Keep interface prompts short while leaving member recipes and comments untouched.
  const heroIntro = document.querySelector('.hero-copy > p:not(.eyebrow)');
  if (heroIntro) heroIntro.textContent = 'Recipes from neighbors, made for sharing.';
  const searchInput = document.querySelector('.searchbox input');
  if (searchInput) searchInput.placeholder = 'Search dishes or ingredients';
  const filtersForm = document.querySelector('.filters');
  const submitFilters = () => {
    if (!filtersForm) return;
    const query = new URLSearchParams(new FormData(filtersForm)).toString();
    // Restore the member to the filter controls after the server returns updated results.
    sessionStorage.setItem('recipeFilterScrollY', String(window.scrollY));
    window.location.assign(query ? `${filtersForm.action || location.pathname}?${query}` : location.pathname);
  };
  // Return to the same filter area after a category or keyword update reloads results.
  const savedFilterScroll = sessionStorage.getItem('recipeFilterScrollY');
  if (savedFilterScroll !== null) {
    sessionStorage.removeItem('recipeFilterScrollY');
    window.scrollTo(0, Number(savedFilterScroll) || 0);
  }
  // Add an inline clear control and preserve the selected category when clearing a search.
  if (searchInput && filtersForm) {
    const clearSearch = document.createElement('button');
    clearSearch.type = 'button';
    clearSearch.className = 'search-clear';
    clearSearch.setAttribute('aria-label', 'Clear search');
    clearSearch.title = 'Clear search';
    clearSearch.textContent = '×';
    clearSearch.hidden = false;
    searchInput.insertAdjacentElement('afterend', clearSearch);
    searchInput.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); submitFilters(); } });
    clearSearch.addEventListener('click', () => { searchInput.value = ''; submitFilters(); });
  }
  const searchButton = document.querySelector('.filters button');
  if (searchButton) searchButton.remove();
  // Apply a category filter as soon as the member changes the dropdown.
  const categorySelect = document.querySelector('.filters select[name="category"]');
  if (categorySelect) categorySelect.addEventListener('change', submitFilters);

  const recipeHeading = document.querySelector('.discover .section-heading h2');
  if (recipeHeading && recipeHeading.textContent.trim() === 'What’s cooking') recipeHeading.textContent = 'Latest recipes';
  const authIntro = document.querySelector('.auth-panel > .muted');
  if (authIntro) authIntro.textContent = document.querySelector('.auth-panel h2')?.textContent.includes('Join')
    ? 'Join to browse and share recipes.'
    : 'Log in to see the latest recipes.';
  const noRecipesText = document.querySelector('.empty-state > p');
  if (noRecipesText) noRecipesText.textContent = 'Try another search or share a recipe.';

  // Replace recipe edit and delete text with labeled icon-only controls.
  const editRecipe = document.querySelector('.detail-actions a.text-action');
  if (editRecipe) {
    editRecipe.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>';
    editRecipe.setAttribute('aria-label', 'Edit recipe');
    editRecipe.title = 'Edit recipe';
  }
  const deleteRecipe = document.querySelector('.detail-actions form button.text-action');
  if (deleteRecipe) {
    deleteRecipe.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>';
    deleteRecipe.setAttribute('aria-label', 'Delete recipe');
    deleteRecipe.title = 'Delete recipe';
  }
  // Use compact icon buttons for the current member's comment edit and delete controls.
  document.querySelectorAll('.comment-controls a').forEach(editComment => {
    editComment.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>';
    editComment.setAttribute('aria-label', 'Edit comment');
    editComment.title = 'Edit comment';
  });
  document.querySelectorAll('.comment-controls form button').forEach(deleteComment => {
    deleteComment.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>';
    deleteComment.setAttribute('aria-label', 'Delete comment');
    deleteComment.title = 'Delete comment';
  });

  // Add ingredient inputs dynamically while enforcing the same 40-item limit as PHP.
  document.querySelectorAll('[data-add-ingredient]').forEach(button => {
    button.addEventListener('click', () => {
      const list = document.querySelector('[data-ingredient-list]');
      if (!list || list.querySelectorAll('.ingredient-row').length >= 40) return;
      const row = document.createElement('div'); row.className = 'ingredient-row';
      row.innerHTML = '<span class="ingredient-bullet">·</span><input name="ingredients[]" required maxlength="180" placeholder="Add another ingredient"><button type="button" class="remove-ingredient" aria-label="Remove ingredient">×</button>';
      list.append(row); row.querySelector('input').focus();
    });
  });
  // Delegate remove and favorite clicks so newly added ingredient rows work too.
  document.addEventListener('click', e => {
    const remove = e.target.closest('.remove-ingredient');
    if (remove) {
      const list = remove.closest('[data-ingredient-list]');
      if (list.querySelectorAll('.ingredient-row').length > 1) remove.closest('.ingredient-row').remove();
      else { const input = remove.parentElement.querySelector('input'); input.value = ''; input.focus(); }
    }
    const favorite = e.target.closest('[data-favorite]');
    if (favorite) toggleFavorite(favorite);
  });
  // Toggle a saved recipe through the JSON endpoint and update the page without reloading.
  async function toggleFavorite(button) {
    if (button.disabled) return;
    button.disabled = true;
    try {
      const response = await fetch('favorite_toggle.php', { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-Token':token}, body: JSON.stringify({recipe_id: Number(button.dataset.favorite)}) });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'Could not update favorite.');
      button.setAttribute('aria-pressed', String(result.saved)); button.classList.toggle('is-favorite', result.saved);
      if (button.classList.contains('favorite-toggle')) button.textContent = result.saved ? '♥ Saved' : '♡ Save recipe';
      if (button.closest('.card-bottom')) { const counts = button.closest('.recipe-card').querySelector('.card-meta span:last-child'); if (counts) counts.textContent = `♡ ${result.count}`; }
      document.querySelectorAll('[data-favorite-count]').forEach(node => node.textContent = result.count);
      if (!result.saved && location.pathname.endsWith('favorites.php')) button.closest('.recipe-card')?.remove();
    } catch (error) { window.alert(error.message); }
    finally { button.disabled = false; }
  }
  // Ask for confirmation before irreversible deletes and let cooks check off ingredients.
  document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', e => { if (!window.confirm(form.dataset.confirm)) e.preventDefault(); }));
  document.querySelectorAll('.ingredient-checklist input[type="checkbox"]').forEach(box => box.addEventListener('change', () => box.closest('li').classList.toggle('checked', box.checked)));
});
