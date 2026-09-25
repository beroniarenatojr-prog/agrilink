<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/FarmerLogistics.php';

requireRole('farmer');

$pdo = Database::getInstance();
$model = new FarmerLogistics($pdo);

$user = currentUser();
$farmerId = (int) $user['id'];

$people = $model->listLogisticsUsersForFarmer($farmerId);

$pageTitle = 'Logistics';
$activeNav = 'logistics';

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Logistics</h1>
        <p class="page-subtitle mb-0">Manage your logistics personnel</p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-agri" data-bs-toggle="modal" data-bs-target="#logisticsModal"
            data-mode="create">
            <i class="ti ti-plus"></i> Add New Logistics
        </button>
        <a href="<?= url('/farmer/orders.php') ?>" class="btn btn-agri-outline">View orders to assign</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <?php if (empty($people)): ?>
            <div class="card-body">
                <div class="empty-state">
                    <h2>No logistics personnel yet</h2>
                    <p>Add your first logistics account to start managing deliveries.</p>
                    <button type="button" class="btn btn-agri" data-bs-toggle="modal" data-bs-target="#logisticsModal"
                        data-mode="create">
                        Add Logistics
                    </button>
                </div>
            </div>
        <?php else: ?>
            <table class="table table-agri align-middle mb-0">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($people as $p): ?>
                        <tr>
                            <td class="fw-600"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($p['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($p['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ((int) ($p['is_active'] ?? 0) === 1): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                <?php else: ?>
                                    <span
                                        class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-end flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                        data-bs-target="#logisticsModal" data-mode="edit" data-id="<?= (int) $p['id'] ?>"
                                        data-name="<?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-email="<?= htmlspecialchars($p['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-phone="<?= htmlspecialchars($p['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-address="<?= htmlspecialchars($p['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-is_active="<?= htmlspecialchars($p['is_active'] ?? 0) ? 1 : 0 ?>">
                                        <i class="ti ti-pencil"></i> Edit
                                    </button>

                                    <form method="post" action="<?= url('/farmer/logistics/delete.php') ?>"
                                        data-vex-confirm="Delete this logistics personnel? This cannot be undone."
                                        data-vex-confirm-variant="danger">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="logistics_user_id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal (Add/Edit) -->
<div class="modal fade" id="logisticsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="logisticsForm" method="post" action="<?= url('/farmer/logistics/store.php') ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="mode" value="create">
                <input type="hidden" name="logistics_user_id" value="">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="logisticsModalTitle">Add Logistics</h5>
                        <div class="modal-subtitle text-muted" style="font-size:0.875rem">Create and manage logistics
                            personnel</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password (optional)</label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password">
                        <div class="text-muted" style="font-size:0.8rem;margin-top:0.25rem">Leave blank to keep the
                            current password.</div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-agri-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-agri" id="logisticsSubmitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
(function(){
  const modalEl = document.getElementById("logisticsModal");
  if(!modalEl) return;

  const form = document.getElementById("logisticsForm");
  const title = document.getElementById("logisticsModalTitle");
  const submitBtn = document.getElementById("logisticsSubmitBtn");

  function setMode(mode){
    const modeInput = form.querySelector("input[name=mode]");
    const logisticsUserIdInput = form.querySelector("input[name=logistics_user_id]");
    modeInput.value = mode;

    if(mode === "create"){
      title.textContent = "Add Logistics";
      submitBtn.textContent = "Save";
      form.action = "' . url('/farmer/logistics/store.php') . '";
      logisticsUserIdInput.value = "";
    } else {
      title.textContent = "Edit Logistics";
      submitBtn.textContent = "Update";
      form.action = "' . url('/farmer/logistics/update.php') . '";
    }
  }

  modalEl.addEventListener("show.bs.modal", function(event){
    const btn = event.relatedTarget;
    if(!btn) return;

    const mode = btn.getAttribute("data-mode") || "create";
    setMode(mode);

    const logisticsUserIdInput = form.querySelector("input[name=logistics_user_id]");
    logisticsUserIdInput.value = btn.getAttribute("data-id") || "";

    form.querySelector("input[name=name]").value = btn.getAttribute("data-name") || "";
    form.querySelector("input[name=email]").value = btn.getAttribute("data-email") || "";
    form.querySelector("input[name=phone]").value = btn.getAttribute("data-phone") || "";
    form.querySelector("textarea[name=address]").value = btn.getAttribute("data-address") || "";

    const isActive = (btn.getAttribute("data-is_active") || "1") === "1";
    form.querySelector("input[name=is_active]").checked = isActive;

    const pwdInput = form.querySelector("input[name=password]");
    if(pwdInput) pwdInput.value = "";
  });
})();
</script>';

require_once __DIR__ . '/../includes/sidebar_end.php';
?>