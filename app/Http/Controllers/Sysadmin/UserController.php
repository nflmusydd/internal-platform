<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Exports\UsersExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        return view('sysadmin.users.index');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'is_active' => 'required|in:0,1',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'password' => $validated['password'],
                'is_active' => $validated['is_active'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.successfully_created')),
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User Store Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => ucfirst(__('general.failed_to_save'))
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();
        $isSuperAdmin = $request->user()->hasRole('SUPER ADMIN');
        $isSelf = $request->user()->id === $user->id;

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id . ',ulid',
            'is_active' => 'required|in:0,1',
        ];

        $canChangePassword = $isSuperAdmin || $isSelf;
        if ($canChangePassword && $request->filled('password')) {
            $rules['password'] = 'required|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        if ($isSelf && $validated['is_active'] === '0') {
            return response()->json([
                'success' => false,
                'message' => ucfirst(__('sysadmin/users/index.cannot_deactivate_self'))
            ], 403);
        }

        $trimmedName = trim($validated['name']);
        $trimmedEmail = strtolower(trim($validated['email']));

        if ($user->name === $trimmedName
            && strtolower(trim($user->email)) === $trimmedEmail
            && (string) $user->is_active === (string) $validated['is_active']
            && !$request->filled('password')
        ) {
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.no_changes')),
                'no_change' => true,
            ]);
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'name' => $trimmedName,
                'email' => $trimmedEmail,
                'is_active' => $validated['is_active'],
                'updated_by' => auth()->id(),
            ];

            if ($canChangePassword && $request->filled('password')) {
                $updateData['password'] = $validated['password'];
            }

            $user->update($updateData);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.successfully_updated')),
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User Update Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => ucfirst(__('general.failed_to_update'))
            ], 500);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function destroy(Request $request, string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();

        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => ucfirst(__('sysadmin/users/index.cannot_delete_self'))
            ], 403);
        }

        DB::beginTransaction();
        try {
            $user->roles()->detach();
            $user->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.successfully_deleted')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User Delete Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => ucfirst(__('general.failed_to_delete'))
            ], 500);
        }
    }

    public function getUsers()
    {
        $users = User::withCount('roles')
            ->with('roles:ulid,name')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $users]);
    }

    public function exportUsers(Request $request)
    {
        $users = User::withCount('roles');

        if ($request->filled('name')) {
            $users->where('name', 'LIKE', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $users->where('email', 'LIKE', '%' . $request->email . '%');
        }
        if ($request->filled('status')) {
            $users->where('is_active', $request->status);
        }
        if ($request->filled('created_at_from')) {
            $users->whereDate('created_at', '>=', $request->created_at_from);
        }
        if ($request->filled('created_at_to')) {
            $users->whereDate('created_at', '<=', $request->created_at_to);
        }
        if ($request->filled('updated_at_from')) {
            $users->whereDate('updated_at', '>=', $request->updated_at_from);
        }
        if ($request->filled('updated_at_to')) {
            $users->whereDate('updated_at', '<=', $request->updated_at_to);
        }

        $users = $users->orderBy('name')->get();
        return (new UsersExport($users))->download('users');
    }
}
