<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <form method="POST" action="{{ route('login') }}" class="bg-white p-6 rounded shadow-md w-80">
        @csrf
        <h1 class="text-xl mb-4 font-bold text-center">Login</h1>

        @if($errors->any())
            <div class="mb-4 text-red-500 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-4">
            <label class="block text-sm">Email</label>
            <input type="email" name="email" required class="w-full border rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm">Password</label>
            <input type="password" name="password" required class="w-full border rounded px-3 py-2">
        </div>

        <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded">Login</button>
    </form>
</body>
</html>
