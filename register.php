<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ./');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>PNK CLOUD - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.10.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans+Thai:300,400,500,700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; }
        .password-requirements { font-size: 0.85rem; color: #666;}
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-primary min-h-screen flex items-center justify-center">
<main class="w-full max-w-lg mx-auto p-4 py-8">
  <div class="card bg-base-100 shadow-lg w-full">
    <div class="card-body">
      <h2 class="card-title justify-center text-2xl font-bold mb-3">สร้างบัญชีใหม่</h2>
      <form id="registerForm" class="space-y-2">
        <div>
          <input type="text" id="username" name="username" placeholder="Username" required minlength="3" maxlength="20"
            pattern="^[a-zA-Z0-9_]{3,20}$"
            class="input input-bordered w-full" />
          <span class="password-requirements">Username 3-20 ตัว a-z, 0-9 หรือ _</span>
        </div>
        <div class="flex gap-2">
          <input type="text" id="realname" name="realname" placeholder="ชื่อจริง" required class="input input-bordered flex-1" />
          <input type="text" id="surname" name="surname" placeholder="นามสกุล" required class="input input-bordered flex-1" />
        </div>
        <div>
          <input type="email" id="email" name="email" placeholder="อีเมล" required class="input input-bordered w-full" />
        </div>
        <div class="flex gap-2">
          <div class="flex-1">
            <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required minlength="8"
              class="input input-bordered w-full" />
            <span class="password-requirements block mt-1">
              อย่างน้อย 8 ตัว, a-z, A-Z, ตัวเลข, อักษรพิเศษ
            </span>
          </div>
          <div class="flex-1">
            <input type="password" id="repeatPassword" placeholder="ยืนยันรหัสผ่าน" required minlength="8"
            class="input input-bordered w-full" />
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-full mt-3">สร้างบัญชี</button>
      </form>
      <div class="mt-3 flex flex-col items-center gap-2">
        <a href="login" class="link link-primary text-sm">มีบัญชีอยู่แล้ว? เข้าสู่ระบบ</a>
      </div>
    </div>
  </div>
</main>
<script>
document.addEventListener("DOMContentLoaded",function(){
  function showInvalid(id,msg){
    document.getElementById(id).classList.add("input-error");
    Swal.fire({icon:'error',title:'ผิดพลาด',text:msg});
  }
  function clearError(id){ document.getElementById(id).classList.remove("input-error"); }

  document.getElementById("registerForm").addEventListener("submit",function(e){
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const repeatPassword = document.getElementById('repeatPassword').value;
    const realname = document.getElementById('realname').value;
    const surname = document.getElementById('surname').value;
    const email = document.getElementById('email').value;

    // Username
    if(!/^[a-zA-Z0-9_]{3,20}$/.test(username)){
      showInvalid('username','Username ต้อง 3-20 ตัว a-z,0-9 หรือ _');
      return;
    }
    clearError('username');

    // Repeat Password
    if(password!==repeatPassword){
      showInvalid('repeatPassword','รหัสผ่านไม่ตรงกัน');
      return;
    }
    clearError('repeatPassword');
    // Email
    if(!/\S+@\S+\.\S+/.test(email)){
      showInvalid('email','กรุณาใส่อีเมลที่ถูกต้อง');
      return;
    }
    clearError('email');

    fetch('api/auth.php',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({
        action:'register',
        username,
        email,
        password,
        realname,
        surname
      })
    }).then(res=>res.json())
    .then(response=>{
      if(response.status==='success'){
        Swal.fire({icon:'success',title:'สร้างบัญชีแล้ว',text:'บัญชีถูกสร้างสำเร็จ',confirmButtonText:'เข้าสู่ระบบ'})
        .then(r=>{
          if(r.isConfirmed)
            window.location.href='login';
        });
      }else{
        Swal.fire({icon:'error',title:'เกิดข้อผิดพลาด!',text:response.message});
      }
    })
    .catch(()=>{
      Swal.fire({icon:'error',title:'เกิดข้อผิดพลาด!',text:'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'});
    });
  });
});
</script>
</body>
</html>
