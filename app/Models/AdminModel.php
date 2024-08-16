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
        $result = DB::transaction(function () use ($email) {
            return DB::table('admins')->select('*')->where('email', $email)->where('status', '!=', 'Deleted')->first();
        });
        return $result;
    }

    public static function getAdminById($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('admins')->select('*')->where('id', $id)->where('status', 'Active')->first();
        });
        return $result;
    }

    public static function resetPassword($id, $otp, $password)
    {
        $result = DB::transaction(function () use ($id, $otp, $password) {
            return DB::table('admins')->where('id', $id)->where('otp', $otp)->update(['password' => $password]);
        });
        return $result;
    }

    public static function updateProfile($id, $data)
    {
        $result = DB::transaction(function () use ($id, $data) {
            $updated = DB::table('admins')->where('id', $id)->update($data);
            if ($updated) {
                return DB::table('admins')->where('id', $id)->first();
            }
        });
        return $result;
    }

    public static function changePassword($id, $password)
    {
        $result = DB::transaction(function () use ($id, $password) {
            return DB::table('admins')->where('id', $id)->update(['password' => $password]);
        });
        return $result;
    }

    public static function getStaticContent($content_type)
    {
        $result = DB::transaction(function () use ($content_type) {
            return DB::table('static_contents')->select('*')->where('content_type', $content_type)->where('status', 'Active')->first();
        });
        return $result;
    }

    public static function updateStaticContent($content_type, $data)
    {
        $result = DB::transaction(function () use ($content_type, $data) {
            $updated = DB::table('static_contents')->where('content_type', $content_type)->update($data);
            if ($updated) {
                return DB::table('static_contents')->where('content_type', $content_type)->first();
            }
        });
        return $result;
    }

    public static function getAllUsers($per_page)
    {
        $result = DB::transaction(function () use ($per_page) {
            return DB::table('users')->select('*')->where('status', '!=', 'Deleted')->paginate($per_page);
        });
        return $result;
    }

    public static function getUserById($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('users')->select('*')->where('id', $id)->where('status', '!=', 'Deleted')->first();
        });
        return $result;
    }

    public static function getAllCategories($per_page, $search = null)
    {
        $result = DB::transaction(function () use ($per_page, $search) {
            $query = DB::table('course_categories')->select('*')->where('status', '!=', 'Deleted');
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
            return DB::table('course_categories')->select('*')->where('id', $id)->where('status', '!=', 'Deleted')->first();
        });
        return $result;
    }

    public static function addCategory($data)
    {
        $result = DB::transaction(function () use ($data) {
            return DB::table('course_categories')->insert($data);
        });
        return $result;
    }

    public static function updateCategory($id, $data)
    {
        $result = DB::transaction(function () use ($id, $data) {
            return DB::table('course_categories')->where('id', $id)->update($data);
        });
        return $result;
    }

    public static function deleteCategory($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('course_categories')->where('id', $id)->update(['status' => 'Deleted']);
        });
        return $result;
    }

    public static function getAllCourses($per_page, $search = null)
    {
        $result = DB::transaction(function () use ($per_page, $search) {
            $query = DB::table('courses')->select('*')->where('status', '!=', 'Deleted');
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
            return DB::table('courses')->select('*')->where('id', $id)->where('status', '!=', 'Deleted')->first();
        });
        return $result;
    }

    public static function addCourse($data)
    {
        $result = DB::transaction(function () use ($data) {
            return DB::table('courses')->insert($data);
        });
        return $result;
    }

    public static function updateCourse($id, $data)
    {
        $result = DB::transaction(function () use ($id, $data) {
            return DB::table('courses')->where('id', $id)->update($data);
        });
        return $result;
    }

    public static function deleteCourse($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('courses')->where('id', $id)->update(['status' => 'Deleted']);
        });
        return $result;
    }
}
