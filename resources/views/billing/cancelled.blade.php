<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout cancelled · LaunchKit AI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <main class="mx-auto flex min-h-screen max-w-2xl items-center justify-center px-6 py-16">
        <section class="w-full rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl sm:p-12">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Checkout cancelled</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">No payment was confirmed.</h1>
            <p class="mt-4 leading-7 text-slate-300">You can return to your account and restart checkout whenever you're ready.</p>
            <a href="{{ url('/dashboard') }}" class="mt-8 inline-flex rounded-xl bg-white px-5 py-3 font-semibold text-slate-950 transition hover:bg-slate-200">
                Return to dashboard
            </a>
        </section>
    </main>
</body>
</html>
