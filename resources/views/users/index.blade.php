@extends('layouts.app')

@section('title', 'User & App Permissions')
@section('page_heading', 'Users & App Permissions')

@section('content')
<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-800">User Accounts & Access Control</h3>
            <p class="text-xs text-slate-500">Manage user logins and grant or revoke permissions to the Mobile App and Admin Panel.</p>
        </div>

        <a href="{{ route('users.create') }}"
           class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow transition">
            <i class="fa-solid fa-user-plus"></i>
            <span>Add New User</span>
        </a>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">User Details</th>
                        <th class="px-4 py-3.5">Role</th>
                        <th class="px-4 py-3.5 text-center">Mobile App Access</th>
                        <th class="px-4 py-3.5 text-center">Admin Panel Access</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Details -->
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-800 flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 text-xs font-bold">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div>{{ $user->name }}</div>
                                        <div class="text-xs text-slate-400 font-normal">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($user->role === 'admin')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                        <i class="fa-solid fa-shield-halved mr-1"></i> Administrator
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        <i class="fa-solid fa-user mr-1"></i> Operator / Staff
                                    </span>
                                @endif
                            </td>

                            <!-- App Permission Badge -->
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if ($user->can_access_app)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i>
                                        Permitted
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                        <i class="fa-solid fa-circle-xmark text-rose-600 text-[10px]"></i>
                                        Denied
                                    </span>
                                @endif
                            </td>

                            <!-- Admin Permission Badge -->
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if ($user->can_access_admin)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                        <i class="fa-solid fa-circle-check text-indigo-600 text-[10px]"></i>
                                        Full Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">
                                        No Access
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Deactivated
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('users.edit', $user) }}" title="Edit Permissions"
                                       class="p-2 rounded text-indigo-600 hover:bg-indigo-50 transition">
                                        <i class="fa-solid fa-user-pen"></i>
                                    </a>

                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                              onsubmit="return confirm('Are you sure you want to delete this user?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete User" class="p-2 rounded text-rose-600 hover:bg-rose-50 transition">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
