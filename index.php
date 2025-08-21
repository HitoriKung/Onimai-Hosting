<?php
ob_start();
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

$page = isset($_GET['p']) ? $_GET['p'] : 'home';

function getBanners($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM banners WHERE active = 1 ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Banner Error: " . $e->getMessage());
        return [];
    }
}

function getNews($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM news ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("News Error: " . $e->getMessage());
        return [];
    }
}

function getUserBalance($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        error_log("Balance Error: " . $e->getMessage());
        return 0;
    }
}

function getUserBalanceUsed($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT balance_used FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        error_log("Balance Error: " . $e->getMessage());
        return 0;
    }
}


function createTablesIfNotExist($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS banners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_url VARCHAR(255) NOT NULL,
            title VARCHAR(100),
            description TEXT,
            active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS news (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            image_url VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        )");

    } catch(PDOException $e) {
        error_log("Table Creation Error: " . $e->getMessage());
    }
}

createTablesIfNotExist($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Onimai Cloud - Hosting</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans+Thai:300,400,500,700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    
    <link href="assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    
    <style>
        * {
            font-family: 'Noto Sans Thai', 'Nunito', sans-serif;
        }
        .banner-slider {
            height: 400px;
            margin-bottom: 2rem;
        }
        .banner-slide {
            background-size: cover;
            background-position: center;
        }
        .news-card {
            transition: transform 0.3s;
        }
        .news-card:hover {
            transform: translateY(-5px);
        }
        .sidebar-brand-text {
            font-weight: 700;
        }
                
        .badge-counter {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
        }

        .nav-link .badge-success {
            background-color: #1cc88a;
        }

        .dropdown-header h5 {
            margin-bottom: 0;
        }

        .img-profile {
            height: 2rem;
            width: 2rem;
        }

        .topbar .dropdown-list .dropdown-header {
            background-color: #4e73df;
            border: 1px solid #4e73df;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            color: #fff;
        }

        .btn-group .btn {
            min-width: 80px;
        }

        .list-group-item img {
            object-fit: contain;
        }

        .collapse-item.active {
            color: #4e73df !important;
            font-weight: bold;
            background-color: #eaecf4;
        }

        .nav-item .badge {
            font-size: 0.75rem;
            font-weight: 600;
        }

        .collapse-inner .collapse-item i {
            width: 16px;
            text-align: center;
        }

        .sidebar-heading {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="./">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-server"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Onimai Cloud</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item <?php echo $page == 'home' ? 'active' : ''; ?>">
                <a class="nav-link" href="?p=home">
                    <i class="fas fa-fw fa-home"></i>
                    <span>หน้าหลัก</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                บริการ
            </div>

            <li class="nav-item <?php echo $page == 'hosting' ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseHosting"
                    aria-expanded="true" aria-controls="collapseHosting">
                    <i class="fas fa-fw fa-server"></i>
                    <span>Hosting</span>
                </a>
                <div id="collapseHosting" class="collapse <?php echo strpos($page, 'hosting') !== false ? 'show' : ''; ?>"
                    aria-labelledby="headingHosting" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">จัดการ Hosting:</h6>
                        <a class="collapse-item <?php echo $page == 'hosting-order' ? 'active' : ''; ?>" href="?p=hosting-order">
                            <i class="fas fa-shopping-cart fa-fw mr-1"></i> สั่งซื้อ Hosting
                        </a>
                        <a class="collapse-item <?php echo $page == 'hosting-manage' ? 'active' : ''; ?>" href="?p=hosting-manage">
                            <i class="fas fa-cogs fa-fw mr-1"></i> จัดการ Hosting
                        </a>
                    </div>
                </div>
            </li>

            <li class="nav-item <?php echo $page == 'Agent' ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAgent"
                    aria-expanded="true" aria-controls="collapseAgent">
                    <i class="fas fa-fw fa-key"></i>
                    <span>API</span>
                </a>
                <div id="collapseAgent" class="collapse <?php echo strpos($page, 'agent') !== false ? 'show' : ''; ?>"
                    aria-labelledby="headingAgent" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo $page == 'agent-view' ? 'active' : ''; ?>" href="?p=agent-view">
                            <i class="fas fa-key fa-fw mr-1"></i> API KEY
                        </a>
                        <a class="collapse-item <?php echo $page == 'agent-host' ? 'active' : ''; ?>" href="?p=agent-host">
                            <i class="fas fa-server fa-fw mr-1"></i> เช่า Host
                        </a>
                    </div>
                </div>
            </li>


            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                การเงิน
            </div>

            <li class="nav-item <?php echo $page == 'topup' ? 'active' : ''; ?>">
                <a class="nav-link" href="#" data-toggle="modal" data-target="#topupModal">
                    <i class="fas fa-fw fa-wallet"></i>
                    <span>เติมเงิน</span>
                    <span class="badge badge-success ml-2">฿<?php echo number_format(getUserBalance($pdo, $_SESSION['user_id']), 2); ?></span>
                </a>
            </li>

            <li class="nav-item <?php echo $page == 'billing' ? 'active' : ''; ?>">
                <a class="nav-link" href="?p=billing">
                    <i class="fas fa-fw fa-file-invoice-dollar"></i>
                    <span>ประวัติการชำระเงิน</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                บัญชีผู้ใช้
            </div>

            <li class="nav-item <?php echo $page == 'profile' ? 'active' : ''; ?>">
                <a class="nav-link" href="?p=profile">
                    <i class="fas fa-fw fa-user"></i>
                    <span>โปรไฟล์</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                นโยบายและข้อกำหนด
            </div>

            <li class="nav-item">
                <a class="nav-link" href="privacy.html" target="_blank">
                    <i class="fas fa-fw fa-shield-alt"></i>
                    <span>Privacy Policy</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="terms.html" target="_blank">
                    <i class="fas fa-fw fa-file-contract"></i>
                    <span>Terms of Service</span>
                </a>
            </li>

            <hr class="sidebar-divider d-none d-md-block">

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
                
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item no-arrow mx-1">
                            <a class="nav-link" href="#" id="walletInfo">
                                <i class="fas fa-wallet fa-fw"></i>
                                <span class="badge badge-success badge-counter">
                                    ฿<?php echo number_format(getUserBalance($pdo, $_SESSION['user_id']), 2); ?>
                                </span>
                            </a>
                        </li>

                        <li class="nav-item no-arrow mx-1">
                            <a class="nav-link" href="#" data-toggle="modal" data-target="#topupModal">
                                <i class="fas fa-plus-circle fa-fw"></i>
                                <span class="d-none d-md-inline-block ml-1">เติมเงิน</span>
                            </a>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php 
                                    echo htmlspecialchars($_SESSION['realname'] . ' ' . $_SESSION['surname']); 
                                    ?>
                                    <br>
                                    <small class="text-primary">
                                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                                    </small>
                                </span>
                                <img class="img-profile rounded-circle" src="assets/img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <div class="dropdown-header">
                                    ยอดเงินคงเหลือ
                                    <h5 class="text-primary mt-1">
                                        ฿<?php echo number_format(getUserBalance($pdo, $_SESSION['user_id']), 2); ?>
                                    </h5>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="?p=profile">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    โปรไฟล์
                                </a>
                                <a class="dropdown-item" href="?p=billing">
                                    <i class="fas fa-file-invoice-dollar fa-sm fa-fw mr-2 text-gray-400"></i>
                                    ประวัติการชำระเงิน
                                </a>
                                <a class="dropdown-item" href="?p=settings">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    ตั้งค่า
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    ออกจากระบบ
                                </a>
                            </div>
                        </li>
                    </ul>

                </nav>

                <div class="container-fluid">
                    <?php
                    switch($page) {
                        case 'home':
                            include 'pages/home.php';
                            break;
                        case 'hosting-order':
                            include 'pages/hosting/order.php';
                            break;
                        case 'hosting-manage':
                            include 'pages/hosting/manage.php';
                            break;
                        case 'hosting-view':
                            include 'pages/hosting/view.php';
                            break;
                        case 'vps-order':
                            include 'pages/vps/order.php';
                            break;
                        case 'vps-manage':
                            include 'pages/vps/manage.php';
                            break;
                        case 'billing':
                            include 'pages/billing.php';
                            break;
                        case 'profile':
                            include 'pages/profile.php';
                            break;
                        case 'settings':
                            include 'pages/settings.php';
                            break;
                        case 'agent-register':
                            include 'pages/agent/register.php';
                            break;
                        case 'agent-view':
                            include 'pages/agent/view.php';
                            break;
                        case 'agent-host':
                            include 'pages/agent/hosting.php';
                            break;
                        case 'agent-service':
                            include 'pages/agent/service.php';
                            break;
                        default:
                            include 'pages/404.php';
                    }

                    ?>
                </div>
            </div>
            
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>&copy; 2024-2025 Onimai Cloud. All rights reserved.</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <div class="modal fade" id="topupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เติมเงิน</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="topupTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#bankTab">
                                <i class="fas fa-university mr-2"></i>โอนผ่านธนาคาร
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#truewalletTab">
                                <i class="fas fa-wallet mr-2"></i>TrueWallet
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#redeemTab">
                                <i class="fas fa-gift mr-2"></i>Redeem Code
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3">
                        <div class="tab-pane fade show active" id="bankTab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">บัญชีธนาคาร</h6>
                                            <p class="mb-1">ธนาคารกรุงเทพ</p>
                                            <p class="mb-1">เลขที่บัญชี: 379-0-38800-7</p>
                                            <p class="mb-0">ชื่อบัญชี: นาย อธิชญ พงษ์วดี</p>
                                        </div>
                                    </div>
                                    <form id="bankTopupForm">
                                        <div class="form-group">
                                            <label>จำนวนเงิน</label>
                                            <input type="number" class="form-control" name="amount" min="1" required>
                                        </div>
                                        <div class="form-group">
                                            <label>อัปโหลดสลิป</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" name="slip" accept="image/*" required>
                                                <label class="custom-file-label">เลือกไฟล์...</label>
                                            </div>
                                            <small class="form-text text-muted">รองรับไฟล์ภาพ jpg, png ขนาดไม่เกิน 2MB</small>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload mr-2"></i>อัปโหลดสลิป
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <div id="slipPreview" class="text-center d-none">
                                        <h6 class="mb-3">ตัวอย่างสลิป</h6>
                                        <img src="" class="img-fluid rounded">
                                        <div id="qrResult" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="truewalletTab">
                            <form id="truewalletTopupForm">
                                <div class="form-group">
                                    <label>ลิงก์อั่งเปา TrueWallet</label>
                                    <input type="text" class="form-control" name="link" placeholder="https://gift.truemoney.com/campaign/?v=xxxxx" required>
                                    <small class="form-text text-muted">ตัวอย่าง: https://gift.truemoney.com/campaign/?v=xxxxx</small>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check mr-2"></i>ยืนยันการเติมเงิน
                                </button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="redeemTab">
                            <form id="redeemForm">
                                <div class="form-group">
                                    <label>Redeem Code</label>
                                    <input type="text" class="form-control" name="code" placeholder="กรอกรหัสคูปอง" required>
                                </div>
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-gift mr-2"></i>ใช้คูปอง
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">ยืนยันการออกจากระบบ?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>คุณต้องการออกจากระบบใช่หรือไม่?</p>
                    <p class="small text-muted mb-0">การออกจากระบบจะทำให้คุณต้องเข้าสู่ระบบใหม่ในครั้งถัดไป</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">
                        <i class="fas fa-times fa-sm fa-fw mr-1"></i>
                        ยกเลิก
                    </button>
                    <a class="btn btn-primary" href="javascript:void(0);" id="confirmLogout">
                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-1"></i>
                        ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </div>


<script>


    function updateBalance() {
        $.ajax({
            url: 'api/balance.php',
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const formattedBalance = '฿' + parseFloat(response.balance).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    $('.badge-success.badge-counter').text(formattedBalance);
                    $('.dropdown-header h5').text(formattedBalance);
                }
            }
        });
    }

    setInterval(updateBalance, 30000);

    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('p');

        if (currentPage && (currentPage.includes('hosting') || currentPage.includes('vps'))) {
            const parentMenu = currentPage.split('-')[0];
            $(`#collapse${parentMenu.charAt(0).toUpperCase() + parentMenu.slice(1)}`).addClass('show');
        }
    });

    $(document).ready(function() {
        $('#confirmLogout').click(function(e) {
            e.preventDefault();
            
            $(this).prop('disabled', true);
            $(this).html('<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>กำลังออกจากระบบ...');
            
            $.ajax({
                url: 'api/auth.php',
                type: 'POST',
                data: {
                    action: 'logout'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ออกจากระบบสำเร็จ',
                            text: 'กำลังนำคุณไปยังหน้าเข้าสู่ระบบ...',
                            timer: 2000,
                            showConfirmButton: false,
                            allowOutsideClick: false
                        }).then(function() {
                            window.location.href = 'login';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถออกจากระบบได้ กรุณาลองใหม่อีกครั้ง'
                        });
                        $('#logoutModal').modal('hide');
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                    $('#logoutModal').modal('hide');
                }
            });
        });
    });

</script>


<script>
$(document).ready(function() {
    $('input[name="slip"]').change(function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#slipPreview').removeClass('d-none');
                $('#slipPreview img').attr('src', e.target.result);

                const image = new Image();
                image.src = e.target.result;
                image.onload = function() {
                    const canvas = document.createElement('canvas');
                    canvas.width = image.width;
                    canvas.height = image.height;
                    const context = canvas.getContext('2d');
                    context.drawImage(image, 0, 0);
                    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height);

                    if (code) {
                        $('#qrResult').html(`
                            <div class="alert alert-success">
                                <i class="fas fa-qrcode mr-2"></i>ตรวจพบ QR Code
                                <div class="small mt-1">${code.data}</div>
                            </div>
                        `);
                    } else {
                        $('#qrResult').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>ไม่พบ QR Code
                            </div>
                        `);
                    }
                };
            };
            reader.readAsDataURL(file);
            $(this).next('.custom-file-label').html(file.name);
        }
    });

    $('#bankTopupForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'bank_transfer');

        const qrData = $('#qrResult .small').text();
        if (qrData) {
            formData.append('qr_data', qrData);
        }

        Swal.fire({
            title: 'ยืนยันการเติมเงิน',
            text: 'คุณต้องการเติมเงินใช่หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังดำเนินการ...',
                    text: 'กรุณารอสักครู่',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: 'api/topup.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.close(); // ปิด loading alert

                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ',
                                text: 'ระบบได้รับข้อมูลการเติมเงินแล้ว กรุณารอการตรวจสอบ'
                            }).then(() => {
                                location.reload();
                            });
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
        });
    });

    $('#truewalletTopupForm').on('submit', function(e) {
        e.preventDefault();
        const link = $(this).find('input[name="link"]').val();

        if (!link.includes('gift.truemoney.com/campaign')) {
            Swal.fire('Error', 'ลิงก์ไม่ถูกต้อง', 'error');
            return;
        }

        Swal.fire({
            title: 'กำลังดำเนินการ...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: 'api/topup.php',
            type: 'POST',
            data: {
                action: 'truewallet',
                link: link
            },
            success: function(response) {
                Swal.close();

                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'ระบบได้รับข้อมูลการเติมเงินแล้ว กรุณารอการตรวจสอบ'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.close();
                Swal.fire('Error', 'เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            }
        });
    });

    $('#redeemForm').on('submit', function(e) {
        e.preventDefault();
        const code = $(this).find('input[name="code"]').val();

        Swal.fire({
            title: 'กำลังดำเนินการ...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: 'api/topup.php',
            type: 'POST',
            data: {
                action: 'redeem',
                code: code
            },
            success: function(response) {
                Swal.close();

                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        html: `ใช้คูปองสำเร็จ<br><b>ได้รับเครดิต: ฿${response.credit_amount}</b>`
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.close();
                Swal.fire('Error', 'เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            }
        });
    });
});
</script>
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js" ></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/js/sb-admin-2.min.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js" ></script>
</body>
</html>
