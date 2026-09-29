@extends('layouts.app')

@section('title', 'System Activity & Audit Logs')
@section('page_heading', 'System Activity & Audit Logs')

@section('content')
<div class="space-y-6">
    <!-- Top Bar & Summary -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-800">System Activity & Audit Trail</h3>
            <p class="text-xs text-slate-500">Track logins, stock check-ins, customer sales, and administrative modifications.</p>
        </div>

        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Live Audit Logging Active</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('activity.index') }}" class="flex flex-wrap items-center gap-3 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search action, description, user, or IP..."
                       class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <select name="action" class="py-2 px-3 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">All Action Types</option>
                @foreach ($actions as $act)
                    <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                @endforeach
            </select>

            <select name="user_id" class="py-2 px-3 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">All Users</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                Filter
            </button>

            @if (request()->hasAny(['search', 'action', 'user_id']))
                <a href="{{ route('activity.index') }}" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Activity Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Timestamp</th>
                        <th class="px-4 py-3.5">User</th>
                        <th class="px-4 py-3.5 text-center">Action</th>
                        <th class="px-4 py-3.5">Description</th>
                        <th class="px-4 py-3.5">IP & Device</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-500">
                                {{ $log->created_at->format('M d, Y - h:i:s A') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-bold text-slate-800">{{ $log->user_name }}</span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if (str_contains($log->action, 'LOGIN'))
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-right-to-bracket text-[10px] mr-1"></i> LOGIN
                                    </span>
                                @elseif (str_contains($log->action, 'CHECKOUT'))
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                        <i class="fa-solid fa-cart-shopping text-[10px] mr-1"></i> SALE / CHECKOUT
                                    </span>
                                @elseif (str_contains($log->action, 'CHECK_IN'))
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-800">
                                        <i class="fa-solid fa-arrow-down text-[10px] mr-1"></i> CHECK IN
                                    </span>
                                @elseif (str_contains($log->action, 'PRODUCT'))
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        <i class="fa-solid fa-box text-[10px] mr-1"></i> {{ $log->action }}
                                    </span>
                                @elseif (str_contains($log->action, 'USER'))
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                                        <i class="fa-solid fa-user-gear text-[10px] mr-1"></i> {{ $log->action }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ $log->action }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-700">
                                {{ $log->description ?: '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-400">
                                <div><code class="text-slate-600">{{ $log->ip_address }}</code></div>
                                <div class="text-[10px] truncate max-w-xs text-slate-400">{{ $log->user_agent }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-400">
                                No activity logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
