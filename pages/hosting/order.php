<?php
$stmt = $pdo->query("SELECT * FROM hosting_categories WHERE is_active = 1");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
// You may want to consider a DAO/service layer, but fine for now.
?>

<div id="categorySection" class="row justify-content-center mb-5">
  <?php foreach($categories as $category): ?>
    <div class="col-xl-4 col-md-6 mb-4">
      <div class="card h-100 shadow-sm border-0 bg-light category-card transition shadow-hover" style="cursor:pointer;" onclick="loadPackages(<?php echo $category['id']; ?>)">
        <div class="card-body text-center">
          <?php if($category['module'] == "directadmin") { ?>
          <img src="https://ashewacloud.com/images/directadmin.svg"
               class="mb-3" alt="icon" style="height:60px;">
          <?php }else{ ?>
          <img src="https://logos-download.com/wp-content/uploads/2021/01/Plesk_Logo-3000x1244.png"
               class="mb-3" alt="icon" style="height:60px;">
          <?php } ?>
          <h4 class="font-weight-bold text-primary mb-2"><?php echo htmlspecialchars($category['name']); ?></h4>
          <p class="text-secondary mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
        </div>
        <div class="card-footer text-center bg-white border-0">
          <button class="btn btn-outline-primary btn-block">ดูแพ็คเกจทั้งหมด</button>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div id="packagesSection" class="row justify-content-center" style="display: none;">
  <div class="col-12 mb-3">
    <button class="btn btn-outline-secondary mb-4" onclick="showCategories()">
      <i class="fas fa-arrow-left mr-2"></i> กลับไปยังหมวดหมู่
    </button>
  </div>
  <div id="packagesContainer" class="col-12"></div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content border-0">
      <div class="modal-header bg-gradient-primary text-white">
        <h5 class="modal-title" id="orderModalLabel">ยืนยันการเช่า Hosting</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <form id="orderForm" class="needs-validation" novalidate>
        <div class="modal-body p-4">
          <input type="hidden" id="packageId" name="package_id">
          <div class="form-group mb-3">
            <label for="domain" class="font-weight-semibold">โดเมนที่ต้องการใช้งาน</label>
            <input type="text" class="form-control form-control-lg" id="domain" name="domain" required placeholder="yourdomain.com">
            <div class="invalid-feedback">โปรดกรอกโดเมน</div>
          </div>
          <div id="packageDetails" class="mb-0"></div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">ยกเลิก</button>
          <button class="btn btn-primary btn-lg" type="button" onclick="confirmOrder()">ยืนยันการเช่า</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.shadow-hover:hover { box-shadow: 0 8px 32px rgba(39, 123, 243,.20),0 1.5px 4px rgba(80,80,80,.07) !important; }
.category-card { transition: box-shadow .2s,transform .14s; border-radius: 1.2rem;}
.category-card:active, .category-card:focus, .category-card:hover { transform: translateY(-4px) scale(1.02);}
</style>

<script>
function loadPackages(categoryId) {
  $.ajax({
    url: 'api/hosting.php',
    type: 'POST',
    data: { action: 'getPackages', category_id: categoryId },
    success: function(response) {
      if (response.status === 'success') {
        $('#packagesContainer').html(generatePackagesHTML(response.packages));
        $('#categorySection').hide();
        $('#packagesSection').show();
      } else {
        Swal.fire('Error', response.message, 'error');
      }
    }
  });
}

