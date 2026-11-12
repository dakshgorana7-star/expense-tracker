# Expense Tracker with Spending Insights

A PHP + MySQL web app: log in, record expenses, see charts, get budget alerts and a forecast of next month's spend.
Built with HTML5, CSS3 (Flexbox/Grid), vanilla JavaScript (fetch/AJAX + JSON), Chart.js, PHP (PDO) and MySQL.

## Run it (XAMPP)
1. Install XAMPP, start **Apache** and **MySQL** in the control panel.
2. Copy this folder to `C:\xampp\htdocs\expense-tracker` (macOS/Linux: the `htdocs` folder of your stack).
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) -> **Import** -> choose `database.sql` -> Go.
4. If your MySQL user/password is not `root` with an empty password, edit the top of `config.php`.
5. Open `http://localhost/expense-tracker/seed.php` once to load demo data (6 months of expenses).
6. Open `http://localhost/expense-tracker/` and log in with **demo@example.com / demo1234**, or register a new account.

Charts load Chart.js from a CDN, so the browser needs internet access.
No XAMPP? From this folder run `php -S localhost:8000` and open `http://localhost:8000`.

## Folder structure
```
config.php            DB connection (PDO), session, CSRF, helpers
database.sql          schema: users, categories, expenses
seed.php              demo user + sample data (safe to re-run)
login/register/logout.php, index.php
dashboard.php  expenses.php  categories.php  insights.php     (pages)
api/expenses.php      GET list | POST add | PUT ?id= edit | DELETE ?id=
api/categories.php    GET | POST | PUT ?id= | DELETE ?id=
api/summary.php       dashboard totals and chart data
api/predict.php       forecast + insight sentences
includes/             header.php, footer.php, analytics.php (forecast + insights logic)
assets/css/style.css  assets/js/app.js
```

## How it works
- **Auth:** passwords are hashed with bcrypt (`password_hash`). Sessions are regenerated on login. Every page and API route checks the session; write requests also need a CSRF token.
- **Data isolation:** every query includes `user_id = ?` from the session, so users only see their own rows.
- **Security:** PDO prepared statements only, output escaped (`htmlspecialchars` / JS `esc()`), input validated on client and server.
- **Charts:** the page fetches JSON from `api/summary.php` and draws a pie (category split), line (monthly totals) and bar (budget vs actual).
- **Prediction** (`includes/analytics.php`), using **completed months only** (the running month is partial):
  1. *Moving average* = average of the last 3 months.
  2. *Linear regression* = least-squares line `y = m*x + b` over monthly totals (x = 1..n), forecast at x = n+1.
  With fewer than 3 months the moving average uses what exists and a note is shown; regression needs 2+ months.
- **Insights:** top category, month-over-month change per category, and over/near-budget alerts for the current month.

## Database
`users(user_id, name, email UNIQUE, password_hash)` 1-N `categories(category_id, user_id FK, name, budget_limit)`;
`expenses(expense_id, user_id FK, category_id FK, amount, expense_date, note)`.
A category with expenses cannot be deleted (foreign key RESTRICT, with a clear message).

## Deploy
Any PHP + MySQL host works (for example InfinityFree or 000webhost): upload the files, import `database.sql`, update `config.php`, then delete `seed.php`.
