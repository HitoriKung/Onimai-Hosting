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
    <title>PNK CLOUD - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.10.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans+Thai:300,400,500,700&display=swap" rel="stylesheet" />
    <style>
      body { font-family: 'Noto Sans Thai', sans-serif; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-primary min-h-screen flex items-center justify-center">
  <main class="w-full max-w-md mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl w-full">
      <div class="card-body">
        <h2 class="card-title justify-center text-2xl font-bold mb-4">เข้าสู่ระบบ</h2>
        <form id="loginForm" class="space-y-4">
          <div>
            <input type="text" id="username" name="username" placeholder="Username" class="input input-bordered w-full" required>
          </div>
          <div>
            <input type="password" id="password" name="password" placeholder="Password" class="input input-bordered w-full" required>
          </div>
          <button type="submit" class="btn btn-primary w-full">เข้าสู่ระบบ</button>
        </form>
        <div class="mt-4 flex flex-col items-center gap-2">
          <a href="forgot-password.html" class="link link-primary text-sm">ลืมรหัสผ่าน?</a>
          <a href="register" class="link link-secondary text-sm">ยังไม่มีบัญชี? สร้างบัญชีใหม่</a>
        </div>
      </div>
    </div>
  </main>
  <script>
    document.getElementById("loginForm").addEventListener("submit", function(e) {
      e.preventDefault();
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;

      if (username.length < 3) {
        Swal.fire({icon:'error',title:'Invalid Username',text:'Username must be at least 3 characters long'});
        return;
      }

      fetch('api/auth.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          action:'login',
          username,
          password
        })
      })
      .then(res=>res.json())
      .then(response=>{
        if(response.status==='success'){
          Swal.fire({icon:'success',title:'Success!',text:'Login successful',timer:1500,showConfirmButton:false})
          .then(()=>window.location.href='./');
        }else{
          Swal.fire({icon:'error',title:'เกิดข้อผิดพลาด!',text:response.message});
        }
      })
      .catch(()=>{
        Swal.fire({icon:'error',title:'Error',text:'An error occurred. Please try again later.'});
      });
    });
  </script>
</body>
</html>
