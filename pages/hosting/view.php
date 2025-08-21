<?php
$hostingId = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT 
        ho.*, 
        hp.name AS package_name, 
        hp.*, 
        hc.* 
    FROM hosting_orders ho 
    JOIN hosting_packages hp ON ho.package_id = hp.id 
    JOIN hosting_categories hc ON hp.category_id = hc.id 
    WHERE ho.id = ? AND ho.user_id = ?
"); 

$stmt->execute([$hostingId, $_SESSION['user_id']]);
$hosting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hosting) {
    header('Location: ?p=hosting-manage');
    exit();
}
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">รอดำเนินการ</span>',
        'active' => '<span class="badge badge-success">ใช้งานอยู่</span>',
        'suspended' => '<span class="badge badge-danger">ระงับการใช้งาน</span>',
        'cancelled' => '<span class="badge badge-secondary">ยกเลิก</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">ไม่ทราบสถานะ</span>';
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <a href="?p=hosting-manage" class="btn btn-sm btn-outline-primary mr-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        รายละเอียด Hosting
    </h1>
    <?php if ($hosting['status'] === 'active'): ?>
    <a href="#" class="btn btn-sm btn-primary shadow-sm" onclick="showRenewalModal(<?php echo $_GET['id']; ?>)">
        <i class="fas fa-sync-alt fa-sm text-white-50 mr-1"></i> ต่ออายุ
    </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">ข้อมูลทั่วไป</h6>
                <div><?php echo getStatusBadge($hosting['status']); ?></div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-5">โดเมน:</div>
                    <div class="col-7 font-weight-bold"><?php echo htmlspecialchars($hosting['domain']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-5">แพ็คเกจ:</div>
                    <div class="col-7"><?php echo htmlspecialchars($hosting['package_name']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-5">ราคา/เดือน:</div>
                    <div class="col-7">฿<?php echo number_format($hosting['price_monthly'], 2); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-5">วันหมดอายุ:</div>
                    <div class="col-7">
                        <?php 
                        echo date('d/m/Y', strtotime($hosting['end_date']));
                        $end_date = new DateTime($hosting['end_date']);
                        $now = new DateTime();
                        $diff = $now->diff($end_date);
                        echo '<br><small class="text-muted">เหลือ ' . $diff->days . ' วัน</small>';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">ข้อมูลเข้าใช้งาน</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-5">Control Panel:</div>
                    <div class="col-7">
                        <a href="https://<?php echo htmlspecialchars($hosting['directadmin_url']); ?>:2222" 
                        target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt mr-1"></i> เปิด DirectAdmin
                        </a>
                    </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5">Username:</div>
                        <div class="col-7">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($hosting['hosting_username']); ?>" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyToClipboard(this)" data-copy="<?php echo htmlspecialchars($hosting['hosting_username']); ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5">Password:</div>
                        <div class="col-7">
                            <div class="input-group">
                                <input type="password" class="form-control form-control-sm" value="<?php echo htmlspecialchars($hosting['hosting_password']); ?>" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="togglePassword(this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyToClipboard(this)" data-copy="<?php echo htmlspecialchars($hosting['hosting_password']); ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5">IP:</div>
                        <div class="col-7">
                            <div class="mb-1">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($hosting['directadmin_ip']); ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyToClipboard(this)" data-copy="<?php echo htmlspecialchars($hosting['directadmin_ip']); ?>">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5">Nameservers:</div>
                        <div class="col-7">
                            <div class="mb-1">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($hosting['ns1']); ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyToClipboard(this)" data-copy="<?php echo htmlspecialchars($hosting['ns1']); ?>">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($hosting['ns2']); ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyToClipboard(this)" data-copy="<?php echo htmlspecialchars($hosting['ns2']); ?>">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    </div>
                    </div>

                    </div>

                    <?php include 'pages/hosting/_renewal_modal.php'; ?>

                    <script>
                    function copyToClipboard(button) {
                        const textToCopy = button.getAttribute('data-copy');
                        navigator.clipboard.writeText(textToCopy).then(function() {
                            const originalIcon = button.innerHTML;
                            button.innerHTML = '<i class="fas fa-check text-success"></i>';
                            setTimeout(() => {
                                button.innerHTML = originalIcon;
                            }, 1500);
                        });
                    }

                    function togglePassword(button) {
                        const input = button.parentElement.previousElementSibling;
                        const icon = button.querySelector('i');
                        
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    }
                    </script>
