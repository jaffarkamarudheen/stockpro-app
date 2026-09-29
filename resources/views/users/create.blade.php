@extends('layouts.app')

@section('title', 'Add New User')
@section('page_heading', 'Add New User & Set Permissions')

@section('content')
<div class="max-w-2xl mx-auto" x-data="{ role: 'operator' }">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-800">Create User Account</h3>
            <p class="text-sm text-slate-500">Configure login credentials and grant access permissions.</p>
        </div>
        <a href="{{ route('users.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Users
        </a>
    </div>

    <form method="POST" action="{{ route('users.store') }}" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-5">
        @csrf

        <div class="space-y-4">
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                    Full Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       placeholder="e.g. Rahul Sharma"
                       class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                    Email Address <span class="text-rose-500">*</span>
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       placeholder="rahul@example.com"
                       class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                    Password <span class="text-rose-500">*</span>
                </label>
                <input type="password" id="password" name="password" required
                       placeholder="At least 6 characters"
                       class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label for="role" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                    Account Role <span class="text-rose-500">*</span>
                </label>
                <select id="role" name="role" x-model="role"
                        class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="operator">Operator / Staff (Field / App Worker)</option>
                    <option value="admin">Administrator (Full System Access)</option>
                </select>
            </div>

            <!-- Permission Checkboxes -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 block">Access Permissions</span>

                <!-- App Permission -->
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="can_access_app" value="1" checked
                           class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-800">Grant Mobile App Access</span>
                        <p class="text-xs text-slate-500">Allows user to log into the Mobile Camera & Stock App to perform Check-In and Check-Out.</p>
                    </div>
                </label>

                <!-- Admin Permission -->
                <label class="flex items-start gap-3 cursor-pointer" x-show="role === 'operator'">
                    <input type="checkbox" name="can_access_admin" value="1"
                           class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-800">Grant Admin Panel Access</span>
                        <p class="text-xs text-slate-500">Allows user to view products, reports, and checkouts ledger.</p>
                    </div>
                </label>

                <!-- Active Toggle -->
                <label class="flex items-start gap-3 cursor-pointer pt-2 border-t border-slate-200">
                    <input type="checkbox" name="is_active" value="1" checked
                           class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-800">Account is Active</span>
                        <p class="text-xs text-slate-500">Uncheck to immediately disable login for this user.</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3">
            <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
            <button type="submit"
                    class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow transition flex items-center gap-2">
                <i class="fa-solid fa-save"></i> Save User & Permissions
            </button>
        </div>
    </form>
</div>
@endsection
