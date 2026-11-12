<?php require 'config.php'; require_login();
$title = 'Categories & Budgets'; $page = 'categories'; include 'includes/header.php'; ?>
<h1 class="page-title">Categories &amp; Budgets</h1>
<section class="card">
  <h2>Add category</h2>
  <form id="cat-form" class="form-row" novalidate>
    <label class="grow">Name<input type="text" name="name" maxlength="40" required></label>
    <label>Monthly budget (₹)<input type="number" name="budget_limit" min="0" step="1" placeholder="Optional"></label>
    <button class="btn btn-primary" type="submit">Add</button>
  </form>
  <div class="form-error" id="cat-error" aria-live="polite"></div>
</section>
<section class="card">
  <h2>Your categories</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Category</th><th class="num">Budget</th><th>This month</th><th></th></tr></thead>
    <tbody id="cat-body"></tbody>
  </table></div>
</section>

<dialog id="edit-dlg"><form id="edit-form" novalidate>
  <h2>Edit category</h2>
  <label>Name<input type="text" name="name" maxlength="40" required></label>
  <label>Monthly budget (₹)<input type="number" name="budget_limit" min="0" step="1" placeholder="Optional"></label>
  <div class="form-error" id="edit-error"></div>
  <div class="dlg-actions"><button type="button" class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
</form></dialog>
<?php include 'includes/footer.php'; ?>
