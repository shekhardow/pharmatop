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
            $query = DB::table('courses')
                ->select('courses.*')
                ->where('courses.status', 'Active');
            // ->whereExists(function ($subquery) {
            //     $subquery->select(DB::raw(1))
            //         ->from('course_module_videos')
            //         ->whereColumn('course_module_videos.course_id', 'courses.id')
            //         ->where('course_module_videos.status', 'Active');
            // });
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

    public static function isCoursePurchased($id, $user_id)
    {
        $result = DB::transaction(function () use ($id, $user_id) {
            return DB::table('user_purchased_courses')->where('course_id', $id)->where('user_id', $user_id)->exists();
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
            $query = DB::table('courses')
                ->select('courses.*')
                ->where('courses.category_id', $id)
                ->where('courses.status', 'Active');
            // ->whereExists(function ($subquery) {
            //     $subquery->select(DB::raw(1))
            //         ->from('course_module_videos')
            //         ->whereColumn('course_module_videos.course_id', 'courses.id')
            //         ->where('course_module_videos.status', 'Active');
            // });
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

    public static function isVideoCompleted($id, $course_id, $user_id)
    {
        $result = DB::transaction(function () use ($id, $course_id, $user_id) {
            return DB::table('user_course_videos_status')->where('video_id', $id)->where('course_id', $course_id)->where('user_id', $user_id)->where('completion_status', 'Completed')->exists();
        });
        return $result;
    }

    public static function videoLastPlaybackTime($id, $course_id, $user_id)
    {
        $result = DB::transaction(function () use ($id, $course_id, $user_id) {
            return DB::table('user_course_videos_status')->where('video_id', $id)->where('course_id', $course_id)->where('user_id', $user_id)->first();
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
            $wishlist_count = DB::table('user_wishlists')
                ->leftJoin('courses', 'user_wishlists.course_id', '=', 'courses.id')
                ->where('user_wishlists.user_id', $user_id)
                ->where('user_wishlists.wishlist_status', 'Added')
                ->where('courses.status', 'Active')
                ->count();
            DB::table('users')->where('id', $user_id)->update(['wishlist_count' => $wishlist_count, 'updated_at' => now()]);
            return (object) [
                'message' => $message,
                'wishlist_count' => $wishlist_count
            ];
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
            $cart = DB::table('user_carts')->where('user_id', $user_id)->where('course_id', $course_id)->first();
            if ($cart) {
                $new_status = $cart->cart_status == 'Added' ? 'Removed' : 'Added';
                DB::table('user_carts')->where('id', $cart->id)->update(['cart_status' => $new_status, 'updated_at' => now()]);
                $message = $new_status == 'Added' ? 'Added to cart' : 'Removed from cart';
            } else {
                DB::table('user_carts')->insert([
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'cart_status' => 'Added'
                ]);
                $message = 'Added to cart';
            }
            $cart_count = DB::table('user_carts')
                ->leftJoin('courses', 'user_carts.course_id', '=', 'courses.id')
                ->where('user_carts.user_id', $user_id)
                ->where('user_carts.cart_status', 'Added')
                ->where('courses.status', 'Active')
                ->count();
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

    public static function getAllCountries()
    {
        $result = DB::transaction(function () {
            return DB::table('countries')->select('*')->where('status', 'Active')->get();
        });
        return $result;
    }

    public static function getAllStatesByCountry($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('states')->select('*')->where('country_id', $id)->where('status', 'Active')->get();
        });
        return $result;
    }

    public static function getAllCitiesByState($id)
    {
        $result = DB::transaction(function () use ($id) {
            return DB::table('cities')->select('*')->where('state_id', $id)->where('status', 'Active')->get();
        });
        return $result;
    }

    public static function createOrder($insertData)
    {
        $result = DB::transaction(function () use ($insertData) {
            $id = DB::table('user_orders')->insertGetId($insertData);
            return $id;
        });
        return $result;
    }

    public static function createPayment($insertData)
    {
        $result = DB::transaction(function () use ($insertData) {
            $id = DB::table('user_payments')->insertGetId($insertData);
            return $id;
        });
        return $result;
    }

    public static function checkout($order_id, $user_id, $data, $course_ids, $course_prices)
    {
        $result = DB::transaction(function () use ($order_id, $user_id, $data, $course_ids, $course_prices) {
            if (!empty($order_id)) {
                DB::table('user_payments')->where('order_id', $order_id)->update($data);
                $id = DB::table('user_payments')->where('order_id', $order_id)->value('id');
            } else {
                $id = DB::table('user_payments')->insertGetId($data);
            }

            $purchasedCourses = [];
            foreach ($course_ids as $index => $course_id) {
                $existingPurchase = DB::table('user_purchased_courses')
                    ->where('user_id', $user_id)
                    ->where('course_id', $course_id)
                    ->exists();

                if (!$existingPurchase) {
                    $purchasedCourses[] = [
                        'user_id' => $user_id,
                        'course_id' => $course_id,
                        'purchased_amount' => $course_prices[$index],
                        'payment_id' => $id
                    ];
                }
            }

            if (!empty($purchasedCourses)) {
                DB::table('user_purchased_courses')->insert($purchasedCourses);
            }

            return $id;
        });
        return $result;
    }

    public static function getPaymentStatus($orderId)
    {
        $result = DB::transaction(function () use ($orderId) {
            return DB::table('user_payments')->select('*')->where('order_id', $orderId)->first();
        });
        return $result;
    }

    public static function isCertificateGenerated($id, $user_id)
    {
        $result = DB::transaction(function () use ($id, $user_id) {
            return DB::table('user_certificates')->where('course_id', $id)->where('user_id', $user_id)->where('status', 'Active')->exists();
        });
        return $result;
    }

    public static function getAllUserPurchasedCourses($user_id, $per_page, $search = null)
    {
        $result = DB::transaction(function () use ($user_id, $per_page, $search) {
            $courseIds = DB::table('user_purchased_courses')
                ->where('user_id', $user_id)
                ->where('status', 'Active')
                ->pluck('course_id');
            $query = DB::table('courses')
                ->select('courses.*', 'user_courses_status.completion_status', 'user_courses_status.completion_date', 'user_courses_status.completion_percentage', 'user_certificates.certificate')
                ->whereIn('courses.id', $courseIds)
                ->where('courses.status', 'Active')
                ->leftJoin('user_courses_status', function ($join) use ($user_id) {
                    $join->on('courses.id', '=', 'user_courses_status.course_id')
                        ->where('user_courses_status.user_id', '=', $user_id);
                })
                ->leftJoin('user_certificates', function ($join) use ($user_id) {
                    $join->on('courses.id', '=', 'user_certificates.course_id')
                        ->where('user_certificates.user_id', '=', $user_id);
                });
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('courses.course_name', 'like', "%{$search}%")
                        ->orWhere('courses.language', 'like', "%{$search}%")
                        ->orWhere('courses.price', 'like', "%{$search}%")
                        ->orWhere('courses.total_sold', 'like', "%{$search}%")
                        ->orWhere('courses.skills', 'like', "%{$search}%")
                        ->orWhere('courses.description', 'like', "%{$search}%")
                        ->orWhere('courses.duration', 'like', "%{$search}%");
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

            $totalVideos = DB::table('course_module_videos')->where('course_id', $course_id)->where('status', 'Active')->count();
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

    public static function generateCertificate($data)
    {
        $result = DB::transaction(function () use ($data) {
            return DB::table('user_certificates')->insert($data);
        });
        return $result;
    }

    public static function logout($id, $data)
    {
        $result = DB::transaction(function () use ($id, $data) {
            return DB::table('users')->where('id', $id)->update($data);
        });
        return $result;
    }
}
