<?php
// Expenses API: GET (list) | POST (add) | PUT ?id= (edit) | DELETE ?id= (remove). Own rows only.
require __DIR__ . '/../config.php';
$uid = api_user(); $db = db(); $method = $_SERVER['REQUEST_METHOD']; $id = (int)($_GET['id'] ?? 0);

/** Server-side validation; returns [errors, clean values]. */
function clean_expense(array $in, int $uid): array {
    $err = [];
    $amount = $in['amount'] ?? '';
    if (!is_numeric($amount) || $amount <= 0 || $amount > 10000000) $err[] = 'Amount must be a positive number.';
    $date = (string)($in['expense_date'] ?? '');
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date)        $err[] = 'Enter a valid date.';
    elseif ($date > date('Y-m-d'))                   $err[] = 'Date cannot be in the future.';
    $cat = (int)($in['category_id'] ?? 0);
    $chk = db()->prepare('SELECT 1 FROM categories WHERE category_id=? AND user_id=?'); $chk->execute([$cat, $uid]);
    if (!$chk->fetch())                              $err[] = 'Choose a valid category.';
    $note = trim((string)($in['note'] ?? ''));
    if (len($note) > 255)                      $err[] = 'Note is too long (max 255).';
    return [$err, [round((float)$amount, 2), $date, $cat, $note !== '' ? $note : null]];
}

try {
    if ($method === 'GET') {
        $sql = 'SELECT e.expense_id, e.amount, e.expense_date, e.note, e.category_id, c.name AS category
                FROM expenses e JOIN categories c ON c.category_id=e.category_id WHERE e.user_id=?';
        $args = [$uid];
        if (!empty($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month'])) { $sql .= " AND DATE_FORMAT(e.expense_date,'%Y-%m')=?"; $args[] = $_GET['month']; }
        $st = $db->prepare($sql . ' ORDER BY e.expense_date DESC, e.expense_id DESC LIMIT 300');
        $st->execute($args);
        json_out($st->fetchAll());
    }
    if ($method === 'POST') {
        [$err, $v] = clean_expense(body_json(), $uid);
        if ($err) json_out(['error' => implode(' ', $err)], 422);
        $db->prepare('INSERT INTO expenses (user_id, amount, expense_date, category_id, note) VALUES (?,?,?,?,?)')->execute([$uid, ...$v]);
        json_out(['ok' => true, 'id' => (int)$db->lastInsertId()], 201);
    }
    if ($method === 'PUT' && $id) {
        [$err, $v] = clean_expense(body_json(), $uid);
        if ($err) json_out(['error' => implode(' ', $err)], 422);
        $st = $db->prepare('UPDATE expenses SET amount=?, expense_date=?, category_id=?, note=? WHERE expense_id=? AND user_id=?');
        $st->execute([...$v, $id, $uid]);
        json_out(['ok' => true]);
    }
    if ($method === 'DELETE' && $id) {
        $st = $db->prepare('DELETE FROM expenses WHERE expense_id=? AND user_id=?'); $st->execute([$id, $uid]);
        if (!$st->rowCount()) json_out(['error' => 'Expense not found.'], 404);
        json_out(['ok' => true]);
    }
    json_out(['error' => 'Bad request.'], 400);
} catch (Throwable $ex) { json_out(['error' => 'Server error. Please try again.'], 500); }
