<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Akun Karyawan Berhasil Dibuat</title>
</head>
<body>
    <h2>Selamat datang di {{ $company_name ?? 'Perusahaan Anda' }}</h2>

    <p>Akun karyawan Anda telah dibuat dengan detail berikut:</p>

    <ul>
        <li><strong>Employee ID:</strong> {{ $employee_id ?? '-' }}</li>
        <li><strong>Email:</strong> {{ $email ?? '-' }}</li>
        <li><strong>Password:</strong> {{ $password ?? '-' }}</li>
        <li><strong>Company Name:</strong> {{ $company_name ?? '-' }}</li>
    </ul>

    <p>Silakan login menggunakan kredensial di atas. Jangan bagikan informasi ini kepada siapapun.</p>

    <p>Terima kasih.</p>
</body>
</html>
