<?php
// Fetch hosting orders for current user
$stmt = $pdo->prepare("
    SELECT ho.*, hp.name as package_name, hp.price_monthly, hc.name as category_name 
    FROM hosting_orders ho 
    JOIN hosting_packages hp ON ho.package_id = hp.id 
    JOIN hosting_categories hc ON hp.category_id = hc.id 
    WHERE ho.user_id = ? 
    ORDER BY ho.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$hostings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get status badge
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

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">จัดการ Hosting</h1>
    <a href="?p=hosting-order" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> เช่า Hosting เพิ่ม
    </a>
</div>

<!-- Hosting List -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">รายการ Hosting ของคุณ</h6>
    </div>
    <div class="card-body">
        <?php if (empty($hostings)): ?>
        <div class="text-center py-5">
            <img src="img/empty.svg" alt="No hosting" style="width: 200px; margin-bottom: 20px;">
            <h5>คุณยังไม่มี Hosting</h5>
            <p class="text-muted">คลิกปุ่ม "เช่า Hosting เพิ่ม" เพื่อเริ่มใช้งาน</p>
            <a href="?p=hosting-order" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> เช่า Hosting เพิ่ม
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered" id="hostingTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>โดเมน</th>
                        <th>แพ็คเกจ</th>
                        <th>ราคา/เดือน</th>
                        <th>วันหมดอายุ</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hostings as $hosting): ?>
                    <tr>
                        <td>
                            <div class="font-weight-bold"><?php echo htmlspecialchars($hosting['domain']); ?></div>
                            <small class="text-muted">Username: <?php echo htmlspecialchars($hosting['hosting_username']); ?></small>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($hosting['package_name']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($hosting['category_name']); ?></small>
                        </td>
                        <td>฿<?php echo number_format($hosting['price_monthly'], 2); ?></td>
                        <td>
                            <?php 
                            $end_date = new DateTime($hosting['end_date']);
                            $now = new DateTime();
                            $diff = $now->diff($end_date);
                            $days_left = $diff->days;
                            
                            if ($end_date < $now) {
                                echo '<span class="text-danger">หมดอายุแล้ว</span>';
                            } else {
                                echo date('d/m/Y', strtotime($hosting['end_date']));
                                echo '<br><small class="text-muted">เหลือ ' . $days_left . ' วัน</small>';
                            }
                            ?>
                        </td>
                        <td><?php echo getStatusBadge($hosting['status']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="?p=hosting-view&id=<?php echo $hosting['id']; ?>" 
                                   class="btn btn-sm btn-info" data-toggle="tooltip" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#hostingTable').DataTable({
        "order": [[3, "asc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
        }
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

</script>