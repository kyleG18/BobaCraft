<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>BobaCraft — Real-Time Milk Tea Bar & Drinks Dispatch Tracker</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-950 text-stone-100 min-h-screen flex flex-col antialiased selection:bg-amber-500 selection:text-black">

    <!-- Top Mobile Navigation Bar -->
    <header class="lg:hidden bg-stone-900 text-stone-100 px-4 py-3 border-b border-stone-800 flex items-center justify-between sticky top-0 z-40 shadow-md">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 flex items-center justify-center text-stone-950 font-bold shadow-inner text-xl">
                🧋
            </div>
            <div>
                <h1 class="text-base font-extrabold tracking-tight text-white">BobaCraft LIVE</h1>
                <p class="text-[10px] text-amber-400 -mt-0.5 font-mono">Tea Bar KDS</p>
            </div>
        </div>

        <button onclick="openNewOrderModal()" class="px-3 py-1.5 bg-amber-500 text-stone-950 text-xs font-bold rounded-lg hover:bg-amber-400 transition-colors shadow flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Order Boba</span>
        </button>
    </header>

    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside class="sidebar hidden lg:flex lg:flex-col w-64 p-6 justify-between select-none shrink-0 shadow-2xl">
            <div>
                <!-- Brand Identity -->
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-400 via-amber-500 to-amber-700 flex items-center justify-center text-stone-950 text-2xl font-bold shadow-lg shadow-amber-500/20">
                        🧋
                    </div>
                    <div>
                        <h2 class="text-lg font-black tracking-tight text-white flex items-center gap-1.5">
                            <span>BobaCraft</span>
                            <span class="text-[10px] bg-amber-500/20 text-amber-400 px-1.5 py-0.5 rounded border border-amber-500/30 font-mono font-bold">TEA BAR</span>
                        </h2>
                        <p class="text-xs text-stone-400">Craft Brews & Pearls</p>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="space-y-1.5">
                    <a href="#dashboard" class="sidebar-link active flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                            <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                            <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                            <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                        </svg>
                        <span>Barista KDS Display</span>
                    </a>

                    <a href="#orders" class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                        <span>All Drink Tickets</span>
                        <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-stone-800 text-amber-400 font-mono font-bold">
                            {{ $stats['total'] }}
                        </span>
                    </a>
                </nav>

                <!-- Milk Tea Bar Info Widget -->
                <div class="mt-8 p-4 rounded-xl bg-stone-900 border border-stone-800">
                    <div class="flex items-center gap-2 text-xs font-bold text-amber-400 uppercase tracking-wider mb-1">
                        <span>🍯 Main Shaker Bar</span>
                    </div>
                    <p class="text-xs text-stone-400 leading-relaxed">
                        Brown sugar pearl cooker active. Tea brewers 1–3 hot. Cup sealers online.
                    </p>
                    <div class="mt-3 pt-2 border-t border-stone-800 flex items-center justify-between text-[11px] text-stone-400 font-mono">
                        <span>Barista: Shift A</span>
                        <span class="text-emerald-400 font-semibold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Live Synced
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Footer -->
            <div class="pt-6 border-t border-stone-800 text-xs text-stone-400 space-y-1">
                <p class="font-semibold text-stone-200">IPT 102 — Lab Activity 7</p>
                <p class="text-[11px] text-stone-500 font-mono">Laravel Reverb WebSocket Live Sync</p>
                <form action="{{ route('orders.reset') }}" method="POST" class="pt-2">
                    @csrf
                    <button type="submit" onclick="return confirm('Reset all drink tickets to initial sample orders?')" class="w-full text-left text-[11px] text-amber-400 hover:text-amber-300 underline cursor-pointer">
                        ↺ Reset Sample Drinks
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-stone-950">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Header Section -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-stone-800">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">BobaCraft Milk Tea Bar</h1>
                            
                            <!-- WebSocket Connection Indicator -->
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-stone-900 border border-stone-800 shadow-sm">
                                <span id="connection-dot" class="pulse-dot connecting" title="WebSocket Status"></span>
                                <span id="connection-label" class="text-xs font-semibold text-amber-400">Connecting...</span>
                            </div>
                        </div>
                        <p class="text-sm text-stone-400 mt-1">
                            Real-time boba drink queue for cashier touchscreens, shaker stations, cup sealers, and customer lounge displays.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden md:flex flex-col text-right">
                            <span class="text-xs font-semibold text-stone-400 uppercase tracking-wider">Store Date</span>
                            <span class="text-xs font-medium text-stone-200">{{ now()->format('l, F j, Y') }}</span>
                        </div>

                        <!-- Reset Button -->
                        <form action="{{ route('orders.reset') }}" method="POST">
                            @csrf
                            <button type="submit" title="Reset sample records for demo" class="px-3.5 py-2 bg-stone-900 text-stone-300 text-xs font-medium rounded-lg border border-stone-800 hover:bg-stone-800 transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                                    <path d="M3 3v5h5"></path>
                                </svg>
                                <span>Reset Data</span>
                            </button>
                        </form>

                        <!-- Primary Action: Take Milk Tea Order -->
                        <button onclick="openNewOrderModal()" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 text-sm font-bold rounded-lg hover:from-amber-400 hover:to-amber-500 transition-all shadow-lg hover:shadow-amber-500/20 flex items-center gap-2 cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>Take Boba Order</span>
                        </button>
                    </div>
                </div>

                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="bg-emerald-950/60 border border-emerald-800 text-emerald-300 text-sm px-4 py-3 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span>✓</span>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white font-bold">&times;</button>
                    </div>
                @endif
                @if (session('info'))
                    <div class="bg-amber-950/60 border border-amber-800 text-amber-300 text-sm px-4 py-3 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span>ℹ</span>
                            <span>{{ session('info') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-amber-400 hover:text-white font-bold">&times;</button>
                    </div>
                @endif

                <!-- Section: Summary Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Cups in Shop -->
                    <div class="bg-stone-900/90 rounded-xl p-5 border border-stone-800 shadow-sm hover:border-stone-700 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Total In Queue</span>
                            <div class="w-9 h-9 rounded-lg bg-stone-800 flex items-center justify-center text-stone-200 text-xl">
                                🧋
                            </div>
                        </div>
                        <p id="stat-total" class="text-3xl font-black text-white mt-2 font-mono">
                            {{ $stats['total'] }}
                        </p>
                        <span class="text-[11px] text-stone-400 mt-1 block">Active cups on barista bar rail</span>
                    </div>

                    <!-- Queued / Brewing (Pending) -->
                    <div class="bg-stone-900/90 rounded-xl p-5 border border-stone-800 shadow-sm hover:border-stone-700 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Brewing Base</span>
                            <div class="w-9 h-9 rounded-lg bg-amber-950/70 border border-amber-800 flex items-center justify-center text-amber-400 text-xl">
                                🍵
                            </div>
                        </div>
                        <p id="stat-pending" class="text-3xl font-black text-amber-400 mt-2 font-mono">
                            {{ $stats['pending'] }}
                        </p>
                        <span class="text-[11px] text-stone-400 mt-1 block">Tea extracted & ticket printed</span>
                    </div>

                    <!-- Shaking & Pearls (Preparing) -->
                    <div class="bg-stone-900/90 rounded-xl p-5 border border-stone-800 shadow-sm hover:border-stone-700 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-purple-400">Shaking & Pearls</span>
                            <div class="w-9 h-9 rounded-lg bg-purple-950/70 border border-purple-800 flex items-center justify-center text-purple-400 text-xl">
                                🍯
                            </div>
                        </div>
                        <p id="stat-preparing" class="text-3xl font-black text-purple-400 mt-2 font-mono">
                            {{ $stats['preparing'] }}
                        </p>
                        <span class="text-[11px] text-stone-400 mt-1 block">Boba scooped, ice shaker mixing</span>
                    </div>

                    <!-- Sealed & At Bar (Ready) -->
                    <div class="bg-stone-900/90 rounded-xl p-5 border border-stone-800 shadow-sm hover:border-stone-700 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Sealed & At Bar</span>
                            <div class="w-9 h-9 rounded-lg bg-emerald-950/70 border border-emerald-800 flex items-center justify-center text-emerald-400 text-xl">
                                ✨
                            </div>
                        </div>
                        <p id="stat-ready" class="text-3xl font-black text-emerald-400 mt-2 font-mono">
                            {{ $stats['ready'] }}
                        </p>
                        <span class="text-[11px] text-stone-400 mt-1 block">Sealed cup with straw ready</span>
                    </div>
                </div>

                <!-- Section: Barista Queue Table -->
                <div class="bg-stone-900/90 rounded-xl border border-stone-800 shadow-xl overflow-hidden">
                    <div class="p-5 border-b border-stone-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                <span>Live Milk Tea Rail & Barista Station</span>
                                <span class="text-xs bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded border border-amber-500/30 font-mono font-bold">LIVE KDS</span>
                            </h2>
                            <p class="text-xs text-stone-400">Real-time status broadcast across cashier, shaker station, sealer, and pickup counter</p>
                        </div>
                        <div class="text-xs text-stone-400 flex items-center gap-2 font-mono">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>WebSocket channel: <strong class="text-amber-400">orders</strong></span>
                        </div>
                    </div>

                    <!-- Desktop Service Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-800">
                            <thead class="bg-stone-950/90 text-left text-xs font-bold text-stone-400 uppercase tracking-wider">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">Cup #</th>
                                    <th scope="col" class="px-6 py-3.5">Customer & Drink Specifications</th>
                                    <th scope="col" class="px-6 py-3.5">Preparation Stage</th>
                                    <th scope="col" class="px-6 py-3.5">Order Placed</th>
                                    <th scope="col" class="px-6 py-3.5">Last Bar Scan</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Update Bar Station</th>
                                </tr>
                            </thead>
                            <tbody id="orders-table-body" class="divide-y divide-stone-800/80 bg-stone-900/50">
                                @forelse ($orders as $order)
                                    @php
                                        $displayLabel = match($order->status) {
                                            'Pending' => 'Order Queued / Brewing',
                                            'Preparing' => 'Shaking & Pearls',
                                            'Ready' => 'Sealed & At Bar',
                                            'Completed' => 'Claimed / Enjoy!',
                                            default => $order->status
                                        };
                                    @endphp
                                    <tr id="order-row-{{ $order->id }}" class="hover:bg-amber-950/20 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-amber-400 font-mono">
                                            #{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-stone-100">
                                            {{ $order->customer_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="badge-status order-status-badge {{ $order->status_badge_class }}">
                                                {{ $displayLabel }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-stone-400 font-mono">
                                            {{ $order->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-stone-400 order-updated-at font-mono">
                                            {{ $order->updated_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <div class="inline-flex items-center gap-2">
                                                <select 
                                                    data-order-id="{{ $order->id }}" 
                                                    data-current-status="{{ $order->status }}"
                                                    class="order-status-select text-xs font-semibold rounded-lg border border-stone-700 bg-stone-950 px-3 py-1.5 text-stone-200 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none transition-colors cursor-pointer"
                                                    onchange="handleStatusChange(this)">
                                                    <option value="Pending" @selected($order->status === 'Pending')>Order Queued / Brewing</option>
                                                    <option value="Preparing" @selected($order->status === 'Preparing')>Shaking & Pearls</option>
                                                    <option value="Ready" @selected($order->status === 'Ready')>Sealed & At Bar</option>
                                                    <option value="Completed" @selected($order->status === 'Completed')>Claimed / Enjoy!</option>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <!-- Handled in empty state -->
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Jobs List -->
                    <div id="orders-mobile-list" class="md:hidden p-4 space-y-3">
                        @foreach ($orders as $order)
                            @php
                                $displayLabel = match($order->status) {
                                    'Pending' => 'Order Queued / Brewing',
                                    'Preparing' => 'Shaking & Pearls',
                                    'Ready' => 'Sealed & At Bar',
                                    'Completed' => 'Claimed / Enjoy!',
                                    default => $order->status
                                };
                            @endphp
                            <div id="order-card-{{ $order->id }}" class="bg-stone-900 rounded-xl p-4 border border-stone-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-amber-400 font-mono">#{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</span>
                                        <span class="text-sm font-semibold text-stone-200">{{ $order->customer_name }}</span>
                                    </div>
                                    <span class="badge-status order-status-badge {{ $order->status_badge_class }}">
                                        {{ $displayLabel }}
                                    </span>
                                </div>
                                <div class="text-xs text-stone-400 flex justify-between font-mono">
                                    <span>Placed: {{ $order->created_at->format('h:i A') }}</span>
                                    <span>Bar: <span class="order-updated-at">{{ $order->updated_at->format('h:i A') }}</span></span>
                                </div>
                                <div class="pt-2 border-t border-stone-800">
                                    <label class="block text-xs font-medium text-stone-400 mb-1">Update Bar Station:</label>
                                    <select 
                                        data-order-id="{{ $order->id }}" 
                                        data-current-status="{{ $order->status }}"
                                        class="order-status-select w-full text-xs font-semibold rounded-lg border border-stone-700 bg-stone-950 px-3 py-2 text-stone-200 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                        onchange="handleStatusChange(this)">
                                        <option value="Pending" @selected($order->status === 'Pending')>Order Queued / Brewing</option>
                                        <option value="Preparing" @selected($order->status === 'Preparing')>Shaking & Pearls</option>
                                        <option value="Ready" @selected($order->status === 'Ready')>Sealed & At Bar</option>
                                        <option value="Completed" @selected($order->status === 'Completed')>Claimed / Enjoy!</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Empty State -->
                    <div id="empty-state" class="text-center py-12 px-4 {{ $orders->count() > 0 ? 'hidden' : '' }}">
                        <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-stone-950 border border-stone-800 flex items-center justify-center text-3xl">
                            🧋
                        </div>
                        <h3 class="text-lg font-bold text-white">No Boba Orders in Queue</h3>
                        <p class="text-sm text-stone-400 max-w-sm mx-auto mt-1 mb-4">
                            There are currently no active milk tea cups on the bar rail. Queue a customer drink or load sample cafe orders.
                        </p>
                        <div class="flex items-center justify-center gap-3">
                            <button onclick="openNewOrderModal()" class="px-4 py-2 bg-amber-500 text-stone-950 text-xs font-bold rounded-lg hover:bg-amber-400 transition cursor-pointer">
                                Queue Boba Order
                            </button>
                            <form action="{{ route('orders.reset') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-stone-800 text-stone-300 border border-stone-700 text-xs font-medium rounded-lg hover:bg-stone-700 transition cursor-pointer">
                                    Load Sample Drinks
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Live Demo Info Banner -->
                <div class="bg-stone-900/60 rounded-xl p-5 border border-stone-800">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center shrink-0 mt-0.5 font-bold text-2xl">
                            🧋
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-white">BobaCraft Real-Time Milk Tea Bar Dispatch System (IPT 102 Lab Activity 7)</h4>
                            <p class="text-xs text-stone-400 leading-relaxed">
                                <strong>Barista Screen A:</strong> Update any milk tea ticket (e.g., from <span class="text-amber-400 font-semibold">Order Queued / Brewing</span> to <span class="text-purple-400 font-semibold">Shaking & Pearls</span>).<br>
                                <strong>Customer Pickup Board Screen B:</strong> Notice the cup status badge, timestamp, and bar metrics update in real-time via <strong>Laravel Reverb WebSockets</strong> without refreshing the page!
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- New Job Order Modal -->
    <div id="new-order-modal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-stone-900 rounded-2xl max-w-md w-full border border-stone-800 shadow-2xl overflow-hidden">
            <div class="p-6 bg-stone-950 border-b border-stone-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-stone-950 flex items-center justify-center font-bold text-xl">
                        🧋
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Take Milk Tea Order</h3>
                        <p class="text-xs text-stone-400">Creates a new boba ticket with initial "Order Queued / Brewing" status</p>
                    </div>
                </div>
                <button onclick="closeNewOrderModal()" class="text-stone-400 hover:text-white text-xl font-bold p-1 cursor-pointer">&times;</button>
            </div>

            <form onsubmit="submitNewOrder(event)" class="p-6 space-y-4">
                <div>
                    <label for="customer_name_input" class="block text-xs font-bold text-stone-300 uppercase tracking-wider mb-1.5">
                        Customer & Drink Customization *
                    </label>
                    <input 
                        type="text" 
                        id="customer_name_input" 
                        name="customer_name" 
                        required 
                        placeholder="e.g. Cup #206 — Tiger Sugar Pearl Milk Tea (Large, 50% Sugar, Less Ice)" 
                        class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-stone-700 bg-stone-950 text-white placeholder-stone-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                    />
                    <p class="text-[11px] text-stone-400 mt-1">Status defaults to <span class="font-semibold text-amber-400">Order Queued / Brewing</span>.</p>
                </div>

                <div class="pt-4 border-t border-stone-800 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeNewOrderModal()" class="px-4 py-2 text-xs font-semibold text-stone-400 hover:text-white rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="submit_order_btn" class="px-5 py-2 bg-amber-500 text-stone-950 text-xs font-bold rounded-lg hover:bg-amber-400 transition shadow cursor-pointer">
                        Brew & Queue Drink
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

</body>
</html>
