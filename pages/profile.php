<?php
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM hosting_orders WHERE user_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$activeHostingCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(balance_used) FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalSpent = $stmt->fetchColumn() ?? 0;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">โปรไฟล์</h1>
</div>

<div class="row">
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">ข้อมูลผู้ใช้</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img class="img-profile rounded-circle mb-3" src="img/undraw_profile.svg" style="width: 150px;">
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['realname'] . ' ' . $user['surname']); ?></h5>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-6 text-center border-right">
                        <div class="h5 mb-0">฿<?php echo number_format($user['balance'], 2); ?></div>
                        <small class="text-muted">ยอดเงินคงเหลือ</small>
                    </div>
                    <div class="col-6 text-center">
                        <div class="h5 mb-0">฿<?php echo number_format($totalSpent, 2); ?></div>
                        <small class="text-muted">ยอดใช้จ่ายทั้งหมด</small>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted">อีเมล</small>
                    <div><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">วันที่สมัคร</small>
                    <div><?php echo date('d/m/Y', strtotime($user['date_register'])); ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">เข้าสู่ระบบล่าสุด</small>
                    <div><?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?></div>
                </div>
                <button class="btn btn-primary btn-block" onclick="showChangePasswordModal()">
                    <i class="fas fa-key fa-sm fa-fw mr-2"></i>เปลี่ยนรหัสผ่าน
                </button>
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-lg-7">
        <div class="row">
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Hosting ที่ใช้งานอยู่</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $activeHostingCount; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-server fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    ยอดเงินคงเหลือ</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">฿<?php echo number_format($user['balance'], 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-wallet fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">กิจกรรมล่าสุด</h6>
            </div>
            <div class="card-body">
                <!-- Add recent activities here -->
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เปลี่ยนรหัสผ่าน</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label>รหัสผ่านปัจจุบัน</label>
                        <input type="password" class="form-control" id="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label>รหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="newPassword" required>
                        <small class="form-text text-muted">
                            รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวอักษรพิมพ์ใหญ่, พิมพ์เล็ก, ตัวเลข และตัวอักษรพิเศษ
                        </small>
                    </div>
                    <div class="form-group">
                        <label>ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="confirmPassword" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">ยกเลิก</button>
                <button class="btn btn-primary" type="button" onclick="changePassword()">เปลี่ยนรหัสผ่าน</button>
            </div>
        </div>
    </div>
</div>

<script>
function showChangePasswordModal() {
    $('#changePasswordForm')[0].reset();
    $('#changePasswordModal').modal('show');
}

function changePassword() {
    const currentPassword = $('#currentPassword').val();
    const newPassword = $('#newPassword').val();
    const confirmPassword = $('#confirmPassword').val();

    if (newPassword.length < 8) {
        Swal.fire('Error', 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร', 'error');
        return;
    }

    if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[a-zA-Z\d!@#$%^&*]{8,}$/.test(newPassword)) {
        Swal.fire('Error', 'รหัสผ่านต้องประกอบด้วยตัวอักษรพิมพ์ใหญ่, พิมพ์เล็ก, ตัวเลข, ตัวอักษรพิเศษ', 'error');
        return;
    }

    if (newPassword !== confirmPassword) {
        Swal.fire('Error', 'รหัสผ่านไม่ตรงกัน', 'error');
        return;
    }

    $.ajax({
        url: 'api/user.php',
        type: 'POST',
        data: {
            action: 'changePassword',
            currentPassword: currentPassword,
            newPassword: newPassword
        },
        success: function(response) {
            if (response.status === 'success') {
                $('#changePasswordModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว'
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}
</script>
