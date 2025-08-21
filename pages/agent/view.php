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

    <!-- Agent Dashboard Card -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-lg mb-5 border-0" style="border-radius:1.2rem;">
                <div class="card-body py-5 text-center bg-gradient-primary text-white" style="border-radius:1.2rem;">
                    <h1 class="font-weight-bold mb-2">ระบบตัวแทนจำหน่าย</h1>
                    <div class="lead mb-1"><i class="fas fa-user-tag mr-2"></i><?php echo htmlspecialchars($agent['shop_name']); ?>
                        <?php if(!$isConfirmed): ?>
                        <span class="badge badge-warning ml-2">รออนุมัติ</span>
                        <?php else: ?>
                        <span class="badge badge-success ml-2">อนุมัติแล้ว</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 mb-1">
                        <a href="<?php echo htmlspecialchars($agent['shop_url']); ?>" target="_blank" class="text-white-50 d-block mb-1" style="font-size:.99rem;">
                            <i class="fas fa-link mr-1"></i> <?php echo htmlspecialchars($agent['shop_url']); ?>
                        </a>
                        <span class="text-white-50"><i class="fas fa-phone-volume mr-1"></i> <?php echo htmlspecialchars($agent['phone']); ?></span>
                    </div>
                </div>
                <div class="card-body pt-4 pb-3">
                    <div class="row mb-2">
                        <div class="col-12 col-md-6 mb-2">
                            <div class="font-weight-bold text-muted mb-1">สถานะ</div>
                            <?php if($isConfirmed): ?>
                                <span class="badge badge-success px-3 py-2 font-weight-bold">Active</span>
                            <?php else: ?>
                                <span class="badge badge-warning px-3 py-2 font-weight-bold">รออนุมัติ</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-6 mb-2 text-md-right">
                            <div class="font-weight-bold text-muted mb-1">วันที่สมัคร</div>
                            <span><?php echo date('d/m/Y H:i', strtotime($agent['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="font-weight-bold text-muted mb-1">API Key ของคุณ</div>
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control" value="<?php echo $agent['api_key']; ?>" readonly id="mainApiKeyInput">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="button" onclick="copyInput('mainApiKeyInput', this)">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button class="btn btn-outline-danger" type="button" onclick="regenerateApiKey(this)">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted ml-2 mt-2">
                            ใช้ API Key นี้ในทุก API <b>อย่าเผยแพร่ต่อสาธารณะ</b>
                        </small>
                    </div>
                </div>
            </div>
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

