<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommonModel extends Model
{
    use HasFactory;

    public static function getUserByEmail($email)
    {
        $result = DB::transaction(function () use ($email) {
            $admin = DB::table('admins')->select('*')->where('email', $email)->where('status', 'Active')->first();
            if ($admin) {
                $admin->source = 'admins';
                return $admin;
            }
            $user = DB::table('users')
                ->select('users.*', 'user_authentications.user_token', 'user_authentications.firebase_token')
                ->join('user_authentications', 'users.id', '=', 'user_authentications.user_id')
                ->where('users.email', $email)->where('users.status', 'Active')->first();
            if ($user) {
                $user->source = 'users';
            }
            return $user;
        });
        return $result;
    }

    public static function resetPassword($email, $password)
    {
        return DB::transaction(function () use ($email, $password) {
            $adminUpdated = DB::table('admins')
                ->where('email', $email)
                ->where('reset_password_verified', 'Yes')
                ->update(['password' => $password]);
            if ($adminUpdated) {
                return ['source' => 'admins', 'status' => true];
            }
            $userUpdated = DB::table('users')
                ->where('email', $email)
                ->where('reset_password_verified', 'Yes')
                ->update(['password' => $password]);
            if ($userUpdated) {
                return ['source' => 'users', 'status' => true];
            }
            return false;
        });
    }
}