function generatePackagesHTML(packages) {
  if (!packages.length) return '<div class="alert alert-warning text-center">ไม่พบแพ็คเกจในหมวดนี้</div>';
  let html = '<div class="row justify-content-center">';
  packages.forEach(pkg => {
    html += `
      <div class="col-xl-4 col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100 package-card transition" style="border-radius:1.2rem;">
          <div class="card-header bg-white text-center border-0 pb-0">
            <h5 class="m-0 font-weight-bold text-primary">${pkg.name}</h5>
            <div class="h3 text-success font-weight-bold mb-0">฿${pkg.price_monthly}<small class="text-secondary font-weight-normal">/เดือน</small></div>
          </div>
          <div class="card-body text-left">
            <ul class="list-unstyled mb-3 pl-1 small">
              <li><i class="fas fa-globe-asia mr-2 text-primary"></i><b>${pkg.domains_limit}</b> โดเมน</li>
              <li><i class="fas fa-layer-group mr-2 text-primary"></i><b>${pkg.subdomains_limit}</b> ซับโดเมน</li>
              <li><i class="fas fa-database mr-2 text-primary"></i><b>${pkg.db_count}</b> ฐานข้อมูล</li>
              <li><i class="fas fa-envelope mr-2 text-primary"></i><b>${pkg.email_accounts}</b> อีเมล</li>
              <li><i class="fas fa-hdd mr-2 text-primary"></i><b>${pkg.space_mb}MB</b> พื้นที่</li>
              <li><i class="fas fa-exchange-alt mr-2 text-primary"></i><b>${pkg.bandwidth_gb}GB</b> แบนด์วิดธ์</li>
            </ul>
            <hr>
            <div class="mb-1">
              ${pkg.has_ssl ? '<span class="badge badge-success mr-1"><i class="fas fa-lock mr-1"></i>SSL ฟรี</span>' : ''}
              ${pkg.has_softaculous ? '<span class="badge badge-secondary mr-1"><i class="fas fa-magic"></i> Softaculous</span>' : ''}
              ${pkg.has_directadmin ? '<span class="badge badge-info mr-1"><i class="fas fa-cogs"></i> DirectAdmin</span>' : ''}
              ${pkg.has_backup ? '<span class="badge badge-warning text-white mr-1"><i class="fas fa-history"></i> Backup</span>' : ''}
            </div>
          </div>
          <div class="card-footer bg-white border-0 text-center">
            <button class="btn btn-primary btn-lg btn-block rounded-pill" onclick="showOrderModal(${pkg.id}, this)">เช่าเลย</button>
          </div>
        </div>
      </div>
    `;
  });
  html += '</div>';
  return html;
}

function showOrderModal(packageId, btn) {
  $('#packageId').val(packageId);

  if (btn) {
    const card = $(btn).closest('.package-card');
    const details = card.find('ul').clone();
    $('#packageDetails').html('<div class="border rounded p-3 bg-light">' + details.prop('outerHTML') + '</div>');
  }

  $('#orderModal').modal('show');
}

function confirmOrder() {
  const form = $('#orderForm')[0];
  if (!form.checkValidity()) {
    $(form).addClass('was-validated');
    return;
  }
  const domain = $('#domain').val();
  const packageId = $('#packageId').val();

  Swal.fire({
    title: 'กำลังดำเนินการ...',
    text: 'กรุณารอสักครู่',
    allowOutsideClick: false,
    didOpen: () => { Swal.showLoading(); }
  });

  $.ajax({
    url: 'api/hosting.php',
    type: 'POST',
    data: {
      action: 'createOrder',
      package_id: packageId,
      domain: domain
    },
    success: function(response) {
      Swal.close();
      if (response.status === 'success') {
        $('#orderModal').modal('hide');
        Swal.fire({
          icon: 'success',
          title: 'สำเร็จ!',
          text: 'การสั่งซื้อของคุณเสร็จสมบูรณ์',
          confirmButtonText: 'ไปหน้าจัดการ Hosting'
        }).then(r => { if (r.isConfirmed) window.location.href = '?p=hosting-manage'; });
      } else {
        Swal.fire('Error', response.message, 'error');
      }
    },
    error: function() {
      Swal.close();
      Swal.fire('Error', 'เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
    }
  });
}

function showCategories() {
  $('#packagesSection').hide();
  $('#categorySection').show();
}

</script>
