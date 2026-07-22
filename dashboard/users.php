<?php
require __DIR__ . '/bootstrap.php';
require_perm('*'); // user management is Administrator-only

$pageTitle = "Users & Roles";
$currentPage = "users";
$message = ""; $messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = inp($_POST, 'action');

    if ($action === 'create' || $action === 'update') {
        $name = inp($_POST, 'name');
        $username = inp($_POST, 'username');
        $email = inp($_POST, 'email');
        $role = in_array(inp($_POST, 'role'), CX_ROLES, true) ? inp($_POST, 'role') : 'Viewer';
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $name === '') {
            $message = "Name and username are required."; $messageType = "danger";
        } elseif ($action === 'create') {
            if (strlen($password) < 6) {
                $message = "Password must be at least 6 characters."; $messageType = "danger";
            } elseif (db_val("SELECT id FROM admin_users WHERE username=?", [$username])) {
                $message = "That username is already taken."; $messageType = "danger";
            } else {
                db_exec("INSERT INTO admin_users (name, username, email, password, role, status) VALUES (?,?,?,?,?, 'active')",
                    [$name, $username, $email ?: null, password_hash($password, PASSWORD_DEFAULT), $role]);
                audit('user.create', 'admin_users', db_insert_id());
                $message = "User created."; $messageType = "success";
            }
        } else { // update
            $id = (int) inp($_POST, 'id');
            if ($password !== '') {
                if (strlen($password) < 6) {
                    $message = "Password must be at least 6 characters."; $messageType = "danger";
                } else {
                    db_exec("UPDATE admin_users SET name=?, email=?, role=?, password=? WHERE id=?",
                        [$name, $email ?: null, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
                }
            } else {
                db_exec("UPDATE admin_users SET name=?, email=?, role=? WHERE id=?", [$name, $email ?: null, $role, $id]);
            }
            if ($messageType !== 'danger') { audit('user.update', 'admin_users', $id); $message = "User updated."; $messageType = "success"; }
        }
    } elseif ($action === 'delete') {
        $id = (int) inp($_POST, 'id');
        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $message = "You can't delete your own account."; $messageType = "danger";
        } elseif ((int) db_val("SELECT COUNT(*) FROM admin_users WHERE role='Administrator'") <= 1
                  && db_val("SELECT role FROM admin_users WHERE id=?", [$id]) === 'Administrator') {
            $message = "Can't delete the last administrator."; $messageType = "danger";
        } else {
            db_exec("DELETE FROM admin_users WHERE id=?", [$id]);
            audit('user.delete', 'admin_users', $id);
            $message = "User removed."; $messageType = "success";
        }
    }
}

$users = db_all("SELECT id, name, username, email, role, status, last_login_at, created_at FROM admin_users ORDER BY id DESC");
include "includes/header.php";
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>"><?= e($message) ?></div>
<?php endif; ?>

<div class="panel">
  <div class="panel-head">
    <div><h3>Users & Roles</h3><p class="sub"><?= count($users) ?> account(s) · role-based access control</p></div>
    <button class="btn btn-primary" onclick="openUser()">+ Add User</button>
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Last Login</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u):
        $rc = $u['role']==='Administrator'?'badge-dept':($u['role']==='Manager'?'badge-late':'badge-present');
      ?>
        <tr>
          <td><div class="emp-cell"><div class="emp-avatar" style="background:linear-gradient(135deg,#6366F1,#8B5CF6)"><?= e(strtoupper(substr($u['name'] ?: $u['username'],0,1))) ?></div><div><div class="emp-name"><?= e($u['name'] ?: $u['username']) ?></div><div class="emp-id">@<?= e($u['username']) ?></div></div></div></td>
          <td><?= e($u['email'] ?: '—') ?></td>
          <td><span class="badge <?= $rc ?>"><?= e($u['role']) ?></span></td>
          <td class="mono"><?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime($u['last_login_at']))) : 'Never' ?></td>
          <td>
            <div class="row-actions">
              <button class="row-act success" title="Edit" onclick='openUser(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
              <?php if ($u['id'] != ($_SESSION['user_id'] ?? 0)): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button class="row-act danger" title="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="userModal" class="cx-modal" style="display:none">
  <div class="cx-modal-card">
    <div class="cx-modal-hd"><h3 id="umTitle">Add User</h3><button class="cx-x" onclick="closeUser()">&times;</button></div>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="umAction" value="create"><input type="hidden" name="id" id="umId" value="">
      <div class="form-panel"><div class="form-grid">
        <div class="form-group"><label>Full Name</label><input name="name" id="umName" required placeholder="Jane Doe"></div>
        <div class="form-group"><label>Username</label><input name="username" id="umUser" required placeholder="jane"></div>
        <div class="form-group"><label>Email</label><input name="email" id="umEmail" type="email" placeholder="jane@company.com"></div>
        <div class="form-group"><label>Role</label><select name="role" id="umRole"><?php foreach (CX_ROLES as $r): ?><option value="<?= $r ?>"><?= $r ?></option><?php endforeach; ?></select></div>
        <div class="form-group" style="grid-column:1/-1"><label id="umPassLbl">Password</label><input name="password" id="umPass" type="password" placeholder="At least 6 characters"></div>
      </div></div>
      <div class="form-actions"><button type="button" class="btn" onclick="closeUser()">Cancel</button><button class="btn btn-primary">Save User</button></div>
    </form>
  </div>
</div>
<style>
.cx-modal{position:fixed;inset:0;background:rgba(11,16,32,.5);backdrop-filter:blur(3px);z-index:2000;display:flex;align-items:center;justify-content:center;padding:20px}
.cx-modal-card{width:100%;max-width:600px;background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,.28);overflow:hidden}
.cx-modal-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #F1F2F6}
.cx-modal-hd h3{font-size:16px;font-weight:750}.cx-x{border:none;background:none;font-size:26px;color:#98A0B3;cursor:pointer}
</style>
<script>
function openUser(u){
  document.getElementById('userModal').style.display='flex';
  var edit=!!u;
  document.getElementById('umTitle').textContent=edit?'Edit User':'Add User';
  document.getElementById('umAction').value=edit?'update':'create';
  document.getElementById('umId').value=edit?u.id:'';
  document.getElementById('umName').value=edit?(u.name||''):'';
  document.getElementById('umUser').value=edit?u.username:'';
  document.getElementById('umUser').readOnly=edit;
  document.getElementById('umEmail').value=edit&&u.email?u.email:'';
  document.getElementById('umRole').value=edit?u.role:'Viewer';
  document.getElementById('umPass').value='';
  document.getElementById('umPassLbl').textContent=edit?'New Password (leave blank to keep)':'Password';
}
function closeUser(){document.getElementById('userModal').style.display='none';}
document.getElementById('userModal').addEventListener('click',function(e){if(e.target===this)closeUser();});
</script>

<?php include "includes/footer.php"; ?>
