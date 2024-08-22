<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\UserModel;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|min:6|string',
                'confirm_password' => 'required_with:password|same:password'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $user = UserModel::getUserByEmail($request->input('email'));

            if (!empty($user)) {
                return response()->json(['result' => -1, 'msg' => 'User already exists with this email!']);
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

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $user = UserModel::getUserByEmail($request->input('email'));

            if (empty($user)) {
                return response()->json(['result' => -1, 'msg' => 'Invalid email!']);
            }

            if (!Hash::check($request->input('password'), $user->password)) {
                return response()->json(['result' => -1, 'msg' => 'Invalid password!']);
            }

            return response()->json(['result' => 1, 'msg' => 'Logged in successfully', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $user = UserModel::getUserByEmail($request->input('email'));

            if (!empty($user)) {
                if ($user->status == 'Inactive' || $user->status == 'Blocked') {
                    return response()->json(['result' => -2, 'msg' => "This account is $user->status!"]);
                }
                $otp = generateOtp();
                // $maildata = [
                //     'name' => $user->first_name,
                //     'to' => $user->email,
                //     'msg' => "Your verification OTP for resetting password is <strong>$otp</strong>",
                //     'subject' => 'OTP Verifiation Email For Forgot Password',
                //     'view_name' => 'otp'
                // ];
                // sendMail($maildata);
                update('users', 'id', $user->id, ['otp' => $otp]);
                $user = UserModel::getUserById($user->id);
                return response()->json(['result' => 1, 'msg' => 'Verification OTP has been sent to your email.', 'data' => $user]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Account does not exist with this email!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function resendOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $user = UserModel::getUserById($request->input('user_id'));

            if (!empty($user)) {
                if ($user->status == 'Inactive' || $user->status == 'Blocked') {
                    return response()->json(['result' => -2, 'msg' => "This account is $user->status!"]);
                }
                $otp = generateOtp();
                // $maildata = [
                //     'name' => $user->first_name,
                //     'to' => $user->email,
                //     'msg' => "Your verification OTP for resetting password is <strong>$otp</strong>",
                //     'subject' => 'OTP Verifiation Email For Forgot Password',
                //     'view_name' => 'otp'
                // ];
                // sendMail($maildata);
                update('users', 'id', $user->id, ['otp' => $otp]);
                $user = UserModel::getUserById($user->id);
                return response()->json(['result' => 1, 'msg' => 'Verification OTP has been resent to your email.', 'data' => $user]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No active account exist with this id!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function verifyResetPasswordOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'otp' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $otp = $request->input('otp');
            $user = UserModel::getUserById($request->input('user_id'));

            if (!empty($user)) {
                if ($user->status == 'Inactive' || $user->status == 'Blocked') {
                    return response()->json(['result' => -2, 'msg' => "This account is $user->status!"]);
                }
                if ($otp === $user->otp) {
                    update('users', 'id', $user->id, ['reset_password_verified' => 'Yes']);
                }
                $user = UserModel::getUserById($user->id);
                return response()->json(['result' => 1, 'msg' => 'OTP verification successful.', 'data' => $user]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No active account exist with this id!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'password' => 'required|min:6',
                'confirm_password' => 'required|same:password'
            ], [
                'required' => 'The :attribute field is required',
                'same' => 'The :attribute field must match the password field',
                'min' => 'The :attribute must be at least :min characters'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $user_id = $request->input('user_id');
            $user = UserModel::getUserById($user_id);

            if (!empty($user)) {
                $password = Hash::make($request->input('password'));
                $result = UserModel::resetPassword($user_id, $password);
                if ($result) {
                    update('users', 'id', $user->id, ['otp' => null, 'reset_password_verified' => 'No']);
                    return response()->json(['result' => 1, 'msg' => 'Password reset successfully']);
                } else {
                    return response()->json(['result' => 0, 'msg' => 'Password already updated!']);
                }
            } else {
                return response()->json(['result' => -1, 'msg' => 'User not found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $token = $request->header('token');
            $user_id = getUserByToken($token)->user_id;

            $user = UserModel::getUserById($user_id);

            $logo = $request->hasFile('logo') ? singleUpload($request, 'logo', '/uploads/admin_profile') : $user->logo;

            $favicon = $request->hasFile('favicon') ? singleUpload($request, 'favicon', '/uploads/admin_profile') : $user->favicon;

            $profile_image = $request->hasFile('profile_image') ? singleUpload($request, 'profile_image', '/uploads/admin_profile') : $user->profile_image;

            if ($request->has('first_name') || $request->has('last_name') || $request->has('phone') || $request->has('address')) {
                $data = [
                    'logo' => !empty($logo) ? $logo : null,
                    'favicon' => !empty($favicon) ? $favicon : null,
                    'profile_image' => !empty($profile_image) ? $profile_image : null,
                    'updated_at' => now()->format('Y-m-d H:i:s')
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
                $result = UserModel::updateProfile($user_id, $data);
                if ($result) {
                    return response()->json(['result' => 1, 'msg' => 'Profile updated successfully', 'data' => $result]);
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
            $user_id = getUserByToken($token)->user_id;

            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|min:6',
                'confirm_password' => 'required|same:new_password'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'msg' => $validator->errors()->first()]);
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

    public function getAllCourses(Request $request)
    {
        try {
            $per_page = $request->query('per_page') ?? 10;
            $search = $request->query('search') ?? null;
            $result = UserModel::getAllCourses($per_page, $search);
            if (!empty($result)) {
                foreach ($result as $value) {
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

    public function getCourseById($id = null)
    {
        try {
            if (empty($id)) {
                return response()->json(['result' => 0, 'errors' => 'Id is required!']);
            }
            $result = UserModel::getCourseById($id);
            if (!empty($result)) {
                $result->course_image = !empty($result->course_image) ? url("uploads/admin/$result->course_image") : null;
                $category = UserModel::getCategoryById($result->category_id);
                $result->category_name = !empty($category->category_name) ? $category->category_name : null;
                $result->skills = !empty($result->skills) ? json_decode($result->skills) : null;
                $videos = select('course_module_videos', '*', ['course_id' => $id, 'status' => 'Active']);
                $documents = select('course_documents', '*', ['course_id' => $id, 'document_type' => 'pdf', 'status' => 'Active']);
                $presentations = select('course_documents', '*', ['course_id' => $id, 'document_type' => 'ppt', 'status' => 'Active']);
                $result->features = [
                    'videos' => !empty($videos) ? count($videos) : 0,
                    'documents' => !empty($documents) ? count($documents) : 0,
                    'presentations' => !empty($presentations) ? count($presentations) : 0,
                    'language' => !empty($result->language) ? json_decode($result->language) : null
                ];
                $result->videos = !empty($videos) ? $videos : null;
                if (!empty($result->videos)) {
                    foreach ($result->videos as $video) {
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
}
