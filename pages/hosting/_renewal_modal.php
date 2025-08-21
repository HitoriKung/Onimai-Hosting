<div class="modal fade" id="renewalModal" tabindex="-1" role="dialog" aria-labelledby="renewalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="renewalModalLabel">ต่ออายุ Hosting</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="hostingId">
                <div class="form-group">
                    <label>ระยะเวลาที่ต้องการต่ออายุ</label>
                    <select class="form-control" id="renewalPeriod">
                        <option value="1">1 เดือน</option>
                        <option value="3">3 เดือน</option>
                        <option value="6">6 เดือน</option>
                        <option value="12">12 เดือน</option>
                    </select>
                </div>
                <div id="renewalPrice" class="text-center my-3">
                    <!-- Price will be updated here via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">ยกเลิก</button>
                <button class="btn btn-primary" type="button" onclick="confirmRenewal()">
                    ยืนยันการต่ออายุ
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showRenewalModal(hostingId) {
    $('#hostingId').val(hostingId);
    updateRenewalPrice();
    $('#renewalModal').modal('show');
}

$('#renewalPeriod').change(function() {
    updateRenewalPrice();
});

function updateRenewalPrice() {
    const hostingId = $('#hostingId').val();
    const period = $('#renewalPeriod').val();
    
    $.ajax({
        url: 'api/hosting.php',
        type: 'POST',
        data: {
            action: 'calculateRenewalPrice',
            hosting_id: hostingId,
            period: period
        },
        success: function(response) {
            if (response.status === 'success') {
                $('#renewalPrice').html(`
                    <h4 class="text-primary mb-3">ยอดชำระ: ฿${response.price.toFixed(2)}</h4>
                `);
            }
        }
    });
}

function confirmRenewal() {
    const hostingId = $('#hostingId').val();
    const period = $('#renewalPeriod').val();
    
    Swal.fire({
        title: 'ยืนยันการต่ออายุ',
        text: 'คุณต้องการต่ออายุ Hosting นี้ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'api/hosting.php',
                type: 'POST',
                data: {
                    action: 'renewHosting',
                    hosting_id: hostingId,
                    period: period
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#renewalModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'ต่ออายุสำเร็จ',
                            text: 'Hosting ของคุณได้รับการต่ออายุเรียบร้อยแล้ว'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}
</script>
