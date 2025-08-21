<?php
// ดึงข้อมูล agent
$stmt = $pdo->prepare("SELECT * FROM agents WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$agent = $stmt->fetch();
if (!$agent) { header('Location: ?p=agent-register'); exit(); }
$isConfirmed = $agent['confirmed'] == 1;

// ดึงแพ็คเกจ กลุ่มตามหมวดหมู่
$stmt = $pdo->prepare("
    SELECT hp.*, hc.name as category_name 
    FROM hosting_packages hp 
    JOIN hosting_categories hc ON hp.category_id = hc.id 
    WHERE hp.is_active = 1
    ORDER BY hc.name, hp.price_monthly
");
$stmt->execute();
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorizedPackages = [];
foreach ($packages as $package) {
    $categorizedPackages[$package['category_name']][] = $package;
}
?>

<div class="container-fluid px-0 mb-4">

    <div class="container">
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="agentTab" role="tablist" style="border-radius:.85rem .85rem 0 0;">
        <li class="nav-item">
            <a class="nav-link active" id="dashboard-tab" data-toggle="tab" href="#dashboardTab" role="tab">
                <i class="fas fa-tachometer-alt mr-1"></i> แดชบอร์ด
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="package-tab" data-toggle="tab" href="#packageTab" role="tab">
                <i class="fas fa-tags mr-1"></i> ราคาตัวแทน
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="api-tab" data-toggle="tab" href="#apiTab" role="tab">
                <i class="fas fa-book mr-1"></i> API Documentation
            </a>
        </li>
    </ul>
    <div>


    <div class="tab-content mb-5" id="agentTabContent">
        <!-- แดชบอร์ด -->
        <div class="tab-pane fade show active" id="dashboardTab" role="tabpanel">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card border-0 shadow rounded">
                        <div class="card-header bg-white border-0 pb-2">
                            <h5 class="font-weight-bold text-primary mb-0">รายการสำหรับตัวแทน</h5>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>รับส่วนลด Agent สำหรับทุกแพ็คเกจ</li>
                                <li>เชื่อมต่อผ่าน API ได้ทันทีหลังได้รับอนุมัติ</li>
                                <li>ดูราคาและรายละเอียดแพ็คเกจในแท็บ "ราคาตัวแทน"</li>
                                <li>ศึกษาการใช้งาน API ในแท็บ "API Documentation"</li>
                            </ul>
                            <div class="text-center mt-4">
                                <a href="?p=hosting-manage" class="btn btn-outline-primary">
                                    <i class="fas fa-laptop mr-1"></i> จัดการ Hosting ของฉัน
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ราคาตัวแทน -->
        <div class="tab-pane fade" id="packageTab" role="tabpanel">
            <?php foreach ($categorizedPackages as $categoryName => $packages): ?>
            <div class="col-lg-12 mx-auto mb-5">
                <div class="card shadow border-0 group-hover">
                    <div class="card-header bg-light border-0">
                        <h5 class="font-weight-bold text-primary mb-0"><i class="fas fa-box-open mr-2"></i><?php echo htmlspecialchars($categoryName); ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>แพ็คเกจ</th>
                                        <th>ราคาปกติ</th>
                                        <th>ราคาตัวแทน</th>
                                        <th>ส่วนลด</th>
                                        <th>รายละเอียด</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($packages as $package): ?>
                                    <tr>
                                        <td class="font-weight-bold text-primary"><?php echo htmlspecialchars($package['name']); ?></td>
                                        <td>฿<?php echo number_format($package['price_monthly'], 2); ?></td>
                                        <td class="text-success font-weight-bold">฿<?php echo number_format($package['agent_price'], 2); ?></td>
                                        <td><span class="badge badge-success">฿<?php echo number_format($package['price_monthly'] - $package['agent_price'], 2); ?></span></td>
                                        <td>
                                            <ul class="small mb-0">
                                                <li>Domain: <?php echo $package['domains_limit']; ?> | Subdomain: <?php echo $package['subdomains_limit']; ?></li>
                                                <li>พื้นที่: <?php echo $package['space_mb']; ?>MB | Bandwidth: <?php echo $package['bandwidth_gb']; ?>GB</li>
                                                <li>Email: <?php echo $package['email_accounts']; ?> | Database: <?php echo $package['db_count']; ?></li>
                                                <?php if($package['has_ssl']): ?><li>SSL ฟรี</li><?php endif;?>
                                                <?php if($package['has_backup']): ?><li>Backup รายสัปดาห์</li><?php endif;?>
                                            </ul>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- API Documentation -->
        <div class="tab-pane fade" id="apiTab" role="tabpanel">
            <?php include __DIR__.'/agent_api_docs.php'; ?>
        </div>
    </div>
</div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-tomorrow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-json.min.js"></script>

<script>
function copyInput(id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    input.select(); document.execCommand('copy');
    btn.innerHTML = '<i class="fas fa-check text-success"></i>'; setTimeout(()=>{btn.innerHTML='<i class="fas fa-copy"></i>';},1400);
}
function regenerateApiKey(btn) {
    Swal.fire({
        title: 'ยืนยันเปลี่ยน API Key',
        text: 'API Key เดิมจะใช้งานไม่ได้อีก',
        icon: 'warning',
        confirmButtonText: 'Regenerate',
        showCancelButton: true,
        cancelButtonText: 'ยกเลิก'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api_agent', {action: 'regenerateKey'}, function(response) {
                if (response.status === 'success') window.location.reload();
                else Swal.fire('Error', response.message, 'error');
            });
        }
    });
}
Prism.highlightAll();
</script>

