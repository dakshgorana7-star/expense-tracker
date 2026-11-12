<?php
// Categories API: GET | POST | PUT ?id= | DELETE ?id=  (own rows only)
require __DIR__ . '/../config.php';
$uid = api_user(); $db = db(); $method = $_SERVER['REQUEST_METHOD']; $id = (int)($_GET['id'] ?? 0);

function clean_cat(array $in): array {
    $err = []; $name = trim((string)($in['name'] ?? ''));
    if ($name === '' || len($name) > 40) $err[] = 'Category name is required (max 40 characters).';
    $b = $in['budget_limit'] ?? null; $b = ($b === '' || $b === null) ? null : $b;
    if ($b !== null && (!is_numeric($b) || $b < 0 || $b > 10000000)) $err[] = 'Budget must be zero or a positive number.';
    return [$err, [$name, $b === null ? null : round((float)$b, 2)]];
}

try {
    if ($method === 'GET') {
        $st = $db->prepare("SELECT c.category_id, c.name, c.budget_limit, COALESCE(SUM(e.amount),0) AS spent
                            FROM categories c LEFT JOIN expenses e ON e.category_id=c.category_id AND DATE_FORMAT(e.expense_date,'%Y-%m')=?
                            WHERE c.user_id=? GROUP BY c.category_id, c.name, c.budget_limit ORDER BY c.name");
        $st->execute([date('Y-m'), $uid]);
        json_out($st->fetchAll());
    }
    if ($method === 'POST' || ($method === 'PUT' && $id)) {
        [$err, $v] = clean_cat(body_json());
        if ($err) json_out(['error' => implode(' ', $err)], 422);
        try {
            if ($method === 'POST') $db->prepare('INSERT INTO categories (user_id, name, budget_limit) VALUES (?,?,?)')->execute([$uid, ...$v]);
            else $db->prepare('UPDATE categories SET name=?, budget_limit=? WHERE category_id=? AND user_id=?')->execute([...$v, $id, $uid]);
        } catch (PDOException $ex) {
            if ($ex->getCode() == 23000) json_out(['error' => 'You already have a category with that name.'], 409);
            throw $ex;
        }
        json_out(['ok' => true], $method === 'POST' ? 201 : 200);
    }
    if ($method === 'DELETE' && $id) {
        $c = $db->prepare('SELECT COUNT(*) FROM expenses WHERE category_id=? AND user_id=?'); $c->execute([$id, $uid]);
        if ($c->fetchColumn() > 0) json_out(['error' => 'This category has expenses. Delete or move them first.'], 409);
        $st = $db->prepare('DELETE FROM categories WHERE category_id=? AND user_id=?'); $st->execute([$id, $uid]);
        if (!$st->rowCount()) json_out(['error' => 'Category not found.'], 404);
        json_out(['ok' => true]);
    }
    json_out(['error' => 'Bad request.'], 400);
} catch (Throwable $ex) { json_out(['error' => 'Server error. Please try again.'], 500); }
