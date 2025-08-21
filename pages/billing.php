<?php
$page_title = "การเงิน";

// Get transactions
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        CASE t.method
            WHEN 'bank' THEN 'โอนผ่านธนาคาร'
            WHEN 'truewallet' THEN 'TrueWallet'
            WHEN 'redeem' THEN 'Redeem Code'
        END as method_text,
        CASE t.status
            WHEN 'pending' THEN 'รอตรวจสอบ'
            WHEN 'approved' THEN 'อนุมัติแล้ว'
            WHEN 'rejected' THEN 'ปฏิเสธ'
        END as status_text,
        a.username as approved_by_name
    FROM topup_transactions t
    LEFT JOIN users a ON t.approved_by = a.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll();

// Get redeem history
$stmt = $pdo->prepare("
    SELECT 
        rh.*,
        rc.code,
        rc.credit_amount
    FROM redeem_history rh
    JOIN redeem_codes rc ON rh.code_id = rc.id
    WHERE rh.user_id = ?
    ORDER BY rh.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$redeem_history = $stmt->fetchAll();

?>

<!-- Content Container -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">การเงิน</h1>
        <button class="btn btn-primary" onclick="showTopupModal()">
            <i class="fas fa-plus fa-sm mr-2"></i>เติมเงิน
        </button>
    </div>

    <!-- Balance Overview -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">ยอดเงินคงเหลือ</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">฿<?php echo number_format(getUserBalance($pdo, $_SESSION['user_id']), 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ยอดใช้จ่ายทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">฿<?php echo number_format(getUserBalanceUsed($pdo, $_SESSION['user_id']), 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">การทำรายการที่รอดำเนินการ</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_filter($transactions, function($t) { return $t['status'] === 'pending'; })); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions History -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">ประวัติการเติมเงิน</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="transactionTable">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>จำนวนเงิน</th>
                            <th>ช่องทาง</th>
                            <th>สถานะ</th>
                            <th>หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trans): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?></td>
                            <td>฿<?php echo number_format($trans['amount'], 2); ?></td>
                            <td>
                                <span class="text-<?php echo $trans['method'] === 'bank' ? 'primary' : ($trans['method'] === 'truewallet' ? 'success' : 'info'); ?>">
                                    <i class="fas fa-<?php echo $trans['method'] === 'bank' ? 'university' : ($trans['method'] === 'truewallet' ? 'wallet' : 'gift'); ?> mr-1"></i>
                                    <?php echo $trans['method_text']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($trans['status'] === 'pending'): ?>
                                    <span class="badge badge-warning">รอตรวจสอบ</span>
                                <?php elseif ($trans['status'] === 'approved'): ?>
                                    <span class="badge badge-success">อนุมัติแล้ว</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">ปฏิเสธ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($trans['status'] === 'approved'): ?>
                                    อนุมัติโดย: <?php echo $trans['approved_by_name']; ?>
                                    <br>
                                    เมื่อ: <?php echo date('d/m/Y H:i', strtotime($trans['approved_at'])); ?>
                                <?php endif; ?>
                                <?php if ($trans['admin_note']): ?>
                                    <br>
                                    หมายเหตุ: <?php echo $trans['admin_note']; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Redeem History -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">ประวัติการใช้คูปอง</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="redeemTable">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>รหัสคูปอง</th>
                            <th>เครดิตที่ได้รับ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($redeem_history as $redeem): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($redeem['created_at'])); ?></td>
                            <td><code><?php echo $redeem['code']; ?></code></td>
                            <td>฿<?php echo number_format($redeem['credit_amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#transactionTable, #redeemTable').DataTable({
        "order": [[0, "desc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
        }
    });
}); 
</script>

<style>
.nav-tabs .nav-link {
    color: #5a5c69;
}
.nav-tabs .nav-link.active {
    color: #4e73df;
    font-weight: 600;
}
.custom-file-label::after {
    content: "เลือกไฟล์";
}
#slipPreview img {
    max-height: 400px;
    object-fit: contain;
}
.badge {
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
}
code {
    background: #eaecf4;
    padding: 0.2rem 0.4rem;
    border-radius: 0.2rem;
}
</style>
