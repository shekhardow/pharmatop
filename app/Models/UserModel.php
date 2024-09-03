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

    public static function getStaticContent($content_type)
    {
        $result = DB::transaction(function () use ($content_type) {
            return DB::table('static_contents')->select('*')->where('content_type', $content_type)->where('status', 'Active')->first();
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

    public static function isCourseInWishlist($id, $user_id)
    {
        $result = DB::transaction(function () use ($id, $user_id) {
            return DB::table('user_wishlists')->where('course_id', $id)->where('user_id', $user_id)->where('wishlist_status', 'Added')->exists();
        });
        return $result;
    }

    public static function isCourseInCart($id, $user_id)
    {
        $result = DB::transaction(function () use ($id, $user_id) {
            return DB::table('user_carts')->where('course_id', $id)->where('user_id', $user_id)->where('cart_status', 'Added')->exists();
        });
        return $result;
    }

    public static function getCourseByCategoryId($id, $per_page, $search = null)
    {
        $result = DB::transaction(function () use ($id, $per_page, $search) {
            $query = DB::table('courses')->select('*')->where('category_id', $id)->where('status', 'Active');
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

    public static function getCourseDetailsById($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('courses')->select('*')->where('id', $id)->where('status', 'Active')->first();
        });
        return $result;
    }

    public static function isVideoCompleted($id, $user_id)
    {
        $result = DB::transaction(function () use ($id, $user_id) {
            return DB::table('user_course_videos_status')->where('video_id', $id)->where('user_id', $user_id)->where('completion_status', 'Completed')->exists();
        });
        return $result;
    }

    public static function toggleWishlist($user_id, $course_id)
    {
        $result = DB::transaction(function () use ($user_id, $course_id) {
            $wishlist = DB::table('user_wishlists')->where('user_id', $user_id)->where('course_id', $course_id)->first();
            if ($wishlist) {
                $new_status = $wishlist->wishlist_status == 'Added' ? 'Removed' : 'Added';
                DB::table('user_wishlists')->where('id', $wishlist->id)->update(['wishlist_status' => $new_status, 'updated_at' => now()]);
                $message = $new_status == 'Added' ? 'Added to wishlist' : 'Removed from wishlist';
            } else {
                DB::table('user_wishlists')->insert([
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'wishlist_status' => 'Added'
                ]);
                $message = 'Added to wishlist';
            }
            return $message;
        });
        return $result;
    }

    public static function getAllWishlistItems($user_id, $per_page, $search = null)
    {
        $result = DB::transaction(function () use ($user_id, $per_page, $search) {
            $courseIds = DB::table('user_wishlists')->where('user_id', $user_id)->where('wishlist_status', 'Added')->where('status', 'Active')->pluck('course_id');
            $query = DB::table('courses')->select('*')->whereIn('id', $courseIds)->where('status', 'Active');
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

    public static function toggleCart($user_id, $course_id)
    {
        $result = DB::transaction(function () use ($user_id, $course_id) {
            $wishlist = DB::table('user_carts')->where('user_id', $user_id)->where('course_id', $course_id)->first();
            if ($wishlist) {
                $new_status = $wishlist->cart_status == 'Added' ? 'Removed' : 'Added';
                DB::table('user_carts')->where('id', $wishlist->id)->update(['cart_status' => $new_status, 'updated_at' => now()]);
                $message = $new_status == 'Added' ? 'Added to cart' : 'Removed from cart';
            } else {
                DB::table('user_carts')->insert([
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'cart_status' => 'Added'
                ]);
                $message = 'Added to cart';
            }
            $cart_count = DB::table('user_carts')->where('user_id', $user_id)->where('cart_status', 'Added')->count();
            DB::table('users')->where('id', $user_id)->update(['cart_count' => $cart_count, 'updated_at' => now()]);
            return (object) [
                'message' => $message,
                'cart_count' => $cart_count
            ];
        });
        return $result;
    }

    public static function getAllCartItems($user_id, $per_page, $search = null)
    {
        $result = DB::transaction(function () use ($user_id, $per_page, $search) {
            $courseIds = DB::table('user_carts')->where('user_id', $user_id)->where('cart_status', 'Added')->where('status', 'Active')->pluck('course_id');
            $query = DB::table('courses')->select('*')->whereIn('id', $courseIds)->where('status', 'Active');
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

    public static function checkout($user_id, $data, $course_ids, $course_prices)
    {
        $result = DB::transaction(function () use ($user_id, $data, $course_ids, $course_prices) {
            $id = DB::table('user_payments')->insertGetId($data);
            if (!empty($id)) {
                $purchasedCourses = [];
                foreach ($course_ids as $index => $course_id) {
                    $purchasedCourses[] = [
                        'user_id' => $user_id,
                        'course_id' => $course_id,
                        'purchased_amount' => $course_prices[$index],
                        'payment_id' => $id
                    ];
                }
                DB::table('user_purchased_courses')->insert($purchasedCourses);
                return $id;
            }
            return false;
        });
        return $result;
    }

    public static function getAllUserPurchasedCourses($user_id, $per_page, $search = null)
    {
        $result = DB::transaction(function () use ($user_id, $per_page, $search) {
            $courseIds = DB::table('user_purchased_courses')->where('user_id', $user_id)->where('status', 'Active')->pluck('course_id');
            $query = DB::table('courses')->select('*')->whereIn('id', $courseIds)->where('status', 'Active');
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

    public static function completeVideo($user_id, $video_id, $course_id, $data)
    {
        $result = DB::transaction(function () use ($user_id, $video_id, $course_id, $data) {
            $videoExists = DB::table('user_course_videos_status')
                ->where('user_id', $user_id)
                ->where('video_id', $video_id)
                ->where('course_id', $course_id)
                ->exists();
            if ($videoExists) {
                DB::table('user_course_videos_status')
                    ->where('user_id', $user_id)
                    ->where('video_id', $video_id)
                    ->where('course_id', $course_id)
                    ->update($data);
            } else {
                DB::table('user_course_videos_status')->insert($data);
            }

            $totalVideos = DB::table('course_module_videos')->where('course_id', $course_id)->count();
            if ($totalVideos > 0) {
                $completedVideos = DB::table('user_course_videos_status')
                    ->where('user_id', $user_id)
                    ->where('course_id', $course_id)
                    ->where('completion_status', 'Completed')
                    ->count();
                $completionPercentage = ($completedVideos / $totalVideos) * 100;
                $courseCompletionData = [
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'completion_status' => $completionPercentage == 100 ? 'Completed' : 'Incomplete',
                    'completion_date' => $completionPercentage == 100 ? now() : null,
                    'completion_percentage' => round($completionPercentage, 2)
                ];
            } else {
                $courseCompletionData = [
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'completion_status' => 'Incomplete',
                    'completion_date' => null,
                    'completion_percentage' => 0
                ];
            }
            $courseStatusExists = DB::table('user_courses_status')->where('user_id', $user_id)->where('course_id', $course_id)->exists();
            if ($courseStatusExists) {
                return DB::table('user_courses_status')->where('user_id', $user_id)->where('course_id', $course_id)->update($courseCompletionData);
            } else {
                return DB::table('user_courses_status')->insert($courseCompletionData);
            }
        });
        return $result;
    }
}
