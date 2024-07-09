<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminModel extends Model
{
    use HasFactory;

    public static function getAdminByEmail($email)
    {
        try {
            $result = DB::transaction(function () use ($email) {
                return DB::table('admins')->select('*')->where('email', $email)->where('status', '!=', 'Deleted')->first();
            });
            return $result;
        } catch (\Exception $e) {
            // return response()->json(['result' => -1, 'msg' => 'DB error occurred: ' . $e->getMessage()]);
            return false;
        }
    }

    public static function getAdminById($id)
    {
        try {
            $result = DB::transaction(function () use ($id) {
                return DB::table('admins')->select('*')->where('id', $id)->where('status', 'Active')->first();
            });
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function resetPassword($id, $otp, $password)
    {
        try {
            $result = DB::transaction(function () use ($id, $otp, $password) {
                return DB::table('admins')->where('id', $id)->where('otp', $otp)->update(['password' => $password]);
            });
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function updateProfile($id, $data)
    {
        try {
            $result = DB::transaction(function () use ($id, $data) {
                return DB::table('admins')->where('id', $id)->update($data);
            });
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function changePassword($id, $password)
    {
        try {
            $result = DB::transaction(function () use ($id, $password) {
                return DB::table('admins')->where('id', $id)->update(['password' => $password]);
            });
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
}
