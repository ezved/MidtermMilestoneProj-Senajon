# Kusina ng Barangay

A responsive neighborhood recipe-sharing site built with plain PHP, sessions, OOP, PDO, HTML, CSS, and JavaScript.

## Run locally with XAMPP

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open phpMyAdmin, import `database.sql`, and confirm the `barangay_kusina` database was created.
3. If your local MySQL credentials differ from the XAMPP defaults, update the connection settings in `db.php`.
4. Visit `http://localhost/MidtermMilestoneProj-Senajon/` and register an account.


## Data model

The database contains exactly six application tables: `users`, `categories`, `recipes`, `ingredients`, `comments`, and `favorites`. Foreign keys cascade when a member or recipe is deleted, while the category reference is restricted to valid seeded categories. Favorites use a composite primary key to prevent duplicate saves. Recipe and comment edits record an `edited_at` timestamp.

Member pages require a session. All writes use POST plus a session CSRF token; database queries use prepared statements, passwords use PHP's password hashing functions, and user-provided text is escaped when rendered. Members can edit or delete only recipes and comments they own. The ingredient checklist is the site's extra cooking helper: check off items as you prepare them.
