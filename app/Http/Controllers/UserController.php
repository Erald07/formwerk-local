<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
  private function getRoleNames($companyId)
  {
    $roles = Role::where("company_id", $companyId)->get()->toArray();
    $roleNames = array_map(function ($role) {
        return $role["name"];
    }, $roles);
    return $roleNames;
  }

  public function createEmployee(Request $request)
  {
    $companyId = $request->user()->company_id;
    $roleNames = $this->getRoleNames($companyId);

    $validator = Validator::make($request->all(), [
      'name' => ['required'],
      'email' => ['required'],
      'password' => ['required', 'min:6'],
      'role' => ['required', Rule::in($roleNames)],
    ]);

    if ($validator->fails()) {
      return redirect()
        ->route('settings', ['#user-management'])
        ->withErrors($validator, 'userManagement')
        ->withInput();
    }

    if (
      !$request->filled('name')
      || !$request->filled('email')
      || !$request->filled('password')
      || !$request->filled('role')
    ) {
      return redirect()->route('settings', ['#user-management']);
    }

    $role = Role::where('name', $request->input('role'))->first();
    $existingUser = User::withTrashed()->withoutGlobalScopes(['company', 'active'])->firstWhere("email", $request->input("email"));
    if ($existingUser) {
        if (
            $existingUser->company_id == $request->user()->company_id
            && $existingUser->deleted_at != null
        ) {
            $existingUser->name = $request->input('name');
            $existingUser->email = $request->input('email');
            $existingUser->password = Hash::make($request->input('password'));
            $existingUser->roles()->detach();
            $existingUser->roles()->attach($role->id);
            $existingUser->save();
            $existingUser->restore();

            return redirect()->route('settings', ['#user-management']);
        } else {
            $validator->errors()->add('email', __("User with this email already exists"));
            return redirect()
                ->route('settings', ['#user-management'])
                ->withErrors($validator, 'userManagement')
                ->withInput();
        }
    } else {
        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->save();
        $user->roles()->attach($role->id);

        return redirect()->route('settings', ['#user-management']);
    }
  }

  public function deleteEmployee(Request $request, $id)
  {
    $userToBeDeleted = User::firstWhere("id", $id);
    if (!$userToBeDeleted) {
        return response(__("User not found"), 404);
    }

    if ($request->user()->company_id !== $userToBeDeleted->company_id) {
        return response(__("You are not allowed to delete this user"), 403);
    }

    // $userToBeDeleted->roles()->detach();
    $userToBeDeleted->delete();

    return response("", 204);
  }

  public function changePassword(Request $request)
  {
    $user = $request->user();

    $validator = Validator::make($request->all(), [
      'old-password' => [
        'required',
        function ($attribute, $value, $fail) use ($user) {
          if (!Hash::check($value, $user->password)) {
            return $fail(__("The current password is not correct"));
          }
        }
      ],
      'new-password' => ['required', 'min:6'],
      'repeat-new-password' => ['required', 'min:6', 'same:new-password'],
    ]);

    if ($validator->fails()) {
      return redirect()
        ->route('settings', ['#change-password'])
        ->withErrors($validator, 'changePassword')
        ->withInput();
    }

    User::where('id', $user->id)
      ->update(['password' => Hash::make($request->input('new-password'))]);

    return redirect()->route('settings', ['#change-password']);
  }

  public function changeUserRole(Request $request, $id)
  {
    $companyId = $request->user()->company_id;
    $roleNames = $this->getRoleNames($companyId);

    $validator = Validator::make($request->input(), [
      'role' => ['required', Rule::in($roleNames)],
    ]);

    if ($validator->fails()) {
      return redirect()
        ->route('settings', ['#user-management'])
        ->withErrors($validator, 'userManagement')
        ->withInput();
    }

    $userToBeUpdated = User::firstWhere('id', $id);
    if (!$userToBeUpdated) {
        return response(404, __('User not found'));
    }

    if ($companyId !== $userToBeUpdated->company_id) {
        return response(__('You are not allowed to update this user'), 403);
    }

    $role = Role::where('name', $request->input('role'))
        ->where('company_id', $companyId)
        ->first();
    $userToBeUpdated->roles()->detach();
    $userToBeUpdated->roles()->attach($role->id);
    $userToBeUpdated->save();

    return response("", 204);
  }

  public function changeOtherUserPassword(Request $request, $id)
  {
    $validator = Validator::make($request->all(), [
      'password' => ['required', 'min:6'],
    ]);

    if ($validator->fails()) {
      return response($validator->errors()->first('password'), 400);
    }

    $companyId = $request->user()->company_id;
    $userToChangePassword = User::where("id", $id)
        ->where("company_id", $companyId)
        ->first();
    if (!$userToChangePassword) {
        return response(404, __("User not found"));
    }

    $userToChangePassword->password = Hash::make($request->input('password'));
    $userToChangePassword->save();

    return response("", 201);
  }
}
