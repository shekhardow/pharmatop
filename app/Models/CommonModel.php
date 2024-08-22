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
            $admin = DB::table('admins')->select('*')->where('email', $email)->where('status', '!=', 'Deleted')->first();
            if ($admin) {
                $admin->source = 'admins';
                return $admin;
            }
            $user = DB::table('users')->select('*')->where('email', $email)->where('status', '!=', 'Deleted')->first();
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
            $tables = ['admins', 'users'];
            foreach ($tables as $table) {
                $user = DB::table($table)
                    ->select('id')
                    ->where('email', $email)
                    ->where('reset_password_verified', 'Yes')
                    ->first();
                if ($user) {
                    DB::table($table)
                        ->where('id', $user->id)
                        ->update(['password' => bcrypt($password)]);
                    return true;
                }
            }
            return false;
        });
    }
}
