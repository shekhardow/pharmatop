<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserModel extends Model
{
    use HasFactory;

    public static function getUserByEmail($email)
    {
        $result = DB::transaction(function () use ($email) {
            return DB::table('users')->select('*')->where('email', $email)->where('status', '!=', 'Deleted')->first();
        });
        return $result;
    }

    public static function getUserById($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('users')->select('*')->where('id', $id)->where('status', 'Active')->first();
        });
        return $result;
    }

    public static function register($data)
    {
        $result = DB::transaction(function () use ($data) {
            $id = DB::table('users')->insertGetId($data);
            if (!empty($id)) {
                $token = [
                    'user_id' => $id,
                    'user_token' => generateToken(),
                    // 'device_type' => $device_type,
                ];
                DB::table('user_authentications')->insert($token);
                return $id;
            }
        });
        return $result;
    }

    public static function updateProfile($id, $data)
    {
        $result = DB::transaction(function () use ($id, $data) {
            $updated = DB::table('users')->where('id', $id)->update($data);
            if ($updated) {
                return DB::table('users')->where('id', $id)->first();
            }
        });
        return $result;
    }

    public static function changePassword($id, $password)
    {
        $result = DB::transaction(function () use ($id, $password) {
            return DB::table('users')->where('id', $id)->update(['password' => $password]);
        });
        return $result;
    }

    public static function getAllCategories($per_page, $search = null)
    {
        $result = DB::transaction(function () use ($per_page, $search) {
            $query = DB::table('course_categories')->select('*')->where('status', 'Active');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('category_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
            return $query->paginate($per_page);
        });
        return $result;
    }

    public static function getCategoryById($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('course_categories')->select('*')->where('id', $id)->where('status', 'Active')->first();
        });
        return $result;
    }

    public static function getAllCourses($per_page, $search = null)
    {
        $result = DB::transaction(function () use ($per_page, $search) {
            $query = DB::table('courses')->select('*')->where('status', 'Active');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('course_name', 'like', "%{$search}%")
                        ->orWhere('language', 'like', "%{$search}%")
                        ->orWhere('price', 'like', "%{$search}%")
                        ->orWhere('total_sold', 'like', "%{$search}%")
                        ->orWhere('skills', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('duration', 'like', "%{$search}%");
                });
            }
            return $query->paginate($per_page);
        });
        return $result;
    }

    public static function getCourseById($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('courses')->select('*')->where('id', $id)->where('status', 'Active')->first();
        });
        return $result;
    }
}