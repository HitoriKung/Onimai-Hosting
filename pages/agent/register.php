<?php
$stmt = $pdo->prepare("SELECT * FROM agents WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$agent = $stmt->fetch();

if ($agent) { header('Location: ?p=agent-view'); exit(); }

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

<div class="container-fluid px-0">
  <div class="bg-gradient-primary rounded-lg shadow-lg p-4 p-md-5 mb-5 text-center text-white">
    <h1 class="display-4 font-weight-bold mb-1">สมัครตัวแทนจำหน่าย</h1>
    <p class="lead text-white-50 mb-2">
      รับสิทธิ์ส่วนลดพิเศษและระบบ API สำหรับร้านค้าพาร์ทเนอร์<br>
      Hosting, API เติมเกม, API เติมเงิน
    </p>
    <p class="text-white-50 small mb-0">
      <strong>สำหรับผู้ใช้งานใหม่เท่านั้น</strong> กรุณาใช้บัญชีใหม่ในการสมัคร<br>
      <strong>ไม่แนะนำ</strong> ให้นำบัญชี Hosting เดิมที่มีอยู่แล้วมาสมัคร เพื่อป้องกันปัญหาด้านสิทธิ์การใช้งานในอนาคต
    </p>
  </div>
</div>


<div class="my-5 text-center">
  <button class="btn btn-gradient-primary btn-lg px-5 shadow" style="font-size:1.35rem;" onclick="showRegisterModal()">
    <i class="fas fa-user-plus mr-2"></i>สมัครตัวแทนจำหน่าย
  </button>
</div>

<div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0" style="border-radius:1.25rem;">
      <div class="modal-header bg-gradient-primary text-white">
        <h5 class="modal-title font-weight-bold">สมัครตัวแทนจำหน่าย</h5>
        <button class="close text-white" type="button" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
      </div>
      <form id="agentRegisterForm" class="needs-validation" novalidate>
        <div class="modal-body p-4">
          <div class="form-group mb-4">
            <label for="shopName">ชื่อร้านค้า</label>
            <input type="text" class="form-control form-control-lg" id="shopName" required>
            <div class="invalid-feedback">กรุณากรอกชื่อร้านค้า</div>
          </div>
          <div class="form-group mb-4">
            <label for="shopUrl">ลิงก์ร้านค้า / เพจ / ดิสคอร์ด</label>
            <input type="url" class="form-control form-control-lg" id="shopUrl" required>
            <div class="invalid-feedback">กรุณากรอกลิงก์ร้านค้าถูกต้อง</div>
          </div>
          <div class="form-group mb-2">
            <label for="phone">เบอร์โทร</label>
            <input type="tel" class="form-control form-control-lg" id="phone" required pattern="[0-9]{10}">
            <div class="invalid-feedback">กรุณากรอกเบอร์โทร 10 หลัก</div>
          </div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">ยกเลิก</button>
          <button class="btn btn-primary btn-lg px-4" type="button" onclick="registerAgent()">สมัครตัวแทน</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.btn-gradient-primary {
    background: linear-gradient(90deg,#5961f9,#6e89fa 90%);
    color: #fff;
    border: 0;
}
.btn-gradient-primary:hover {
    background: linear-gradient(90deg,#4e54c8,#6c63ff 90%);
    color: #fff;
}
.group-hover:hover {box-shadow:0 8px 24px rgba(85,110,230,.11), 0 2px 4px rgba(52,59,92,.14);}
</style>

<script>
function showRegisterModal() {
  $('#agentRegisterForm')[0].reset();
  $('#agentRegisterForm').removeClass('was-validated');
  $('#registerModal').modal('show');
}

// Use Bootstrap validation UI
$('#agentRegisterForm').on('submit', function(e) { e.preventDefault(); });

function registerAgent() {
  const form = $('#agentRegisterForm')[0];
  if (!form.checkValidity()) {
    $(form).addClass('was-validated');
    return;
  }
  const shopName = $('#shopName').val();
  const shopUrl = $('#shopUrl').val();
  const phone = $('#phone').val();
  $.ajax({
    url: 'api_agent',
    type: 'POST',
    data: {
      action: 'register', shopName, shopUrl, phone
    },
    success: function(response) {
      if (response.status === 'success') {
        $('#registerModal').modal('hide');
        Swal.fire({
          icon: 'success',
          title: 'สมัครสำเร็จ',
          text: 'กรุณารอการอนุมัติจากผู้ดูแลระบบ',
          confirmButtonText: 'ตกลง'
        }).then(() => { window.location.href = 'index.php?p=agent-view'; });
      } else {
        Swal.fire('Error', response.message, 'error');
      }
    }
  });
}
</script>
