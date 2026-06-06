<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-cover bg-center" 
style="background-image: url('{{ asset('images/back2.png') }}');">

    <div class="bg-white/50 backdrop-blur-md shadow-lg rounded-lg p-8 w-full max-w-md">
        <div class="text-center mb-6">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-20 mx-auto mb-4" style="border-radius: 50%; width: 100px; height: 100px; object-fit: cover;">


            <h2 class="text-2xl font-bold text-gray-800">Iniciar Sesión</h2>
        </div>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <!-- Email -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                <input id="email" name="email" type="email" required
                    class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Ingresa tu correo">
            </div>
            <!-- Contraseña -->
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Ingresa tu contraseña">
            </div>
            <!-- Recordarme -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <input id="remember_me" name="remember" type="checkbox"
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-900">Recordarme</label>
                </div>
                <!-- @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">¿Olvidaste tu contraseña?</a>
                @endif -->
            </div>
            <!-- Botón de inicio de sesión -->
            <div>
                <button type="submit"
                    class="w-full px-4 py-2 text-white bg-blue-600 rounded-md shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Iniciar Sesión
                </button>
            </div>
        </form>
        <!-- Enlace de registro -->
        <!-- @if (Route::has('register'))
            <p class="mt-6 text-center text-sm text-gray-600">
                ¿No tienes una cuenta? <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Regístrate</a>
            </p>
        @endif -->
    </div>


<script>
(function () {
    function refreshCsrf() {
        fetch('/csrf-token', { credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !data.token) return;
                document.querySelectorAll('input[name="_token"]').forEach(function (el) {
                    el.value = data.token;
                });
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', data.token);
            })
            .catch(function () {});
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') refreshCsrf();
    });

    setInterval(refreshCsrf, 30 * 60 * 1000);
})();
</script>
</body>
</html>
