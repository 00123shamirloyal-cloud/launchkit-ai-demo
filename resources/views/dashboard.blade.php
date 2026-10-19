{{--
    LaunchKit AI — Agency Dashboard
    Save as: resources/views/dashboard.blade.php

    Assumptions:
    - Laravel's Vite build includes Tailwind CSS.
    - Alpine.js is loaded by resources/js/app.js or the CDN include below.
    - The demo data below is a fallback. Pass $stats and $projects from your controller in production.
    - Wire /ai/auto-invoice and the project URLs to your application's authorized routes/policies.
--}}

@php
    $dashboardStats = array_merge([
        'total_revenue' => '$48,290.00',
        'revenue_change' => '+12.8%',
        'active_projects' => 18,
        'active_projects_change' => '+3 this month',
        'outstanding_invoices' => '$12,450.00',
        'outstanding_invoices_count' => 7,
        'ai_tasks_executed' => '1,284',
        'ai_tasks_change' => '+18.2%',
    ], $stats ?? []);

    $demoProjects = [
        [
            'id' => 101,
            'name' => 'Website redesign & development',
            'client' => 'Northstar Studio',
            'status' => 'active',
            'progress' => 78,
            'budget' => '$8,400',
            'updated' => 'Updated 2 hours ago',
        ],
        [
            'id' => 102,
            'name' => 'E-commerce mobile experience',
            'client' => 'Meridian Goods',
            'status' => 'pending',
            'progress' => 24,
            'budget' => '$12,800',
            'updated' => 'Updated yesterday',
        ],
        [
            'id' => 103,
            'name' => 'AI customer support assistant',
            'client' => 'Forma Health',
            'status' => 'active',
            'progress' => 56,
            'budget' => '$6,250',
            'updated' => 'Updated 2 days ago',
        ],
        [
            'id' => 104,
            'name' => 'Brand identity & design system',
            'client' => 'Atlas Ventures',
            'status' => 'completed',
            'progress' => 100,
            'budget' => '$4,900',
            'updated' => 'Updated Oct 12',
        ],
    ];

    $rawProjects = $projects ?? $demoProjects;

    // Normalize arrays and Eloquent models into one predictable view structure.
    $dashboardProjects = collect($rawProjects)->map(function ($project) {
        $status = data_get($project, 'status', 'pending');

        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        $client = data_get($project, 'client.name')
            ?? data_get($project, 'client')
            ?? data_get($project, 'client_name', 'Unassigned client');

        if (! is_string($client)) {
            $client = data_get($client, 'name', 'Unassigned client');
        }

        $updated = data_get($project, 'updated', data_get($project, 'updated_at'));

        if ($updated instanceof \DateTimeInterface) {
            $updated = \Illuminate\Support\Carbon::instance($updated)->diffForHumans();
            $updated = 'Updated ' . $updated;
        }

        $normalizedStatus = strtolower((string) $status);
        $defaultProgress = match ($normalizedStatus) {
            'active' => 60,
            'completed' => 100,
            default => 0,
        };

        return [
            'id' => data_get($project, 'id', 0),
            'name' => data_get($project, 'name', 'Untitled project'),
            'client' => $client,
            'status' => $normalizedStatus,
            // Progress is estimated from status unless the app supplies a real progress field.
            'progress' => min(100, max(0, (int) data_get($project, 'progress', $defaultProgress))),
            'budget' => data_get($project, 'budget', data_get($project, 'amount', null)),
            'updated' => $updated ?: 'Recently updated',
        ];
    });

    $statusStyles = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20',
        'active' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/20',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20',
    ];

    $currentUser = auth()->user();
    $displayName = $currentUser?->name ?? 'Alex Morgan';
    $displayEmail = $currentUser?->email ?? 'alex@launchkit.studio';
    $notificationCount = $notificationCount ?? 3;
    $workspaces = $workspaces ?? [
        ['id' => 'all', 'name' => 'All workspaces'],
        ['id' => 'launchkit', 'name' => 'LaunchKit Studio'],
        ['id' => 'northstar', 'name' => 'Northstar Studio'],
    ];
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full" x-data="launchKitDashboard()" x-init="init()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard · LaunchKit AI</title>
    <meta name="theme-color" content="#090d1a">

    @vite(['resources/css/app.css'])

    {{-- This view loads Alpine once via CDN. If Alpine is bundled in app.js, remove this tag and load that bundle instead. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        :root { color-scheme: light; }
        .dark { color-scheme: dark; }
        body { font-family: 'DM Sans', sans-serif; }
        h1, h2, h3, .brand-font { font-family: 'Manrope', sans-serif; }
        .soft-grid {
            background-image: radial-gradient(rgba(100, 116, 139, .16) .7px, transparent .7px);
            background-size: 17px 17px;
        }
        .sidebar-scroll { scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, .25) transparent; }
        .sidebar-scroll::-webkit-scrollbar { width: 5px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, .25); border-radius: 999px; }
        .focus-ring:focus-visible { outline: 2px solid #8b5cf6; outline-offset: 3px; }
    </style>
</head>

<body class="min-h-full bg-slate-50 text-slate-900 antialiased transition-colors duration-200 dark:bg-[#090d1a] dark:text-slate-100">
    <div class="min-h-screen">
        {{-- Mobile sidebar backdrop --}}
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
        ></div>

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-white/10 bg-[#0d1222] text-slate-300 transition-all duration-300 ease-in-out lg:translate-x-0"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarCollapsed ? 'lg:w-20' : 'lg:w-72'
            ]"
            aria-label="Main navigation"
        >
            <div class="flex h-[78px] shrink-0 items-center justify-between border-b border-white/[0.07] px-5">
                <a href="{{ url('/dashboard') }}" class="flex min-w-0 items-center gap-3 focus-ring rounded-xl" aria-label="LaunchKit AI home">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[14px] bg-gradient-to-br from-violet-500 via-violet-600 to-indigo-600 shadow-lg shadow-violet-950/40 ring-1 ring-white/15">
                        <svg viewBox="0 0 32 32" class="h-6 w-6 text-white" fill="none" aria-hidden="true">
                            <path d="M16 3.5 28.5 10.7v10.6L16 28.5 3.5 21.3V10.7L16 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="m10.1 18.8 4.1-9.1 7.7 12.6-4.1-6.1-7.7 2.6Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="sidebar-label min-w-0" x-show="!sidebarCollapsed" x-transition.opacity>
                        <span class="brand-font block text-[15px] font-extrabold tracking-tight text-white">LaunchKit<span class="text-violet-400"> AI</span></span>
                        <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-[.2em] text-slate-500">Agency workspace</span>
                    </span>
                </a>
                <button @click="sidebarOpen = false" class="rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden focus-ring" aria-label="Close navigation">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                </button>
            </div>

            <div class="sidebar-scroll flex-1 overflow-y-auto px-3 py-6">
                <p class="sidebar-label mb-3 px-3 text-[10px] font-bold uppercase tracking-[.2em] text-slate-600" x-show="!sidebarCollapsed" x-transition.opacity>Workspace</p>
                <nav class="space-y-1" aria-label="Workspace">
                    <a href="{{ url('/dashboard') }}" class="group flex items-center gap-3 rounded-xl bg-violet-500/15 px-3 py-3 text-sm font-semibold text-violet-200 ring-1 ring-inset ring-violet-400/20 transition hover:bg-violet-500/20" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''" aria-current="page" title="Dashboard">
                        <svg class="h-[19px] w-[19px] shrink-0 text-violet-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.6"/><rect x="13.5" y="3.5" width="7" height="4" rx="1.6"/><rect x="13.5" y="10.5" width="7" height="10" rx="1.6"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.6"/></svg>
                        <span class="sidebar-label" x-show="!sidebarCollapsed" x-transition.opacity>Dashboard</span>
                        <span class="ml-auto h-1.5 w-1.5 rounded-full bg-violet-300" x-show="!sidebarCollapsed"></span>
                    </a>
                    <a href="{{ url('/projects') }}" class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-400 transition hover:bg-white/[0.06] hover:text-white" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''" title="Projects">
                        <svg class="h-[19px] w-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M8 4V2.8M16 4V2.8M3 9h18M8 13h3m-3 3h7" stroke-linecap="round"/></svg>
                        <span class="sidebar-label" x-show="!sidebarCollapsed" x-transition.opacity>Projects</span>
                        <span class="sidebar-label ml-auto rounded-md bg-white/[0.07] px-2 py-0.5 text-[10px] text-slate-400" x-show="!sidebarCollapsed">18</span>
                    </a>
                    <a href="{{ url('/invoices') }}" class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-400 transition hover:bg-white/[0.06] hover:text-white" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''" title="Invoices">
                        <svg class="h-[19px] w-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3.5h9l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 20V5a1.5 1.5 0 0 1 1-1.5Z"/><path d="M14.5 3.8V8H19M8 12h8M8 16h5" stroke-linecap="round"/></svg>
                        <span class="sidebar-label" x-show="!sidebarCollapsed" x-transition.opacity>Invoices</span>
                        <span class="sidebar-label ml-auto rounded-md bg-amber-400/10 px-2 py-0.5 text-[10px] font-semibold text-amber-300" x-show="!sidebarCollapsed">7 due</span>
                    </a>
                    <a href="{{ url('/ai-automation') }}" class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-400 transition hover:bg-white/[0.06] hover:text-white" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''" title="AI Automation">
                        <svg class="h-[19px] w-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v2.2M12 18.8V21M3 12h2.2M18.8 12H21M5.64 5.64l1.55 1.55m9.62 9.62 1.55 1.55m0-12.72-1.55 1.55m-9.62 9.62-1.55 1.55" stroke-linecap="round"/><circle cx="12" cy="12" r="5.2"/><path d="m12 9.5.8 1.7 1.7.8-1.7.8-.8 1.7-.8-1.7-1.7-.8 1.7-.8.8-1.7Z" fill="currentColor" stroke="none"/></svg>
                        <span class="sidebar-label" x-show="!sidebarCollapsed" x-transition.opacity>AI Automation</span>
                        <span class="ml-auto h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,.65)]" x-show="!sidebarCollapsed" title="AI services operational"></span>
                    </a>
                </nav>

                <p class="sidebar-label mb-3 mt-9 px-3 text-[10px] font-bold uppercase tracking-[.2em] text-slate-600" x-show="!sidebarCollapsed" x-transition.opacity>Manage</p>
                <nav class="space-y-1" aria-label="Manage">
                    <a href="{{ url('/settings') }}" class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-400 transition hover:bg-white/[0.06] hover:text-white" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''" title="Settings">
                        <svg class="h-[19px] w-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="m19.4 15 .1.1 1.3 1-1.4 2.4-1.6-.6a8 8 0 0 1-1.6.9l-.3 1.7h-2.8l-.3-1.7a8 8 0 0 1-1.6-.9l-1.6.6-1.4-2.4 1.3-1a7.7 7.7 0 0 1 0-1.9l-1.3-1 1.4-2.4 1.6.6a8 8 0 0 1 1.6-.9l.3-1.7h2.8l.3 1.7a8 8 0 0 1 1.6.9l1.6-.6 1.4 2.4-1.3 1a7.7 7.7 0 0 1-.1 1.8Z" stroke-linejoin="round"/></svg>
                        <span class="sidebar-label" x-show="!sidebarCollapsed" x-transition.opacity>Settings</span>
                    </a>
                </nav>

                <div class="sidebar-label mt-9 rounded-2xl border border-white/[0.08] bg-gradient-to-br from-white/[0.06] to-white/[0.02] p-4" x-show="!sidebarCollapsed" x-transition.opacity>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-slate-300">AI credits</span>
                        <span class="rounded-full bg-violet-400/15 px-2 py-0.5 text-[10px] font-bold text-violet-300">PRO</span>
                    </div>
                    <div class="mt-3 flex items-end justify-between gap-2">
                        <span class="brand-font text-xl font-bold text-white">7,420 <span class="text-xs font-medium text-slate-500">/ 10k</span></span>
                        <span class="text-[10px] text-slate-500">74%</span>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full w-[74%] rounded-full bg-gradient-to-r from-violet-500 to-indigo-400"></div></div>
                    <a href="{{ url('/settings/billing') }}" class="mt-4 flex items-center justify-between text-xs font-semibold text-violet-300 transition hover:text-violet-200">
                        Manage plan
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            </div>

            <div class="shrink-0 border-t border-white/[0.07] p-3">
                <button @click="sidebarCollapsed = !sidebarCollapsed" class="hidden w-full items-center gap-3 rounded-xl px-3 py-3 text-xs font-medium text-slate-500 transition hover:bg-white/[0.06] hover:text-slate-200 lg:flex" :class="sidebarCollapsed ? 'justify-center px-0' : ''" :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                    <svg class="h-[18px] w-[18px] shrink-0 transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.5 5 7.5 12l7 7M20 5v14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span x-show="!sidebarCollapsed" x-transition.opacity>Collapse sidebar</span>
                </button>
                <div class="flex items-center gap-3 rounded-xl px-3 py-3" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-rose-400 to-orange-300 text-xs font-bold text-white ring-2 ring-[#0d1222]">{{ strtoupper(substr($displayName, 0, 1)) }}</div>
                    <div class="sidebar-label min-w-0 flex-1" x-show="!sidebarCollapsed" x-transition.opacity>
                        <p class="truncate text-xs font-semibold text-white">{{ $displayName }}</p>
                        <p class="mt-0.5 truncate text-[10px] text-slate-500">Workspace owner</p>
                    </div>
                    <span class="sidebar-label text-slate-500" x-show="!sidebarCollapsed">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                    </span>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="min-h-screen transition-[padding] duration-300" :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'">
            {{-- Header --}}
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl dark:border-white/[0.07] dark:bg-[#0b1020]/85">
                <div class="flex h-[78px] items-center gap-3 px-4 sm:px-6 xl:px-9">
                    <button @click="sidebarOpen = true" class="rounded-xl border border-slate-200 p-2.5 text-slate-600 hover:bg-slate-50 focus-ring dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5 lg:hidden" aria-label="Open navigation">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
                    </button>

                    <div class="hidden items-center gap-2 text-xs text-slate-400 md:flex">
                        <span>Workspace</span>
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">Dashboard</span>
                    </div>

                    <div class="relative ml-auto hidden w-full max-w-[360px] lg:block">
                        <label for="dashboard-search" class="sr-only">Search workspace</label>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5" stroke-linecap="round"/></svg>
                        <input id="dashboard-search" x-model="searchQuery" type="search" placeholder="Search projects, clients..." class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/80 pl-10 pr-14 text-xs text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:bg-white focus:ring-4 focus:ring-violet-500/10 dark:border-white/[0.08] dark:bg-white/[0.03] dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-400/40 dark:focus:bg-white/[0.05]">
                        <kbd class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-400 dark:border-white/10 dark:bg-white/5 dark:text-slate-500">⌘ K</kbd>
                    </div>

                    <div class="relative" @click.outside="workspaceMenuOpen = false">
                        <button @click="workspaceMenuOpen = !workspaceMenuOpen; profileMenuOpen = false" class="flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus-ring dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.06] sm:px-3" aria-haspopup="true" :aria-expanded="workspaceMenuOpen">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-400/15 dark:text-violet-300">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                            </span>
                            <span class="hidden max-w-28 truncate sm:inline" x-text="selectedWorkspaceName"></span>
                            <svg class="h-3.5 w-3.5 text-slate-400 transition-transform" :class="workspaceMenuOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 10 5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div x-cloak x-show="workspaceMenuOpen" x-transition.origin.top.right @keydown.escape.window="workspaceMenuOpen = false" class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10 dark:border-white/10 dark:bg-[#151b2c] dark:shadow-black/30">
                            <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-[.16em] text-slate-400">Switch workspace</p>
                            @foreach ($workspaces as $workspace)
                                <button type="button" @click="selectedWorkspaceName = @js($workspace['name']); workspaceMenuOpen = false" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-white/5">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ strtoupper(substr($workspace['name'], 0, 1)) }}</span>
                                    <span class="flex-1 truncate">{{ $workspace['name'] }}</span>
                                    @if (($workspace['id'] ?? null) === 'all')
                                        <svg class="h-3.5 w-3.5 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <button @click="toggleDarkMode()" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:text-slate-800 focus-ring dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-300 dark:hover:bg-white/[0.06]" :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'" title="Toggle appearance">
                        <svg x-show="!darkMode" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" stroke-linecap="round"/></svg>
                        <svg x-cloak x-show="darkMode" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.5 15.2A8.4 8.4 0 0 1 8.8 3.5 8.5 8.5 0 1 0 20.5 15.2Z" stroke-linejoin="round"/></svg>
                    </button>

                    <div class="relative" @click.outside="notificationsOpen = false">
                        <button @click="notificationsOpen = !notificationsOpen; profileMenuOpen = false" class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:text-slate-800 focus-ring dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-300 dark:hover:bg-white/[0.06]" aria-label="Notifications" aria-haspopup="true" :aria-expanded="notificationsOpen">
                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @if ($notificationCount > 0)
                                <span class="absolute -right-1 -top-1 flex h-[17px] min-w-[17px] items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[9px] font-bold leading-none text-white dark:border-[#0b1020]">{{ $notificationCount > 9 ? '9+' : $notificationCount }}</span>
                            @endif
                        </button>
                        <div x-cloak x-show="notificationsOpen" x-transition.origin.top.right @keydown.escape.window="notificationsOpen = false" class="absolute right-0 mt-2 w-[300px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 dark:border-white/10 dark:bg-[#151b2c] dark:shadow-black/30 sm:w-[340px]">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 dark:border-white/[0.07]">
                                <div><p class="text-sm font-bold text-slate-900 dark:text-white">Notifications</p><p class="mt-0.5 text-[10px] text-slate-500">You're all caught up on the important stuff.</p></div>
                                <span class="rounded-full bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-700 dark:bg-violet-400/10 dark:text-violet-300">{{ $notificationCount }} new</span>
                            </div>
                            <div class="space-y-1 p-2">
                                <a href="{{ url('/invoices') }}" class="flex gap-3 rounded-xl p-3 transition hover:bg-slate-50 dark:hover:bg-white/5">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-300"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6m-6 4h6" stroke-linecap="round"/></svg></span>
                                    <span class="min-w-0 flex-1"><span class="block text-xs font-semibold text-slate-800 dark:text-slate-100">Invoice needs attention</span><span class="mt-1 block text-[11px] leading-relaxed text-slate-500">An invoice is coming up on its due date.</span><span class="mt-1.5 block text-[10px] text-slate-400">12 minutes ago</span></span>
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-violet-500"></span>
                                </a>
                                <a href="{{ url('/projects') }}" class="flex gap-3 rounded-xl p-3 transition hover:bg-slate-50 dark:hover:bg-white/5">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-300"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <span class="min-w-0 flex-1"><span class="block text-xs font-semibold text-slate-800 dark:text-slate-100">Project milestone reached</span><span class="mt-1 block text-[11px] leading-relaxed text-slate-500">A project has moved to its next milestone.</span><span class="mt-1.5 block text-[10px] text-slate-400">1 hour ago</span></span>
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-violet-500"></span>
                                </a>
                            </div>
                            <a href="{{ url('/notifications') }}" class="block border-t border-slate-100 px-4 py-3 text-center text-xs font-semibold text-violet-600 transition hover:bg-slate-50 dark:border-white/[0.07] dark:text-violet-300 dark:hover:bg-white/5">View all notifications</a>
                        </div>
                    </div>

                    <div class="relative" @click.outside="profileMenuOpen = false">
                        <button @click="profileMenuOpen = !profileMenuOpen; workspaceMenuOpen = false; notificationsOpen = false" class="flex items-center gap-2 rounded-xl p-1.5 transition hover:bg-slate-100 focus-ring dark:hover:bg-white/5" aria-haspopup="true" :aria-expanded="profileMenuOpen">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-rose-400 to-orange-300 text-xs font-bold text-white shadow-sm">{{ strtoupper(substr($displayName, 0, 1)) }}</span>
                            <span class="hidden max-w-28 text-left xl:block"><span class="block truncate text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $displayName }}</span><span class="mt-0.5 block truncate text-[10px] text-slate-400">Administrator</span></span>
                            <svg class="hidden h-3.5 w-3.5 text-slate-400 transition-transform xl:block" :class="profileMenuOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 10 5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div x-cloak x-show="profileMenuOpen" x-transition.origin.top.right @keydown.escape.window="profileMenuOpen = false" class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10 dark:border-white/10 dark:bg-[#151b2c] dark:shadow-black/30">
                            <div class="border-b border-slate-100 px-3 py-3 dark:border-white/[0.07]"><p class="truncate text-xs font-bold text-slate-900 dark:text-white">{{ $displayName }}</p><p class="mt-1 truncate text-[11px] text-slate-500">{{ $displayEmail }}</p></div>
                            <a href="{{ url('/settings/profile') }}" class="mt-1 flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-white/5"><svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.4-3.7 2.8-5.5 7-5.5s6.6 1.8 7 5.5" stroke-linecap="round"/></svg>My profile</a>
                            <a href="{{ url('/settings') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-white/5"><svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="m19 13.5 1.1.9-1.5 2.6-1.4-.5-1.7 1-0.2 1.5h-3l-.3-1.5-1.7-1-1.4.5-1.5-2.6 1.1-.9v-2.1l-1.1-.9 1.5-2.6 1.4.5 1.7-1 .3-1.5h3l.2 1.5 1.7 1 1.4-.5 1.5 2.6-1.1.9v2.1Z" stroke-linejoin="round"/></svg>Account settings</a>
                            @auth
                                <form method="POST" action="{{ url('/logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-400/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" stroke-linecap="round" stroke-linejoin="round"/></svg>Sign out</button>
                                </form>
                            @else
                                <a href="{{ url('/login') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-400/10">Sign in</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-[1600px] px-4 pb-12 pt-8 sm:px-6 xl:px-9">
                {{-- Page heading --}}
                <div class="mb-8 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold text-slate-400">
                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            <span>{{ strtoupper(now()->format('l, F j, Y')) }}</span>
                            <span class="text-slate-300 dark:text-slate-700">/</span>
                            <span class="text-violet-600 dark:text-violet-300">Live overview</span>
                        </div>
                        <h1 class="brand-font text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-[30px]">Good to see you, {{ explode(' ', trim($displayName))[0] }} <span aria-hidden="true">✦</span></h1>
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">Here's what's happening across your agency today. Let's make it a productive one.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus-ring dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-300 dark:hover:bg-white/[0.06]">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18" stroke-linecap="round"/></svg>
                            Last 30 days
                            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 10 5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" @click="invoiceModalOpen = true" class="group inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 text-xs font-bold text-white shadow-lg shadow-violet-600/20 transition hover:-translate-y-0.5 hover:from-violet-500 hover:to-indigo-500 hover:shadow-xl hover:shadow-violet-600/25 focus-ring">
                            <svg class="h-4 w-4 transition-transform group-hover:rotate-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18M3 12h18" stroke-linecap="round"/><path d="m7 7 10 10" stroke-linecap="round" opacity=".4"/></svg>
                            Trigger AI Auto-Invoice
                        </button>
                    </div>
                </div>

                {{-- Stat cards --}}
                <section aria-label="Key performance indicators" class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.025] transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/[0.06] dark:border-white/[0.07] dark:bg-[#111729] dark:shadow-black/10 dark:hover:shadow-black/25">
                        <div class="absolute -right-8 -top-9 h-28 w-28 rounded-full bg-emerald-400/10 blur-2xl transition group-hover:bg-emerald-400/15"></div>
                        <div class="relative flex items-start justify-between">
                            <div><p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total revenue</p><p class="brand-font mt-3 text-[25px] font-extrabold tracking-tight text-slate-950 dark:text-white">{{ $dashboardStats['total_revenue'] }}</p></div>
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18m5-13.5c0-1.7-2.2-2.7-5-2.7s-5 1-5 2.7 2.1 2.5 5 3.2 5 1.5 5 3.3-2.2 3-5 3-5-1.2-5-3" stroke-linecap="round"/></svg></span>
                        </div>
                        <div class="relative mt-5 flex items-center justify-between gap-3"><span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="m5 14 6-6 4 4 4-6" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $dashboardStats['revenue_change'] }}</span><span class="text-[10px] text-slate-400">vs. last month</span></div>
                        <svg class="absolute bottom-4 right-4 h-8 w-20 opacity-70" viewBox="0 0 80 32" fill="none" aria-hidden="true"><path d="M1 26 12 22 22 24 32 15 42 18 53 9 64 12 79 3" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 26 12 22 22 24 32 15 42 18 53 9 64 12 79 3V32H1Z" fill="url(#revenueFill)"/><defs><linearGradient id="revenueFill" x1="40" y1="3" x2="40" y2="32" gradientUnits="userSpaceOnUse"><stop stop-color="#10b981" stop-opacity=".22"/><stop offset="1" stop-color="#10b981" stop-opacity="0"/></linearGradient></defs></svg>
                    </article>

                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.025] transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/[0.06] dark:border-white/[0.07] dark:bg-[#111729] dark:shadow-black/10 dark:hover:shadow-black/25">
                        <div class="absolute -right-8 -top-9 h-28 w-28 rounded-full bg-sky-400/10 blur-2xl transition group-hover:bg-sky-400/15"></div>
                        <div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Active projects</p><p class="brand-font mt-3 text-[25px] font-extrabold tracking-tight text-slate-950 dark:text-white">{{ $dashboardStats['active_projects'] }}</p></div><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-400/10 dark:text-sky-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M3 9h18M8 4V2.5M16 4V2.5M8 13h3m-3 3h7" stroke-linecap="round"/></svg></span></div>
                        <div class="relative mt-5 flex items-center justify-between gap-3"><span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-1 text-[10px] font-bold text-sky-700 dark:bg-sky-400/10 dark:text-sky-300"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="m5 14 6-6 4 4 4-6" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $dashboardStats['active_projects_change'] }}</span><span class="text-[10px] text-slate-400">Across all clients</span></div>
                        <div class="absolute bottom-5 right-5 flex items-end gap-1 opacity-70" aria-hidden="true"><span class="h-3 w-1.5 rounded-t bg-sky-200 dark:bg-sky-500/30"></span><span class="h-5 w-1.5 rounded-t bg-sky-300 dark:bg-sky-500/40"></span><span class="h-4 w-1.5 rounded-t bg-sky-200 dark:bg-sky-500/30"></span><span class="h-7 w-1.5 rounded-t bg-sky-400 dark:bg-sky-400/70"></span><span class="h-6 w-1.5 rounded-t bg-sky-300 dark:bg-sky-500/50"></span><span class="h-8 w-1.5 rounded-t bg-sky-500 dark:bg-sky-400"></span></div>
                    </article>

                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.025] transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/[0.06] dark:border-white/[0.07] dark:bg-[#111729] dark:shadow-black/10 dark:hover:shadow-black/25">
                        <div class="absolute -right-8 -top-9 h-28 w-28 rounded-full bg-amber-400/10 blur-2xl transition group-hover:bg-amber-400/15"></div>
                        <div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Outstanding invoices</p><p class="brand-font mt-3 text-[25px] font-extrabold tracking-tight text-slate-950 dark:text-white">{{ $dashboardStats['outstanding_invoices'] }}</p></div><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3.5h9l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 20V5a1.5 1.5 0 0 1 1-1.5Z"/><path d="M14.5 3.8V8H19M8 12h8M8 16h5" stroke-linecap="round"/></svg></span></div>
                        <div class="relative mt-5 flex items-center justify-between gap-3"><span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-[10px] font-bold text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">{{ $dashboardStats['outstanding_invoices_count'] }} awaiting payment</span><a href="{{ url('/invoices') }}" class="text-[10px] font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-300">Review <span aria-hidden="true">→</span></a></div>
                    </article>

                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.025] transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/[0.06] dark:border-white/[0.07] dark:bg-[#111729] dark:shadow-black/10 dark:hover:shadow-black/25">
                        <div class="absolute -right-8 -top-9 h-28 w-28 rounded-full bg-violet-400/10 blur-2xl transition group-hover:bg-violet-400/15"></div>
                        <div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold text-slate-500 dark:text-slate-400">AI tasks executed</p><p class="brand-font mt-3 text-[25px] font-extrabold tracking-tight text-slate-950 dark:text-white">{{ $dashboardStats['ai_tasks_executed'] }}</p></div><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 2 1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8L12 2Z" stroke-linejoin="round"/><path d="m19 15 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z" stroke-linejoin="round"/></svg></span></div>
                        <div class="relative mt-5 flex items-center justify-between gap-3"><span class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-700 dark:bg-violet-400/10 dark:text-violet-300"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="m5 14 6-6 4 4 4-6" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $dashboardStats['ai_tasks_change'] }}</span><span class="text-[10px] text-slate-400">This month</span></div>
                    </article>
                </section>

                {{-- Secondary insight row --}}
                <section class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-[1.45fr_1fr]">
                    <article class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/[0.07] dark:bg-[#111729] sm:p-6">
                        <div class="absolute right-0 top-0 h-44 w-64 soft-grid opacity-50 [mask-image:linear-gradient(to_left,black,transparent)] dark:opacity-30"></div>
                        <div class="relative flex flex-wrap items-start justify-between gap-4">
                            <div><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-300"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 17 9 11l4 4 8-9" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 6h6v6" stroke-linecap="round" stroke-linejoin="round"/></svg></span><h2 class="text-sm font-bold text-slate-900 dark:text-white">Revenue performance</h2></div><p class="mt-2 text-xs text-slate-500 dark:text-slate-400">A quick look at your agency's momentum.</p></div>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50/80 px-2.5 py-1.5 text-[10px] font-bold text-emerald-700 dark:border-emerald-400/15 dark:bg-emerald-400/10 dark:text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Healthy growth</span>
                        </div>
                        <div class="relative mt-6 grid grid-cols-2 gap-5 sm:grid-cols-3">
                            <div><p class="text-[10px] font-semibold uppercase tracking-[.13em] text-slate-400">Collected</p><p class="brand-font mt-1.5 text-lg font-extrabold text-slate-900 dark:text-white">$32,840</p><p class="mt-1 text-[10px] text-slate-500">68% of target</p></div>
                            <div><p class="text-[10px] font-semibold uppercase tracking-[.13em] text-slate-400">Pipeline</p><p class="brand-font mt-1.5 text-lg font-extrabold text-slate-900 dark:text-white">$21,600</p><p class="mt-1 text-[10px] text-slate-500">Across 9 proposals</p></div>
                            <div class="col-span-2 sm:col-span-1"><div class="mb-2 flex items-center justify-between"><p class="text-[10px] font-semibold uppercase tracking-[.13em] text-slate-400">Monthly target</p><span class="text-[10px] font-bold text-slate-600 dark:text-slate-300">68%</span></div><div class="h-2 rounded-full bg-slate-100 dark:bg-white/[0.07]"><div class="h-2 w-[68%] rounded-full bg-gradient-to-r from-emerald-400 to-teal-500"></div></div><p class="mt-2 text-[10px] text-slate-500">$15,160 left to goal</p></div>
                        </div>
                    </article>

                    <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#201648] via-[#282060] to-[#34236f] p-5 text-white shadow-lg shadow-indigo-950/10 sm:p-6">
                        <div class="absolute -right-8 -top-12 h-40 w-40 rounded-full border-[26px] border-white/[0.04]"></div><div class="absolute -bottom-16 right-20 h-40 w-40 rounded-full bg-violet-400/15 blur-3xl"></div>
                        <div class="relative flex items-start justify-between gap-3"><div><span class="inline-flex items-center gap-1.5 rounded-full border border-violet-300/20 bg-white/[0.08] px-2.5 py-1 text-[10px] font-semibold text-violet-100"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>AI workspace online</span><h2 class="brand-font mt-4 text-lg font-extrabold tracking-tight">Let AI handle the busywork.</h2><p class="mt-1.5 max-w-xs text-xs leading-relaxed text-violet-100/70">Automate repetitive agency tasks so your team can focus on great client work.</p></div><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.08]"><svg class="h-6 w-6 text-violet-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m12 2 1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8L12 2Z" stroke-linejoin="round"/><path d="m19 15 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z" stroke-linejoin="round"/></svg></span></div>
                        <div class="relative mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-white/10 pt-4"><div><p class="text-[10px] text-violet-200/60">Time saved this month</p><p class="mt-1 text-sm font-bold">42.5 hours</p></div><div class="h-7 w-px bg-white/10"></div><div><p class="text-[10px] text-violet-200/60">Successful runs</p><p class="mt-1 text-sm font-bold">98.6%</p></div><button @click="invoiceModalOpen = true" class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-[10px] font-bold text-indigo-800 transition hover:bg-violet-50 focus-ring">Run an automation <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></button></div>
                    </article>
                </section>

                {{-- Recent projects --}}
                <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/[0.025] dark:border-white/[0.07] dark:bg-[#111729] dark:shadow-black/10" aria-labelledby="recent-projects-heading">
                    <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 dark:border-white/[0.07] sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div><div class="flex items-center gap-2.5"><h2 id="recent-projects-heading" class="brand-font text-base font-extrabold tracking-tight text-slate-950 dark:text-white">Recent projects</h2><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-white/[0.07] dark:text-slate-400">{{ $dashboardProjects->count() }}</span></div><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Track delivery, ownership, and progress at a glance.</p></div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="inline-flex h-9 items-center gap-2 rounded-xl border border-slate-200 px-3 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50 focus-ring dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5"><svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M7 12h10m-7 6h4" stroke-linecap="round"/></svg>Filter</button>
                            <a href="{{ url('/projects') }}" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-slate-900 px-3 text-[11px] font-semibold text-white transition hover:bg-slate-700 focus-ring dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">All projects <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px] border-collapse text-left">
                            <thead>
                                <tr class="bg-slate-50/80 dark:bg-white/[0.025]">
                                    <th scope="col" class="px-6 py-3.5 text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Project name</th>
                                    <th scope="col" class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Client</th>
                                    <th scope="col" class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Status</th>
                                    <th scope="col" class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Progress</th>
                                    <th scope="col" class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Budget</th>
                                    <th scope="col" class="px-6 py-3.5 text-right text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.055]">
                                @forelse ($dashboardProjects as $project)
                                    @php
                                        $statusValue = $project['status'];
                                        $statusClass = $statusStyles[$statusValue] ?? $statusStyles['pending'];
                                        $clientInitials = collect(explode(' ', $project['client']))
                                            ->filter()
                                            ->take(2)
                                            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                            ->implode('');
                                    @endphp
                                    <tr data-project-row data-project-name="{{ strtolower($project['name']) }}" data-client-name="{{ strtolower($project['client']) }}" x-show="matchesProject($el.dataset.projectName, $el.dataset.clientName)" class="group transition hover:bg-slate-50/80 dark:hover:bg-white/[0.025]">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3.5">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ ['bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-300', 'bg-sky-50 text-sky-600 dark:bg-sky-400/10 dark:text-sky-300', 'bg-rose-50 text-rose-600 dark:bg-rose-400/10 dark:text-rose-300', 'bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-300'][$loop->index % 4] }}">
                                                    @if ($loop->index % 4 === 0)
                                                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 4V2.5M16 4V2.5" stroke-linecap="round"/></svg>
                                                    @elseif ($loop->index % 4 === 1)
                                                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5" stroke-linecap="round"/></svg>
                                                    @elseif ($loop->index % 4 === 2)
                                                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3 20 7.5v9L12 21l-8-4.5v-9L12 3Z" stroke-linejoin="round"/><path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    @else
                                                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 19.5V5.8A1.8 1.8 0 0 1 5.8 4h12.4A1.8 1.8 0 0 1 20 5.8v13.7l-4-2-4 2-4-2-4 2Z" stroke-linejoin="round"/><path d="M8 8h8M8 12h6" stroke-linecap="round"/></svg>
                                                    @endif
                                                </span>
                                                <div class="min-w-0"><a href="{{ url('/projects/' . $project['id']) }}" class="block max-w-[280px] truncate text-xs font-bold text-slate-800 transition hover:text-violet-700 dark:text-slate-100 dark:hover:text-violet-300">{{ $project['name'] }}</a><p class="mt-1 truncate text-[10px] text-slate-400">{{ $project['updated'] }}</p></div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4"><div class="flex items-center gap-2.5"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[9px] font-bold text-slate-600 dark:bg-white/[0.08] dark:text-slate-300">{{ $clientInitials ?: 'CL' }}</span><span class="max-w-[150px] truncate text-xs font-medium text-slate-600 dark:text-slate-300">{{ $project['client'] }}</span></div></td>
                                        <td class="px-4 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-[10px] font-bold ring-1 ring-inset {{ $statusClass }}"><span class="h-1.5 w-1.5 rounded-full {{ $statusValue === 'completed' ? 'bg-emerald-500' : ($statusValue === 'active' ? 'bg-sky-500' : 'bg-amber-500') }}"></span>{{ ucfirst(str_replace('_', ' ', $statusValue)) }}</span></td>
                                        <td class="px-4 py-4"><div class="w-32"><div class="mb-2 flex items-center justify-between gap-3"><span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300">{{ $project['progress'] }}%</span><span class="text-[9px] text-slate-400">complete</span></div><div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/[0.08]"><div class="h-full rounded-full transition-all duration-500 {{ $project['progress'] === 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-violet-500 to-indigo-500' }}" style="width: {{ $project['progress'] }}%"></div></div></div></td>
                                        <td class="px-4 py-4"><span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ is_numeric($project['budget']) ? '$' . number_format((float) $project['budget'], 2) : ($project['budget'] ?: '—') }}</span></td>
                                        <td class="px-6 py-4"><div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ url('/projects/' . $project['id']) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2.5 text-[10px] font-semibold text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700 focus-ring dark:border-white/10 dark:text-slate-300 dark:hover:border-violet-400/20 dark:hover:bg-violet-400/10 dark:hover:text-violet-300">View</a>
                                            <a href="{{ url('/projects/' . $project['id'] . '/edit') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2.5 text-[10px] font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-ring dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5">Edit</a>
                                            <form method="POST" action="{{ url('/projects/' . $project['id']) }}" onsubmit="return confirm('Delete this project? This action cannot be undone.');" class="inline-flex">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-rose-100 px-2.5 text-[10px] font-semibold text-rose-600 transition hover:border-rose-200 hover:bg-rose-50 focus-ring dark:border-rose-400/15 dark:text-rose-300 dark:hover:bg-rose-400/10">Delete</button>
                                            </form>
                                        </div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-6 py-16 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-white/[0.06]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/></svg></span><p class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-100">No projects yet</p><p class="mt-1 text-xs text-slate-500">Create your first project to see it here.</p><a href="{{ url('/projects/create') }}" class="mt-4 inline-flex rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-violet-500">Create project</a></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 dark:border-white/[0.07] sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <p class="text-[11px] text-slate-400">Showing <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $dashboardProjects->count() }}</span> recent projects</p>
                        <div class="flex items-center gap-2"><button type="button" disabled class="rounded-lg border border-slate-200 px-3 py-1.5 text-[10px] font-semibold text-slate-300 dark:border-white/10 dark:text-slate-600">Previous</button><button type="button" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 text-[10px] font-bold text-violet-700 dark:border-violet-400/20 dark:bg-violet-400/10 dark:text-violet-300">1</button><button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[10px] font-semibold text-slate-500 transition hover:bg-slate-50 dark:border-white/10 dark:text-slate-400 dark:hover:bg-white/5">2</button><button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[10px] font-semibold text-slate-500 transition hover:bg-slate-50 dark:border-white/10 dark:text-slate-400 dark:hover:bg-white/5">Next</button></div>
                    </div>
                </section>

                <footer class="mt-8 flex flex-col gap-2 border-t border-slate-200/80 pt-5 text-[10px] text-slate-400 dark:border-white/[0.07] sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ now()->year }} LaunchKit AI. Built for teams that build what's next.</p>
                    <div class="flex items-center gap-4"><a href="{{ url('/help') }}" class="transition hover:text-slate-600 dark:hover:text-slate-200">Help center</a><a href="{{ url('/privacy') }}" class="transition hover:text-slate-600 dark:hover:text-slate-200">Privacy</a><span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>All systems operational</span></div>
                </footer>
            </main>
        </div>
    </div>

    {{-- AI Auto-Invoice modal --}}
    <div x-cloak x-show="invoiceModalOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-end justify-center overflow-y-auto sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="invoice-modal-title" @keydown.escape.window="invoiceModalOpen = false">
        <div class="fixed inset-0 bg-slate-950/65 backdrop-blur-sm" @click="invoiceModalOpen = false" aria-hidden="true"></div>
        <div x-show="invoiceModalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-5 scale-[.98] opacity-0" x-transition:enter-end="translate-y-0 scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 scale-100 opacity-100" x-transition:leave-end="translate-y-3 scale-[.99] opacity-0" class="relative w-full max-w-xl overflow-hidden rounded-t-[28px] border border-white/10 bg-white shadow-2xl shadow-slate-950/25 dark:bg-[#111729] sm:rounded-[28px]" @click.stop>
            <div class="relative overflow-hidden bg-gradient-to-br from-[#21164a] via-[#30216c] to-[#4b2991] px-6 pb-6 pt-6 text-white sm:px-8">
                <div class="absolute -right-9 -top-12 h-44 w-44 rounded-full border-[28px] border-white/[0.04]"></div><div class="absolute -bottom-16 right-24 h-36 w-36 rounded-full bg-violet-300/15 blur-3xl"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div><span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/[0.08] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.12em] text-violet-100"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 2 1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8L12 2Z" stroke-linejoin="round"/></svg>AI-powered workflow</span><h2 id="invoice-modal-title" class="brand-font mt-3 text-xl font-extrabold tracking-tight">Create an invoice, automatically.</h2><p class="mt-1.5 max-w-sm text-xs leading-relaxed text-violet-100/75">Let LaunchKit AI prepare an invoice draft from your project details. Review it before it reaches your client.</p></div>
                    <button @click="invoiceModalOpen = false" type="button" class="rounded-xl border border-white/10 bg-white/[0.07] p-2 text-white/70 transition hover:bg-white/[0.14] hover:text-white focus-ring" aria-label="Close modal"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg></button>
                </div>
                <div class="relative mt-5 flex flex-wrap gap-x-4 gap-y-2 text-[10px] font-medium text-violet-100/80"><span class="inline-flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>Draft first, send after review</span><span class="inline-flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>Client-ready details</span></div>
            </div>

            <form method="POST" action="{{ url('/ai/auto-invoice') }}" class="px-6 py-6 sm:px-8" x-on:submit="invoiceSubmitting = true">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="invoice-project" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Project <span class="text-rose-500">*</span></label>
                        <select id="invoice-project" name="project_id" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none transition focus:border-violet-400 focus:ring-4 focus:ring-violet-500/10 dark:border-white/10 dark:bg-[#0b1020] dark:text-slate-100">
                            <option value="">Choose a project...</option>
                            @foreach ($dashboardProjects as $project)
                                <option value="{{ $project['id'] }}">{{ $project['name'] }} — {{ $project['client'] }}</option>
                            @endforeach
                        </select>
                        @error('project_id')<p class="mt-1.5 text-[11px] text-rose-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="invoice-amount" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Invoice amount (USD) <span class="text-rose-500">*</span></label>
                        <div class="relative"><span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">$</span><input id="invoice-amount" type="number" name="amount" min="0.01" step="0.01" required placeholder="2500.00" class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-8 pr-3 text-xs text-slate-700 outline-none transition placeholder:text-slate-300 focus:border-violet-400 focus:ring-4 focus:ring-violet-500/10 dark:border-white/10 dark:bg-[#0b1020] dark:text-slate-100 dark:placeholder:text-slate-600"></div>
                        @error('amount')<p class="mt-1.5 text-[11px] text-rose-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="invoice-due-date" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Payment due date <span class="text-rose-500">*</span></label>
                        <input id="invoice-due-date" type="date" name="due_date" min="{{ now()->toDateString() }}" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none transition focus:border-violet-400 focus:ring-4 focus:ring-violet-500/10 dark:border-white/10 dark:bg-[#0b1020] dark:text-slate-100">
                        @error('due_date')<p class="mt-1.5 text-[11px] text-rose-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="invoice-notes" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Notes for the AI <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="invoice-notes" name="notes" rows="3" maxlength="2000" placeholder="e.g. Include the second milestone, note the maintenance add-on..." class="w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-xs leading-relaxed text-slate-700 outline-none transition placeholder:text-slate-300 focus:border-violet-400 focus:ring-4 focus:ring-violet-500/10 dark:border-white/10 dark:bg-[#0b1020] dark:text-slate-100 dark:placeholder:text-slate-600"></textarea>
                        @error('notes')<p class="mt-1.5 text-[11px] text-rose-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5 flex items-start gap-3 rounded-xl border border-violet-100 bg-violet-50/70 p-3.5 dark:border-violet-400/15 dark:bg-violet-400/[0.06]">
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-400/15 dark:text-violet-300"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18M3 12h18" stroke-linecap="round"/><circle cx="12" cy="12" r="9"/></svg></span>
                    <div><p class="text-[11px] font-bold text-violet-950 dark:text-violet-100">You'll stay in control</p><p class="mt-1 text-[10px] leading-relaxed text-violet-800/75 dark:text-violet-200/70">The workflow should create a draft for review. Only send or email the invoice after the authorized user approves it.</p></div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2.5 border-t border-slate-100 pt-5 dark:border-white/[0.07] sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[10px] text-slate-400">AI output should be validated before billing.</p>
                    <div class="flex gap-2.5"><button type="button" @click="invoiceModalOpen = false" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 px-4 text-xs font-bold text-slate-600 transition hover:bg-slate-50 focus-ring dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5">Cancel</button><button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 text-xs font-bold text-white shadow-md shadow-violet-600/20 transition hover:from-violet-500 hover:to-indigo-500 focus-ring disabled:cursor-wait disabled:opacity-70" :disabled="invoiceSubmitting"><svg x-show="!invoiceSubmitting" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 2 1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8L12 2Z" stroke-linejoin="round"/></svg><svg x-cloak x-show="invoiceSubmitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg><span x-text="invoiceSubmitting ? 'Preparing draft...' : 'Generate invoice draft'"></span></button></div>
                </div>
            </form>
        </div>
    </div>
    <script>
        function launchKitDashboard() {
            return {
                sidebarOpen: false,
                sidebarCollapsed: false,
                darkMode: false,
                workspaceMenuOpen: false,
                notificationsOpen: false,
                profileMenuOpen: false,
                invoiceModalOpen: false,
                invoiceSubmitting: false,
                selectedWorkspaceName: 'All workspaces',
                searchQuery: '',

                init() {
                    try {
                        const storedTheme = window.localStorage.getItem('launchkit-theme');
                        this.darkMode = storedTheme
                            ? storedTheme === 'dark'
                            : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    } catch (error) {
                        this.darkMode = false;
                    }

                    document.documentElement.classList.toggle('dark', this.darkMode);

                    this.$watch('darkMode', (value) => {
                        document.documentElement.classList.toggle('dark', value);
                        try {
                            window.localStorage.setItem('launchkit-theme', value ? 'dark' : 'light');
                        } catch (error) {
                            // Theme toggling still works when local storage is unavailable.
                        }
                    });

                    window.addEventListener('keydown', (event) => {
                        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                            event.preventDefault();
                            const search = document.getElementById('dashboard-search');
                            if (search) search.focus();
                        }
                    });
                },

                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                },

                matchesProject(name, client) {
                    const query = this.searchQuery.trim().toLowerCase();
                    return query === '' || name.includes(query) || client.includes(query);
                },
            };
        }
    </script>
</body>
</html>
