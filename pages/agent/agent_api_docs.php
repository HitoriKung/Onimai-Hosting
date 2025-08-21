<?php
$api_key = $agent['api_key'];
?>

<div class="card shadow border-0 rounded">
    <div class="card-header bg-white border-0">
        <h5 class="mb-1 font-weight-bold text-primary">
            <i class="fas fa-book mr-2"></i>API ขั้นพื้นฐาน ตัวแทน
        </h5>
    </div>
    <div class="card-body pb-4">
        <div class="mb-3">
            <strong class="text-danger">API หลัก</strong>
            <ul class="mb-3 mt-1" style="font-size:1.05rem;">
                <li><b>API URL:</b>
                    <a href="https://my.pnkshop.in.th/api_agent" target="_blank">https://my.pnkshop.in.th/api_agent</a>
                </li>
                <li><b>Method:</b> <span class="badge badge-info">POST</span></li>
            </ul>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <h6 class="font-weight-bold mb-1">Request Header</h6>
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Request Field</th>
                            <th>Type</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>X-API-Key</td>
                            <td>text</td>
                            <td>
                                คีย์ของคุณ
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card-body pb-4">
        <div class="mb-3">
            <strong class="text-danger">เช็คยอดเงินเครดิต</strong>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <h6 class="font-weight-bold mb-1">Request Body</h6>
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Request Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>action</td>
                            <td>checkBalance</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h6 class="font-weight-bold mt-4 mb-2">ตัวอย่าง Response</h6>
        <pre class="bg-light rounded px-3 py-2"><code class="language-json">{
    "status": "success",
    "balance": 1000.00
}</code></pre>

    </div>
    <hr/>
    <div class="card-body pb-4">
        <div class="mb-3">
            <strong class="text-danger">ดูรายการแพคเกจ</strong>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <h6 class="font-weight-bold mb-1">Request Body</h6>
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Request Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>action</td>
                            <td>getPackages</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h6 class="font-weight-bold mt-4 mb-2">ตัวอย่าง Response</h6>
        <pre class="bg-light rounded px-3 py-2"><code class="language-json">{
    "status": "success",
    "packages": [
        {
            "id": 1, //นี่คือ package id ใช้อันนี้เป็นหลัก
            "name": "Starter Pack SG1",
            "price": "10.00",
            "domains_limit": "1",
            "subdomains_limit": "1",
            "space_mb": "100",
            "bandwidth_gb": "10",
            "email_accounts": "1",
            "db_count": "1",
            "package_id": "basic", //นี่ไม่ใช้ package id อย่าใช้ตัวนี้
            "category_name": "DirectAdmin SG-1"
        }
    ]
}</code></pre>

    </div>
    <hr/>
    <div class="card-body pb-4">
        <div class="mb-3">
            <strong class="text-danger">สร้างคำสั่งซื้อ</strong>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <h6 class="font-weight-bold mb-1">Request Body</h6>
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Request Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>action</td>
                            <td>createOrder</td>
                        </tr>
                        <tr>
                            <td>package_id</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td>domain</td>
                            <td>onimai.cloud</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h6 class="font-weight-bold mt-4 mb-2">ตัวอย่าง Response</h6>
        <pre class="bg-light rounded px-3 py-2"><code class="language-json">{
    "status": "success",
    "order_id": 123,
    "credentials": {
        "username": "examplecom",
        "password": "randompassword"
    }
}</code></pre>

    </div>
    <hr/>
    <div class="card-body pb-4">
        <div class="mb-3">
            <strong class="text-danger">ต่ออายุ</strong>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <h6 class="font-weight-bold mb-1">Request Body</h6>
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Request Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>action</td>
                            <td>renewHosting</td>
                        </tr>
                        <tr>
                            <td>order_id</td>
                            <td>123</td>
                        </tr>
                        <tr>
                            <td>period</td>
                            <td>จำนวนเดือน</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h6 class="font-weight-bold mt-4 mb-2">ตัวอย่าง Response</h6>
        <pre class="bg-light rounded px-3 py-2"><code class="language-json">{
    "status": "success",
    "message": "Hosting renewed successfully"
}</code></pre>

    </div>
    <hr/>
    <div class="card-body pb-4">
        <div class="mb-3">
            <strong class="text-danger">ประวัติการสั่งซื้อ</strong>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <h6 class="font-weight-bold mb-1">Request Body</h6>
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Request Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>action</td>
                            <td>getOrderHistory</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h6 class="font-weight-bold mt-4 mb-2">ตัวอย่าง Response</h6>
        <pre class="bg-light rounded px-3 py-2"><code class="language-json">{
    "status": "success",
    "orders": [
        {
            "id": 1,
            "domain": "kazebyte.xyz",
            "status": "active",
            "start_date": "2025-02-19 15:10:08",
            "end_date": "2026-05-30 21:10:51",
            "package_name": "Cookie Pack SG1",
            "price": "20.00"
        }
    ]
}</code></pre>

    </div>
    <hr/>
    <div class="card-body pb-4">
        <div class="mb-3">
            <strong class="text-danger">ดูรายละเอียด</strong>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <h6 class="font-weight-bold mb-1">Request Body</h6>
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Request Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>action</td>
                            <td>getHostingDetails</td>
                        </tr>
                        <tr>
                            <td>order_id</td>
                            <td>1</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h6 class="font-weight-bold mt-4 mb-2">ตัวอย่าง Response</h6>
        <pre class="bg-light rounded px-3 py-2"><code class="language-json">{
    "status": "success",
    "hosting": {
        "id": 1,
        "user_id": 2,
        "package_id": 2,
        "domain": "kazebyte.xyz",
        "hosting_username": "kazebyte",
        "hosting_password": "X%4*hmlS08Ds",
        "status": "active",
        "start_date": "2025-02-19 15:10:08",
        "end_date": "2026-05-30 21:10:51",
        "created_at": "2025-02-19 15:10:08",
        "renewed_at": "2025-03-30 21:10:51",
        "renewed_by": "1",
        "terminated_at": null,
        "terminated_by": "",
        "suspended_at": null,
        "suspended_by": "",
        "unsuspended_at": null,
        "unsuspended_by": "",
        "package_name": "Cookie Pack SG1",
        "price": "20.00",
        "domains_limit": "1",
        "subdomains_limit": "5",
        "space_mb": "1024",
        "bandwidth_gb": "100",
        "email_accounts": "10",
        "db_count": "10",
        "category_name": "DirectAdmin SG-1"
    }
}</code></pre>

    </div>
</div>

<script>
function copyInput(id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    input.select();
    document.execCommand('copy');
    btn.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(()=>{btn.innerHTML='<i class="fas fa-copy"></i>';}, 1400);
}
</script>
