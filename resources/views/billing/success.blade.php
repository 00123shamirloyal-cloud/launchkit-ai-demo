<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout received · LaunchKit AI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <main class="mx-auto flex min-h-screen max-w-2xl items-center justify-center px-6 py-16">
        <section class="w-full rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl sm:p-12">
            <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-400/15 text-emerald-300">
                <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                </svg>
            </div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-300">Checkout returned</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Thanks for choosing LaunchKit AI.</h1>
            <p class="mt-4 leading-7 text-slate-300">
                Stripe has redirected you back to LaunchKit AI. Your account access is updated by a verified Stripe webhook,
                not by this page. It may take a moment for the payment confirmation to appear in your dashboard.
            </p>
            <a href="{{ url('/dashboard') }}" class="mt-8 inline-flex rounded-xl bg-white px-5 py-3 font-semibold text-slate-950 transition hover:bg-slate-200">
                Return to dashboard
            </a>
        </section>
    </main>
</body>
</html>
