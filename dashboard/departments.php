<?php
require __DIR__ . '/bootstrap.php';

$pageTitle = "Departments";
$currentPage = "departments";

$message = ""; $messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_perm('*');
    csrf_verify();
    $action = inp($_POST, 'action');
    $name = inp($_POST, 'name');
    $code = strtoupper(inp($_POST, 'code'));
    $desc = inp($_POST, 'description');

    if ($action === 'create' || $action === 'update') {
        if ($name === '' || $code === '') {
            $message = "Name and code are required."; $messageType = "danger";
        } else {
            // uniqueness check on code
            $id = (int) inp($_POST, 'id');
            $clash = db_val("SELECT id FROM departments WHERE code=? AND id<>?", [$code, $id]);
            if ($clash) {
                $message = "That department code is already in use."; $messageType = "danger";
            } elseif ($action === 'create') {
                db_exec("INSERT INTO departments (name, code, description) VALUES (?,?,?)", [$name, $code, $desc ?: null]);
                audit('department.create', 'departments', db_insert_id());
                $message = "Department created."; $messageType = "success";
            } else {
                db_exec("UPDATE departments SET name=?, code=?, description=? WHERE id=?", [$name, $code, $desc ?: null, $id]);
                audit('department.update', 'departments', $id);
                $message = "Department updated."; $messageType = "success";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) inp($_POST, 'id');
        db_exec("DELETE FROM departments WHERE id=?", [$id]);
        audit('department.delete', 'departments', $id);
        $message = "Department deleted."; $messageType = "success";
    }
}

$rows = db_all(
    "SELECT dep.*,
        (SELECT COUNT(*) FROM employees e WHERE e.department_id = dep.id) AS emp_count,
        (SELECT COUNT(*) FROM devices d WHERE d.department_id = dep.id) AS dev_count
     FROM departments dep ORDER BY dep.name"
);

include "includes/header.php";
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>"><?= e($message) ?></div>
<?php endif; ?>

<div class="panel">
  <div class="panel-head">
    <div><h3>Departments</h3><p class="sub"><?= count($rows) ?> departments · organize your workforce</p></div>
    <?php if (can('*')): ?><button class="btn btn-primary" onclick="openDept()">+ Add Department</button><?php endif; ?>
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Department</th><th>Code</th><th>Description</th><th>Employees</th><th>Devices</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($rows): foreach ($rows as $r):
        $colors=['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'];$bg=$colors[$r['id']%6];
      ?>
        <tr>
          <td><div class="emp-cell"><div class="emp-avatar" style="background:<?= $bg ?>"><?= e(strtoupper(substr($r['name'],0,1))) ?></div><div class="emp-name"><?= e($r['name']) ?></div></div></td>
          <td><span class="badge badge-dept"><?= e($r['code']) ?></span></td>
          <td><?= e($r['description'] ?: '—') ?></td>
          <td class="mono"><?= (int)$r['emp_count'] ?></td>
          <td class="mono"><?= (int)$r['dev_count'] ?></td>
          <td>
            <?php if (can('*')): ?>
            <div class="row-actions">
              <button class="row-act success" title="Edit" onclick='openDept(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this department? Employees keep their records but lose the department link.')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="row-act danger" title="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>
              </form>
            </div>
            <?php else: ?><span style="color:#98A0B3">—</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6"><div class="empty-state"><h4>No departments</h4><p>Create departments to organize employees and devices.</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (can('*')): ?>
<div id="deptModal" class="cx-modal" style="display:none">
  <div class="cx-modal-card">
    <div class="cx-modal-hd"><h3 id="dpTitle">Add Department</h3><button class="cx-x" onclick="closeDept()">&times;</button></div>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="dpAction" value="create"><input type="hidden" name="id" id="dpId" value="">
      <div class="form-panel"><div class="form-grid">
        <div class="form-group"><label>Name</label><input name="name" id="dpName" required placeholder="e.g. Engineering"></div>
        <div class="form-group"><label>Code</label><input name="code" id="dpCode" required placeholder="e.g. ENG" style="text-transform:uppercase"></div>
        <div class="form-group" style="grid-column:1/-1"><label>Description</label><input name="description" id="dpDesc" placeholder="Short description"></div>
      </div></div>
      <div class="form-actions"><button type="button" class="btn" onclick="closeDept()">Cancel</button><button class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>
<style>
.cx-modal{position:fixed;inset:0;background:rgba(11,16,32,.5);backdrop-filter:blur(3px);z-index:2000;display:flex;align-items:center;justify-content:center;padding:20px}
.cx-modal-card{width:100%;max-width:560px;background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,.28);overflow:hidden}
.cx-modal-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #F1F2F6}
.cx-modal-hd h3{font-size:16px;font-weight:750}.cx-x{border:none;background:none;font-size:26px;color:#98A0B3;cursor:pointer}
</style>
<script>
function openDept(d){
  document.getElementById('deptModal').style.display='flex';
  var edit=!!d;
  document.getElementById('dpTitle').textContent=edit?'Edit Department':'Add Department';
  document.getElementById('dpAction').value=edit?'update':'create';
  document.getElementById('dpId').value=edit?d.id:'';
  document.getElementById('dpName').value=edit?d.name:'';
  document.getElementById('dpCode').value=edit?d.code:'';
  document.getElementById('dpDesc').value=edit&&d.description?d.description:'';
}
function closeDept(){document.getElementById('deptModal').style.display='none';}
document.getElementById('deptModal').addEventListener('click',function(e){if(e.target===this)closeDept();});
</script>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
