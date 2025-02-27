<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateInvoiceJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\UserModel;
use App\Models\AdminModel;
use App\Models\CommonModel;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Junges\Kafka\Facades\Kafka;
use GuzzleHttp\Client;

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

            $remove_image = $request->query->get('remove_profile_image');
            if ($remove_image) {
                update('users', 'id', $user_id, ['profile_image' => null]);
                return response()->json(['result' => 1, 'msg' => 'Profile picture removed!', 'data' => null]);
            }

            $user = UserModel::getUserById($user_id);

            $profileImageResult = $request->hasFile('profile_image') ? singleAwsUpload($request, 'profile_image') : $user->profile_image;
            $profile_image = $profileImageResult->url ?? $user->profile_image;

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
            $result = UserModel::getAllCourses($per_page, $search, $user_id);
            if (!empty($result)) {
                foreach ($result as $value) {
                    if (!$guest) {
                        $isPurchased = UserModel::isCoursePurchased($value->id, $user_id);
                        $value->is_purchased = $isPurchased;
                        $isInWishlist = UserModel::isCourseInWishlist($value->id, $user_id);
                        $value->is_in_wishlist = $isInWishlist;
                        $isInCart = UserModel::isCourseInCart($value->id, $user_id);
                        $value->is_in_cart = $isInCart;
                    } else {
                        $value->is_purchased = false;
                        $value->is_in_wishlist = false;
                        $value->is_in_cart = false;
                    }
                    $category = AdminModel::getCategoryById($value->category_id);
                    $value->category_name = !empty($category->category_name) ? $category->category_name : null;
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
            $result = UserModel::getCourseByCategoryId($id, $per_page, $search, $user_id);
            if (!empty($result)) {
                foreach ($result as $value) {
                    if (!$guest) {
                        $isPurchased = UserModel::isCoursePurchased($value->id, $user_id);
                        $value->is_purchased = $isPurchased;
                        $isInWishlist = UserModel::isCourseInWishlist($value->id, $user_id);
                        $value->is_in_wishlist = $isInWishlist;
                        $isInCart = UserModel::isCourseInCart($value->id, $user_id);
                        $value->is_in_cart = $isInCart;
                    } else {
                        $value->is_purchased = false;
                        $value->is_in_wishlist = false;
                        $value->is_in_cart = false;
                    }
                    $category = AdminModel::getCategoryById($value->category_id);
                    $value->category_name = !empty($category->category_name) ? $category->category_name : null;
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
            $result = UserModel::getCourseDetailsById($id, $user_id);
            if (!empty($result)) {
                if (!$guest) {
                    $isPurchased = UserModel::isCoursePurchased($result->id, $user_id);
                    $result->is_purchased = $isPurchased;
                    $isInWishlist = UserModel::isCourseInWishlist($result->id, $user_id);
                    $result->is_in_wishlist = $isInWishlist;
                    $isInCart = UserModel::isCourseInCart($result->id, $user_id);
                    $result->is_in_cart = $isInCart;
                } else {
                    $result->is_purchased = false;
                    $result->is_in_wishlist = false;
                    $result->is_in_cart = false;
                }
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
                        $isVideoCompleted = UserModel::isVideoCompleted($video->id, $result->id, $user_id);
                        $video->is_video_completed = $isVideoCompleted;
                        $videoLastPlaybackTime = UserModel::videoLastPlaybackTime($video->id, $result->id, $user_id);
                        $video->last_playback_time = !empty($videoLastPlaybackTime->last_playback_time) ? $videoLastPlaybackTime->last_playback_time : 0;
                        $video->last_playback_percentage = $video->duration > 0 ? (($video->last_playback_time / $video->duration) * 100) : 0;
                    }
                }
                $result->documents = !empty($documents) ? $documents : null;
                $result->presentations = !empty($presentations) ? $presentations : null;
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
            $result = UserModel::toggleWishlist($user_id, $course_id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => $result->message, 'data' => ['wishlist_count' => $result->wishlist_count]]);
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
            $localCartItems = $request->query('local_cart_items') ?? false;
            if ($localCartItems) {
                $isInCart = UserModel::isCourseInCart($course_id, $user_id);
                if (!$isInCart) {
                    $result = UserModel::toggleCart($user_id, $course_id);
                } else {
                    return response()->json(['result' => -1, 'msg' => "Already Added"]);
                }
            } else {
                $result = UserModel::toggleCart($user_id, $course_id);
            }

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
                }
                return response()->json(['result' => 1, 'msg' => 'Cart data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllCountries()
    {
        try {
            $result = UserModel::getAllCountries();
            if ($result) {
                return response()->json(['result' => 1, 'msg' => 'Countries fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllStatesByCountry($country_id = null)
    {
        try {
            if (empty($country_id)) {
                return response()->json(['result' => 0, 'errors' => 'Country Id is required!']);
            }
            $result = UserModel::getAllStatesByCountry($country_id);
            if ($result) {
                return response()->json(['result' => 1, 'msg' => 'States fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllCitiesByState($state_id = null)
    {
        try {
            if (empty($state_id)) {
                return response()->json(['result' => 0, 'errors' => 'State Id is required!']);
            }
            $result = UserModel::getAllCitiesByState($state_id);
            if ($result) {
                if ($result->isEmpty()) {
                    $state = select('states', 'name', ['id' => $state_id])->first();
                    $result = [
                        (object) [
                            'id' => 0,
                            'name' => $state->name
                        ]
                    ];
                }
                return response()->json(["result" => 1, "msg" => "Cities fetched successfully", 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function createPaymentIntent(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;

            $validator = Validator::make($request->all(), [
                'total_amount' => 'required',
                'currency' => 'required'
            ], [
                'required' => 'The :attribute field is required',
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $total_amount = $request->post('total_amount');
            $currency = $request->post('currency');

            Stripe::setApiKey(env('STRIPE_SECRET'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $total_amount * 100,
                'currency' => $currency,
                'payment_method_types' => ['card']
            ]);

            return response()->json(['result' => 1, 'msg' => 'PaymentIntent created', 'payment_intent_id' => $paymentIntent->id, 'client_secret' => $paymentIntent->client_secret]);
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function initiateCheckout(Request $request)
    {
        try {
            $token = $request->header('token');
            $user = getUserByToken($token);

            $validator = Validator::make($request->all(), [
                'amount' => 'required',
                'currency' => 'required',
                'course_ids' => 'required',
                'redirect_url' => 'required',
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $amount = $request->post('amount');
            $currency = $request->post('currency');
            $course_ids = $request->post('course_ids');
            $redirect_url = $request->post('redirect_url');
            $course_prices = $request->post('course_prices');
            $user_info = [
                'name' => "$user->first_name $user->last_name",
                'email' => $user->email,
                'address' => !empty($request->post('address')) ? $request->post('address') : null,
                'country' => !empty($request->post('country')) ? $request->post('country') : null,
                'state' => !empty($request->post('state')) ? $request->post('state') : null,
                'city' => !empty($request->post('city')) ? $request->post('city') : null,
                'pincode' => !empty($request->post('pincode')) ? $request->post('pincode') : null,
            ];
            $cidsenc = urlencode(json_encode($course_ids));
            $cpricesenc = urlencode(json_encode($course_prices));
            $uinfoenc = urlencode(json_encode($user_info));
            $redirect_url_with_params = "{$redirect_url}&cur={$currency}&amt={$amount}&cids={$cidsenc}&cprices={$cpricesenc}&uinfo={$uinfoenc}";
            $courses_amount = 0;
            if (!empty($course_ids)) {
                foreach ($course_ids as $course_id) {
                    $course = UserModel::getCourseDetailsById($course_id);
                    $courses_amount += $course->price;
                }
            }

            if ($amount != $courses_amount) {
                return response()->json([
                    'result' => -1,
                    'msg' => 'The provided amount does not match the total course amount!'
                ]);
            }

            $payload = [
                'amount' => $courses_amount * 100,
                'currency' => $currency,
                'redirect_url' => $redirect_url_with_params,
                'description' => 'Payment for courses',
                'customer' => [
                    'name' => "$user->first_name $user->last_name",
                    'email' => $user->email,
                ],
            ];

            // Make the API call to Revolut
            $client = new Client();
            $response = $client->post('https://sandbox-merchant.revolut.com/api/orders', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Revolut-Api-Version' => '2024-09-01',
                    'Authorization' => 'Bearer ' . env('REVOLUT_API_KEY'),
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody(), true);

            if (isset($responseData['id']) && isset($responseData['checkout_url'])) {
                $orderData = [
                    'order_id' => $responseData['id'],
                    'user_id' => $user->id,
                    'course_ids' => json_encode($course_ids),
                    'currency' => $currency,
                    'amount' => $courses_amount,
                    'redirect_url' => $redirect_url_with_params,
                    'customer' => json_encode([
                        'name' => "$user->first_name $user->last_name",
                        'email' => $user->email,
                    ]),
                ];
                $paymentData = [
                    'order_id' => $responseData['id'],
                    'user_id' => $user->id,
                    'course_ids' => !empty($course_ids) ? json_encode($course_ids) : null,
                    'name' => "$user->first_name $user->last_name",
                    'email' => $user->email,
                    'address' => !empty($request->post('address')) ? $request->post('address') : null,
                    'country' => !empty($request->post('country')) ? $request->post('country') : null,
                    'state' => !empty($request->post('state')) ? $request->post('state') : null,
                    'city' => !empty($request->post('city')) ? $request->post('city') : null,
                    'pincode' => !empty($request->post('pincode')) ? $request->post('pincode') : null,
                    'payment_method' => "Revolut",
                    'currency' => $currency,
                    'amount' => !empty($courses_amount) ? $courses_amount : null,
                    'status' => "Inactive",
                    'payment_status' => "Pending"
                ];
                UserModel::createOrder($orderData);
                UserModel::createPayment($paymentData);
                return response()->json([
                    'result' => 1,
                    'msg' => 'Payment order created successfully',
                    'data' => $responseData
                ]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Failed to create payment order']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function handleRevolutWebhook(Request $request)
    {
        $signature = $request->header('Revolut-Signature');
        $timestamp = $request->header('Revolut-Request-Timestamp');

        $payload = $request->getContent();
        $secret = env('REVOLUT_WEBHOOK_SECRET');

        if (!($signature !== $secret)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);

        switch ($event['event']) {
            case 'ORDER_COMPLETED':
                \Log::info('Webhook event: ' . json_encode($event, JSON_PRETTY_PRINT));
                DB::table('user_orders')
                    ->where('order_id', $event['order_id'])
                    ->update(['payment_status' => "Success", 'updated_at' => now()]);

                DB::table('user_payments')
                    ->where('order_id', $event['order_id'])
                    ->update(['status' => "Active", 'payment_status' => "Success", 'updated_at' => now()]);

                $payment = DB::table('user_payments')->where('order_id', $event['order_id'])->first();
                GenerateInvoiceJob::dispatch($payment);
                break;

            case 'ORDER_AUTHORISED':
                // \Log::info('Webhook event: ' . json_encode($event, JSON_PRETTY_PRINT));
                DB::table('user_orders')
                    ->where('order_id', $event['order_id'])
                    ->update(['payment_status' => "Authorised", 'updated_at' => now()]);

                DB::table('user_payments')
                    ->where('order_id', $event['order_id'])
                    ->update(['status' => "Active", 'payment_status' => "Authorised", 'updated_at' => now()]);
                break;

            case 'ORDER_CANCELLED':
                // \Log::info('Webhook event: ' . json_encode($event, JSON_PRETTY_PRINT));
                DB::table('user_orders')
                    ->where('order_id', $event['order_id'])
                    ->update(['payment_status' => "Failed", 'updated_at' => now()]);

                DB::table('user_payments')
                    ->where('order_id', $event['order_id'])
                    ->update(['status' => "Active", 'payment_status' => "Failed", 'updated_at' => now()]);
                break;

            default:
                \Log::info('Unhandled webhook event: ' . json_encode($event, JSON_PRETTY_PRINT));
        }

        return response()->json(null, 204);
    }

    public function getPaymentStatus($orderId)
    {
        try {
            $payment = UserModel::getPaymentStatus($orderId);

            return response()->json(['result' => 1, 'status' => $payment->payment_status], 200);
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function checkout(Request $request)
    {
        try {
            $token = $request->header('token');
            $user = getUserByToken($token);

            $validator = Validator::make($request->all(), [
                'course_ids' => 'required',
                'course_prices' => 'required',
                'total_amount' => 'required',
                'order_id' => 'required',
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $course_ids = $request->post('course_ids');
            $course_prices = $request->post('course_prices');
            $currency = $request->post('currency');
            $total_amount = $request->post('total_amount');
            $order_id = $request->post('order_id');

            $courses_amount = 0;
            if (!empty($course_ids)) {
                foreach ($course_ids as $course_id) {
                    $course = UserModel::getCourseDetailsById($course_id);
                    $courses_amount += $course->price;
                }
            }

            if ($total_amount != $courses_amount) {
                return response()->json([
                    'result' => -1,
                    'msg' => 'The provided amount does not match the total course amount!'
                ]);
            }

            $data = [
                'user_id' => $user->id,
                'course_ids' => !empty($course_ids) ? json_encode($course_ids) : null,
                'name' => !empty($user->first_name) ? "$user->first_name $user->last_name" : null,
                'email' => !empty($user->email) ? $user->email : null,
                'address' => !empty($request->post('address')) ? $request->post('address') : null,
                'country' => !empty($request->post('country')) ? $request->post('country') : null,
                'state' => !empty($request->post('state')) ? $request->post('state') : null,
                'city' => !empty($request->post('city')) ? $request->post('city') : null,
                'pincode' => !empty($request->post('pincode')) ? $request->post('pincode') : null,
                'payment_method' => "Revolut",
                'currency' => $currency,
                'amount' => !empty($courses_amount) ? $courses_amount : null
            ];

            $result = UserModel::checkout($order_id, $user->id, $data, $course_ids, $course_prices);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Payment successful', 'data' => true]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function generateInvoice($payment)
    {
        try {
            $user = UserModel::getUserById($payment->user_id);
            if (!$payment->order_id) {
                return response()->json(['result' => -1, 'msg' => 'Invalid payment ID']);
            }

            $course_ids = json_decode($payment->course_ids, true);

            $courses = DB::table('courses')->whereIn('id', $course_ids)->get();

            $course_details = [];
            $total_price = $payment->amount;
            foreach ($courses as $course) {
                $course_details[] = [
                    'name' => $course->course_name,
                    'price' => number_format($course->price, 2)
                ];
            }

            $tax = $total_price * 0.10; // 10% tax
            $total_after_tax = $total_price + $tax;
            $completion_date = $payment->created_at;

            $pdf = PDF::loadView('pdf.invoice', compact('course_details', 'completion_date', 'payment', 'total_price', 'tax', 'total_after_tax'))
                ->setOption('margin-top', 0)
                ->setOption('margin-right', 0)
                ->setOption('margin-bottom', 0)
                ->setOption('margin-left', 0)
                ->setOption('dpi', 300)
                ->setOption('enable-local-file-access', true)
                ->setOption('no-images', false)
                ->setOption('image-quality', 100);

            $fileName = "invoice-$payment->order_id.pdf";
            $filePath = "invoices/$fileName";
            Storage::disk('public')->put($filePath, $pdf->output());

            if (Storage::disk('public')->exists($filePath)) {
                $tempFilePath = Storage::disk('public')->path($filePath);

                if (!file_exists($tempFilePath)) {
                    return response()->json(['result' => -1, 'msg' => 'Temporary PDF file not found.']);
                }

                $file = new UploadedFile(
                    $tempFilePath,
                    $fileName,
                    'application/pdf',
                    filesize($tempFilePath),
                    true
                );

                $newRequest = Request::create('/upload', 'POST', [], [], ['invoice' => $file]);
                $uploadResult = singleAwsUpload($newRequest, 'invoice', 'invoices');
                if ($uploadResult === false) {
                    return response()->json(['result' => -1, 'msg' => 'File upload to S3 failed.']);
                }
                if ($uploadResult) {
                    update('user_payments', 'order_id', $payment->order_id, ['invoice' => $uploadResult->url]);
                    $maildata = [
                        'name' => "$user->first_name $user->last_name",
                        'to' => $user->email,
                        'msg' => "Thank you for your purchase! We truly appreciate your trust in us. Attached, you will find your invoice for Order #$payment->order_id. We hope you enjoy your purchase and look forward to serving you again in the future.",
                        'subject' => "Invoice for Order #$payment->order_id",
                        'view_name' => 'invoice',
                        'payment' => $payment,
                    ];
                    sendMail($maildata, $tempFilePath);
                    if (file_exists($tempFilePath)) {
                        unlink($tempFilePath);
                    }
                    return true;
                } else {
                    return response()->json(['result' => -1, 'msg' => 'Failed to generate the pdf!']);
                }
            } else {
                return response()->json(['message' => 'Failed to save the PDF!'], 500);
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
                    $isCertificateGenerated = UserModel::isCertificateGenerated($value->id, $user_id);
                    $value->is_certificate_generated = $isCertificateGenerated;
                    $value->course_completion_progress = !empty($value->completion_percentage) ? $value->completion_percentage : 0;
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

    public function generateCertificate(Request $request)
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

            $course_id = $request->input('course_id');

            $certificate = select('user_certificates', 'id', ['course_id' => $course_id, 'user_id' => $user_id])->first();
            if (!empty($certificate)) {
                return response()->json(['result' => -1, 'msg' => 'Certificate already generated!']);
            }

            $user = UserModel::getUserById($user_id);
            $course = select('courses', '*', ['id' => $course_id])->first();

            $student_name = $user->first_name . ' ' . $user->last_name;
            $course_name = !empty($course->course_name) ? $course->course_name : '';
            $completion_date = now()->format('dS F, Y');

            $pdf = PDF::loadView('pdf.certificate', compact('student_name', 'course_name', 'completion_date'))
                ->setOption('margin-top', 0)
                ->setOption('margin-right', 0)
                ->setOption('margin-bottom', 0)
                ->setOption('margin-left', 0)
                ->setOption('dpi', 300)
                ->setOption('enable-local-file-access', true)
                ->setOption('no-images', false)
                ->setOption('image-quality', 100);

            $fileName = "certificate-$user_id-$course_id.pdf";
            $filePath = "certificates/$fileName";
            Storage::disk('public')->put($filePath, $pdf->output());

            if (Storage::disk('public')->exists($filePath)) {
                $tempFilePath = Storage::disk('public')->path($filePath);

                if (!file_exists($tempFilePath)) {
                    return response()->json(['result' => -1, 'msg' => 'Temporary PDF file not found.']);
                }

                $file = new UploadedFile(
                    $tempFilePath,
                    $fileName,
                    'application/pdf',
                    filesize($tempFilePath),
                    true
                );

                $newRequest = Request::create('/upload', 'POST', [], [], ['certificate' => $file]);
                $uploadResult = singleAwsUpload($newRequest, 'certificate', 'certificates');
                if ($uploadResult === false) {
                    return response()->json(['result' => -1, 'msg' => 'File upload to S3 failed.']);
                }
                if ($uploadResult) {
                    if (file_exists($tempFilePath)) {
                        unlink($tempFilePath);
                    }
                    $data = [
                        'user_id' => $user_id,
                        'course_id' => $course_id,
                        'certificate' => $uploadResult->url,
                        'completed_on' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    UserModel::generateCertificate($data);
                    return response()->json(['result' => 1, 'msg' => 'Certificate generated successfully', 'data' => ['file' => $uploadResult->url]]);
                } else {
                    return response()->json(['result' => -1, 'msg' => 'Failed to generate the pdf!']);
                }
            } else {
                return response()->json(['message' => 'Failed to save the PDF!'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function downloadCertificate($user_id, $course_id)
    {
        $user_certificate = select('user_certificates', 'certificate', ['user_id' => $user_id, 'course_id' => $course_id])->first();
        $certificateUrl = !empty($user_certificate) ? $user_certificate->certificate : null;
        if (empty($certificateUrl)) {
            return response()->json(['result' => -1, 'msg' => 'Certificate not found!']);
        }
        $fileContent = file_get_contents($certificateUrl);
        $fileName = 'certificate_' . $course_id . '.pdf';
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Content-Transfer-Encoding', 'binary')
            ->header('Accept-Ranges', 'bytes');
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->id;

            $data = [
                'last_logout' => now(),
                'login_status' => 0
            ];

            $result = UserModel::logout($user_id, $data);

            if ($result) {
                return response()->json(['result' => 1, 'msg' => 'Logged out successfully']);
            } else {
                return response()->json(['message' => 'Failed to Log out!'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function updatePlayback(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required',
                'video_id' => 'required',
                'course_id' => 'required',
                'playback_time' => 'required',
            ]);
            $userId = $validated['user_id'];
            $courseId = $validated['course_id'];
            $videoId = $validated['video_id'];
            $playback = DB::table('user_course_videos_status')
                ->where([
                    ['user_id', '=', $userId],
                    ['course_id', '=', $courseId],
                    ['video_id', '=', $videoId],
                ])
                ->first();
            if ($playback) {
                DB::table('user_course_videos_status')
                    ->where([
                        ['user_id', '=', $userId],
                        ['course_id', '=', $courseId],
                        ['video_id', '=', $videoId],
                    ])
                    ->update(['last_playback_time' => $validated['playback_time'], 'updated_at' => now()]);
            } else {
                DB::table('user_course_videos_status')->insert([
                    'user_id' => $userId,
                    'video_id' => $videoId,
                    'course_id' => $courseId,
                    'last_playback_time' => $validated['playback_time'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Playback time updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }
}
