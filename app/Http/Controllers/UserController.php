<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\UserModel;
use App\Models\AdminModel;
use App\Models\CommonModel;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // public function insertDummyUsers()
    // {
    //     $numberOfUsers = 100;
    //     for ($i = 1; $i <= $numberOfUsers; $i++) {
    //         DB::table('users')->insert([
    //             'first_name' => 'Tushar' . $i,
    //             'last_name' => 'Kumar',
    //             'email' => 'tushar' . $i . '@example.com',
    //             'password' => Hash::make('password')
    //         ]);
    //     }
    //     return "Inserted {$numberOfUsers} dummy users!";
    // }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|string|email',
                'password' => 'required|min:6|string',
                'confirm_password' => 'required_with:password|same:password'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $user = UserModel::getUserByEmail($request->input('email'));
            $admin = AdminModel::getAdminByEmail($request->input('email'));

            if (!empty($user)) {
                return response()->json(['result' => -1, 'msg' => 'An account with this email already exists!']);
            }
            if (!empty($admin)) {
                return response()->json(['result' => -1, 'msg' => 'An account with this email already exists!']);
            }

            $name = $request->input('name');
            $nameParts = explode(' ', $name);
            if (count($nameParts) > 1) {
                $last_name = array_pop($nameParts);
                $first_name = implode(' ', $nameParts);
            } else {
                $first_name = $nameParts[0];
                $last_name = '';
            }

            $data = [
                'first_name' => !empty($first_name) ? $first_name : null,
                'last_name' => !empty($last_name) ? $last_name : null,
                'email' => !empty($request->input('email')) ? $request->input('email') : null,
                'password' => !empty($request->input('password')) ? Hash::make($request->input('password')) : null
            ];

            $result = UserModel::register($data);
            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Registered successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Failed to register user!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getUserDetails(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;

            $result = UserModel::getUserById($user_id);

            if (!empty($result)) {
                $result->profile_image = !empty($result->profile_image) ? url("uploads/admin/$result->profile_image") : null;
                return response()->json(['result' => 1, 'msg' => 'User data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;

            $rules = [];
            $messages = [
                'required' => 'The :attribute field is required'
            ];
            if ($request->has('current_password') || $request->has('new_password') || $request->has('confirm_password')) {
                $rules['current_password'] = 'required';
                $rules['new_password'] = 'required|min:6';
                $rules['confirm_password'] = 'required|same:new_password';
            }
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $user = UserModel::getUserById($user_id);

            $profile_image = $request->hasFile('profile_image') ? singleUpload($request, 'profile_image', '/user') : $user->profile_image;

            if ($request->has('first_name') || $request->has('last_name') || $request->has('phone') || $request->has('address')) {
                $data = [
                    'profile_image' => !empty($profile_image) ? $profile_image : null,
                    'updated_at' => now()
                ];
                if ($request->has('first_name')) {
                    $data['first_name'] = $request->input('first_name');
                }
                if ($request->has('last_name')) {
                    $data['last_name'] = $request->input('last_name');
                }
                if ($request->has('phone')) {
                    $data['phone'] = $request->input('phone');
                }
                if ($request->has('address')) {
                    $data['address'] = $request->input('address');
                }

                if ($request->has('current_password') && $request->has('new_password') && $request->has('confirm_password')) {
                    if (!Hash::check($request->input('current_password'), $user->password)) {
                        return response()->json(['result' => -1, 'msg' => 'Incorrect current password']);
                    }

                    if ($request->input('current_password') === $request->input('new_password')) {
                        return response()->json(['result' => -1, 'msg' => "Current password and new password shouldn't be the same!"]);
                    }

                    $data['password'] = Hash::make($request->input('new_password'));
                }

                $result = UserModel::updateProfile($user_id, $data);
                if ($result) {
                    $updatedUserDetails = CommonModel::getUserByEmail($user->email);
                    $updatedUserDetails->profile_image = !empty($updatedUserDetails->profile_image) ? url("uploads/user/$updatedUserDetails->profile_image") : null;
                    return response()->json(['result' => 1, 'msg' => 'Profile updated successfully', 'data' => $updatedUserDetails]);
                }
            } else {
                return response()->json(['result' => -1, 'msg' => 'No changes were found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;

            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|min:6',
                'confirm_password' => 'required|same:new_password'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'msg' => $validator->errors()]);
            }

            $user = UserModel::getUserById($user_id);

            if (!empty($user)) {
                if ($request->post('current_password') !== $request->post('new_password')) {
                    if (!Hash::check($request->input('current_password'), $user->password)) {
                        return response()->json(['result' => -1, 'msg' => 'Incorrect current password']);
                    }

                    $password = Hash::make($request->input('new_password'));

                    $result = UserModel::changePassword($user_id, $password);

                    if ($result) {
                        return response()->json(['result' => 1, 'msg' => 'Password changed successfully']);
                    } else {
                        return response()->json(['result' => -1, 'msg' => 'Failed to update password!']);
                    }
                } else {
                    return response()->json(['result' => -1, 'msg' => "Current password and new password shouldn't be same!"]);
                }
            } else {
                return response()->json(['result' => -1, 'msg' => 'Invalid token!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getStaticContent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content_type' => ['required', 'in:Terms,Privacy,About']
            ], [
                'required' => 'The :attribute field is required',
                'in' => 'The :attribute field must be either Terms, Privacy or About'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $content_type = $request->query('content_type');

            $result = UserModel::getStaticContent($content_type);

            if ($result) {
                return response()->json(['result' => 1, 'msg' => "$content_type content fetched successfully", 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No content found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllCategories(Request $request)
    {
        try {
            $per_page = $request->query('per_page') ?? 10;
            $search = $request->query('search') ?? null;
            $result = UserModel::getAllCategories($per_page, $search);
            if (!empty($result)) {
                foreach ($result as $value) {
                    $value->category_image = !empty($value->category_image) ? url("uploads/admin/$value->category_image") : null;
                }
                return response()->json(['result' => 1, 'msg' => 'Categories data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllCourses(Request $request, $guest = null)
    {
        try {
            $user_id = null;
            if (!$guest) {
                $token = $request->header('token');
                $user_id = getUserByToken($token)->id;
            }

            $per_page = $request->query('per_page') ?? 10;
            $search = $request->query('search') ?? null;
            $result = UserModel::getAllCourses($per_page, $search);
            if (!empty($result)) {
                foreach ($result as $value) {
                    if (!$guest) {
                        $isInWishlist = UserModel::isCourseInWishlist($value->id, $user_id);
                        $value->is_in_wishlist = $isInWishlist;
                        $isInCart = UserModel::isCourseInCart($value->id, $user_id);
                        $value->is_in_cart = $isInCart;
                    } else {
                        $value->is_in_wishlist = false;
                        $value->is_in_cart = false;
                    }
                    $category = AdminModel::getCategoryById($value->category_id);
                    $value->category_name = !empty($category->category_name) ? $category->category_name : null;
                    $value->course_image = !empty($value->course_image) ? url("uploads/admin/$value->course_image") : null;
                }
                return response()->json(['result' => 1, 'msg' => 'Courses data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getCourseByCategoryId(Request $request, $id = null, $guest = null)
    {
        try {
            $user_id = null;
            if (!$guest) {
                $token = $request->header('token');
                $user_id = getUserByToken($token)->id;
            }

            if (empty($id)) {
                return response()->json(['result' => 0, 'errors' => 'Id is required!']);
            }
            $per_page = $request->query('per_page') ?? 10;
            $search = $request->query('search') ?? null;
            $result = UserModel::getCourseByCategoryId($id, $per_page, $search);
            if (!empty($result)) {
                foreach ($result as $value) {
                    if (!$guest) {
                        $isInWishlist = UserModel::isCourseInWishlist($value->id, $user_id);
                        $value->is_in_wishlist = $isInWishlist;
                        $isInCart = UserModel::isCourseInCart($value->id, $user_id);
                        $value->is_in_cart = $isInCart;
                    } else {
                        $value->is_in_wishlist = false;
                        $value->is_in_cart = false;
                    }
                    $category = AdminModel::getCategoryById($value->category_id);
                    $value->category_name = !empty($category->category_name) ? $category->category_name : null;
                    $value->course_image = !empty($value->course_image) ? url("uploads/admin/$value->course_image") : null;
                }
                return response()->json(['result' => 1, 'msg' => 'Courses data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getCourseDetailsById(Request $request, $id = null, $guest = null)
    {
        try {
            $user_id = null;
            if (!$guest) {
                $token = $request->header('token');
                $user_id = getUserByToken($token)->id;
            }

            if (empty($id)) {
                return response()->json(['result' => 0, 'errors' => 'Id is required!']);
            }
            $result = UserModel::getCourseDetailsById($id);
            if (!empty($result)) {
                if (!$guest) {
                    $isInWishlist = UserModel::isCourseInWishlist($result->id, $user_id);
                    $result->is_in_wishlist = $isInWishlist;
                    $isInCart = UserModel::isCourseInCart($result->id, $user_id);
                    $result->is_in_cart = $isInCart;
                } else {
                    $result->is_in_wishlist = false;
                    $result->is_in_cart = false;
                }
                $result->course_image = !empty($result->course_image) ? url("uploads/admin/$result->course_image") : null;
                $category = UserModel::getCategoryById($result->category_id);
                $result->category_name = !empty($category->category_name) ? $category->category_name : null;
                $result->skills = !empty($result->skills) ? json_decode($result->skills) : null;
                $videos = select('course_module_videos', '*', ['course_id' => $id, 'status' => 'Active']);
                $documents = select('course_documents', '*', ['course_id' => $id, 'document_type' => 'pdf', 'status' => 'Active']);
                $presentations = select('course_documents', '*', ['course_id' => $id, 'document_type' => 'ppt', 'status' => 'Active']);
                $result->features = [
                    'language' => !empty($result->language) ? $result->language : null,
                    'videos' => !empty($videos) ? count($videos) : 0,
                    'presentations' => !empty($presentations) ? count($presentations) : 0,
                    'documents' => !empty($documents) ? count($documents) : 0,
                    'certificate_on_completion' => true
                ];
                $result->videos = !empty($videos) ? $videos : null;
                if (!empty($result->videos)) {
                    foreach ($result->videos as $video) {
                        $isVideoCompleted = UserModel::isVideoCompleted($video->id, $user_id);
                        $video->is_video_completed = $isVideoCompleted;
                        $video->thumbnail = !empty($video->thumbnail) ? url("uploads/admin/$video->thumbnail") : null;
                        $video->video = !empty($video->video) ? url("uploads/admin/$video->video") : null;
                    }
                }
                $result->documents = !empty($documents) ? $documents : null;
                if (!empty($result->documents)) {
                    foreach ($result->documents as $document) {
                        $document->document = !empty($document->document) ? url("uploads/admin/$document->document") : null;
                    }
                }
                $result->presentations = !empty($presentations) ? $presentations : null;
                if (!empty($result->presentations)) {
                    foreach ($result->presentations as $presentation) {
                        $presentation->document = !empty($presentation->document) ? url("uploads/admin/$presentation->document") : null;
                    }
                }
                return response()->json(['result' => 1, 'msg' => 'Course data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function toggleWishlist(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;

            $validator = Validator::make($request->all(), [
                'course_id' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $course_id = $request->post('course_id');
            $message = UserModel::toggleWishlist($user_id, $course_id);

            if (!empty($message)) {
                return response()->json(['result' => 1, 'msg' => $message, 'data' => true]);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllWishlistItems(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;
            $per_page = $request->query('per_page') ?? 10;
            $search = $request->query('search') ?? null;
            $result = UserModel::getAllWishlistItems($user_id, $per_page, $search);
            if (!empty($result)) {
                foreach ($result as $value) {
                    $isInWishlist = UserModel::isCourseInWishlist($value->id, $user_id);
                    $value->is_in_wishlist = $isInWishlist;
                    $isInCart = UserModel::isCourseInCart($value->id, $user_id);
                    $value->is_in_cart = $isInCart;
                    $value->course_image = !empty($value->course_image) ? url("uploads/admin/$value->course_image") : null;
                }
                return response()->json(['result' => 1, 'msg' => 'Wishlist data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function toggleCart(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;

            $validator = Validator::make($request->all(), [
                'course_id' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $course_id = $request->post('course_id');
            $result = UserModel::toggleCart($user_id, $course_id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => $result->message, 'data' => ['cart_count' => $result->cart_count]]);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllCartItems(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;
            $per_page = $request->query('per_page') ?? 10;
            $search = $request->query('search') ?? null;
            $result = UserModel::getAllCartItems($user_id, $per_page, $search);
            if (!empty($result)) {
                foreach ($result as $value) {
                    $isInWishlist = UserModel::isCourseInWishlist($value->id, $user_id);
                    $value->is_in_wishlist = $isInWishlist;
                    $isInCart = UserModel::isCourseInCart($value->id, $user_id);
                    $value->is_in_cart = $isInCart;
                    $value->course_image = !empty($value->course_image) ? url("uploads/admin/$value->course_image") : null;
                }
                return response()->json(['result' => 1, 'msg' => 'Cart data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function checkout(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;

            $validator = Validator::make($request->all(), [
                'course_ids' => 'required',
                'course_prices' => 'required',
                'total_amount' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $course_ids = $request->post('course_ids');
            $course_prices = $request->post('course_prices');
            $total_amount = $request->post('total_amount');

            $data = [
                'user_id' => $user_id,
                'course_ids' => !empty($course_ids) ? json_encode($course_ids) : null,
                'name' => !empty($request->post('name')) ? $request->post('name') : null,
                'email' => !empty($request->post('email')) ? $request->post('email') : null,
                'payment_method' => !empty($request->post('payment_method')) ? $request->post('payment_method') : null,
                'currency' => !empty($request->post('currency')) ? $request->post('currency') : null,
                'amount' => !empty($total_amount) ? $total_amount : null
            ];

            $result = UserModel::checkout($user_id, $data, $course_ids, $course_prices);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Payment successful', 'data' => true]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllUserPurchasedCourses(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;
            $per_page = $request->query('per_page') ?? 10;
            $search = $request->query('search') ?? null;
            $result = UserModel::getAllUserPurchasedCourses($user_id, $per_page, $search);
            if (!empty($result)) {
                foreach ($result as $value) {
                    $videos = select('course_module_videos', '*', ['course_id' => $value->id, 'status' => 'Active']);
                    $documents = select('course_documents', '*', ['course_id' => $value->id, 'document_type' => 'pdf', 'status' => 'Active']);
                    $presentations = select('course_documents', '*', ['course_id' => $value->id, 'document_type' => 'ppt', 'status' => 'Active']);
                    $value->features = [
                        'language' => !empty($value->language) ? $value->language : null,
                        'videos' => !empty($videos) ? count($videos) : 0,
                        'presentations' => !empty($presentations) ? count($presentations) : 0,
                        'documents' => !empty($documents) ? count($documents) : 0,
                        'certificate_on_completion' => true
                    ];
                    $course_completion_progress = select('user_courses_status', 'completion_percentage', ['course_id' => $value->id, 'user_id' => $user_id, 'status' => 'Active'])->first();
                    $value->course_completion_progress = !empty($course_completion_progress->completion_percentage) ? $course_completion_progress->completion_percentage : 0;
                    $value->course_image = !empty($value->course_image) ? url("uploads/admin/$value->course_image") : null;
                }
                return response()->json(['result' => 1, 'msg' => 'Courses data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function completeVideo(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;

            $validator = Validator::make($request->all(), [
                'video_id' => 'required',
                'course_id' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $video_id = $request->input('video_id');
            $course_id = $request->input('course_id');
            $video = select('course_module_videos', '*', ['id' => $video_id])->first();
            if (empty($video)) {
                return response()->json(['result' => -1, 'msg' => 'Invalid Id!']);
            }

            $data = [
                'user_id' => $user_id,
                'course_id' => $course_id,
                'video_id' => $video_id,
                'completion_status' => 'Completed',
                'completion_date' => now()
            ];

            $result = UserModel::completeVideo($user_id, $video_id, $course_id, $data);

            if ($result) {
                return response()->json(['result' => 1, 'msg' => 'Video completed', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Already completed!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }
}
