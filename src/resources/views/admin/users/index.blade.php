@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">Users List</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th class="ps-4">Avatar</th>
                        <th>Nickname</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Role</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr wire:key="user-row-{{ $user->id }}" id="user-row-{{ $user->id }}">
                            <td class="ps-4">
                                @if($user->avatar_path)
                                    <img src="{{ Storage::disk('minio')->url($user->avatar_path) }}" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 1rem;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->status === \App\Enums\UserStatus::Active)
                                    <span class="badge bg-success">Active</span>
                                @elseif($user->status === \App\Enums\UserStatus::Banned)
                                    <span class="badge bg-danger">Banned</span>
                                @else
                                    <span class="badge bg-secondary">{{ $user->status->value ?? $user->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($user->id === auth()->id())
                                    <span class="badge bg-primary">
                                        You ({{ ucfirst($user->getRoleNames()->first() ?? 'User') }})
                                    </span>

                                @elseif($user->hasRole('admin') && !auth()->user()->hasRole('admin'))
                                    <span class="badge bg-danger">Admin</span>

                                @elseif($user->hasRole('manager') && !auth()->user()->hasRole('admin'))
                                    <span class="badge bg-warning">Manager</span>

                                @else
                                    <form action="{{ route('admin.users.assign-role', $user) }}" method="POST" class="d-flex align-items-center mb-0" autocomplete="off">
                                        @csrf
                                        @method('PATCH')

                                        <select name="role" id="role-user-{{ $user->id }}" class="form-select form-select-sm me-2" style="width: 130px;" autocomplete="off" required>
                                            <option value="" disabled {{ $user->roles->count() === 0 ? 'selected' : '' }}>No Role</option>

                                            @foreach(\App\Enums\UserRole::cases() as $role)
                                                @if(in_array($role->value, ['admin', 'manager']) && !auth()->user()->hasRole('admin'))
                                                    @continue
                                                @endif

                                                <option value="{{ $role->value }}" {{ $user->hasRole($role->value) ? 'selected' : '' }}>
                                                    {{ ucfirst($role->value) }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                    </form>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($user->id === auth()->id() || $user->hasRole(\App\Enums\UserRole::Admin->value))
                                    <button class="btn btn-sm btn-secondary" disabled title="Protected">Protected</button>
                                @else
                                    @if($user->status === \App\Enums\UserStatus::Active)
                                        <form action="{{ route('admin.users.ban', $user) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-danger">Ban</button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.users.unban', $user) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success">Unban</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endsection
