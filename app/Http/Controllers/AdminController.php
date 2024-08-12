<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminModel;

class AdminController extends Controller
{
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

            $user = AdminModel::getAdminByEmail($request->input('email'));

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

            $user = AdminModel::getAdminByEmail($request->input('email'));

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
                update('admins', 'id', $user->id, ['otp' => $otp]);
                $user = AdminModel::getAdminById($user->id);
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

            $user = AdminModel::getAdminById($request->input('user_id'));

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
                update('admins', 'id', $user->id, ['otp' => $otp]);
                $user = AdminModel::getAdminById($user->id);
                return response()->json(['result' => 1, 'msg' => 'Verification OTP has been resent to your email.', 'data' => $user]);
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
            $otp = $request->input('otp');
            $user = AdminModel::getAdminById($user_id);

            if (!empty($user)) {
                $password = Hash::make($request->input('password'));
                $result = AdminModel::resetPassword($user_id, $otp, $password);
                if ($result) {
                    update('admins', 'id', $user->id, ['otp' => null]);
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
            $validator = Validator::make($request->all(), [
                'user_id' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $user_id = $request->input('user_id');
            $user = AdminModel::getAdminById($user_id);

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
                $result = AdminModel::updateProfile($user_id, $data);
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
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'current_password' => 'required',
                'new_password' => 'required|min:6',
                'confirm_password' => 'required|same:new_password'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'msg' => $validator->errors()->first()]);
            }

            $user_id = $request->input('user_id');

            $user = AdminModel::getAdminById($user_id);

            if (!empty($user)) {
                if ($request->post('current_password') !== $request->post('new_password')) {
                    if (!Hash::check($request->input('current_password'), $user->password)) {
                        return response()->json(['result' => -1, 'msg' => 'Incorrect current password']);
                    }

                    $password = Hash::make($request->input('new_password'));

                    $result = AdminModel::changePassword($user_id, $password);

                    if ($result) {
                        return response()->json(['result' => 1, 'msg' => 'Password changed successfully']);
                    } else {
                        return response()->json(['result' => -1, 'msg' => 'Failed to update password!']);
                    }
                } else {
                    return response()->json(['result' => -1, 'msg' => "Current password and new password shouldn't be same!"]);
                }
            } else {
                return response()->json(['result' => -1, 'msg' => 'Invalid ID!']);
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

            $result = AdminModel::getStaticContent($content_type);

            if ($result) {
                return response()->json(['result' => 1, 'msg' => "$content_type content fetched successfully", 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No content found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function updateStaticContent(Request $request)
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

            $content_type = $request->input('content_type');

            if ($request->has('title') || $request->has('description')) {
                $data = [
                    'updated_at' => now()->format('Y-m-d H:i:s')
                ];
                if ($request->has('title')) {
                    $data['title'] = $request->input('title');
                }
                if ($request->has('description')) {
                    $data['description'] = $request->input('description');
                }
                $result = AdminModel::updateStaticContent($content_type, $data);
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

    public function getDashboardData()
    {
        try {
            $result = [
                'total_users' => "200k",
                'total_courses' => "25",
                'earnings' => "$75k",
                'top_category' => "Pharmaceutical",
                'top_course' => "pharmaceutics",
                'active_users' => "85%",
                'new_users' => "15%",
                'revenue' => null
            ];

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Dashboard data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllUsers(Request $request)
    {
        try {
            $per_page = $request->query('per_page') ?? 10;
            $result = AdminModel::getAllUsers($per_page);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Users data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getUserById($id = null)
    {
        try {
            if (empty($id)) {
                return response()->json(['result' => 0, 'errors' => 'Id is required!']);
            }

            $result = AdminModel::getUserById($id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'User data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getAllCategories(Request $request)
    {
        try {
            $per_page = $request->query('per_page') ?? 10;
            $result = AdminModel::getAllCategories($per_page);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Categories data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function getCategoryById($id = null)
    {
        try {
            if (empty($id)) {
                return response()->json(['result' => 0, 'errors' => 'Id is required!']);
            }

            $result = AdminModel::getCategoryById($id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Category data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function addCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_name' => 'required',
                'description' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $category = select('course_categories', 'category_name', ['category_name' => $request->input('category_name'), ['status', '!=', 'Deleted']])->first();
            if (!empty($category)) {
                return response()->json(['result' => -1, 'msg' => 'The category name has already been taken!']);
            }

            if ($request->hasfile('category_image')) {
                $category_image = singleUpload($request, 'category_image', 'admin');
            } else {
                return response()->json(['result' => -1, 'msg' => 'Upload category image!']);
            }

            $data = [
                'category_name' => $request->input('category_name'),
                'description' => $request->input('description'),
                'category_image' => !empty($category_image) ? $category_image : null
            ];

            $result = AdminModel::addCategory($data);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Category added successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function updateCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'category_name' => 'required',
                'description' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $category_id = $request->input('category_id');
            $category = select('course_categories', '*', ['id' => $category_id])->first();
            if (empty($category)) {
                return response()->json(['result' => -1, 'msg' => 'Invalid Id!']);
            }

            if ($request->hasfile('category_image')) {
                $category_image = singleUpload($request, 'category_image', 'admin');
            } else {
                $category_image = $category->category_image;
            }

            $data = [
                'category_name' => $request->input('category_name'),
                'description' => $request->input('description'),
                'category_image' => !empty($category_image) ? $category_image : null
            ];

            $result = AdminModel::updateCategory($category_id, $data);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Category updated successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function deleteCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $category_id = $request->input('category_id');
            $category = select('course_categories', '*', ['id' => $category_id])->first();
            if (empty($category)) {
                return response()->json(['result' => -1, 'msg' => 'Invalid Id!']);
            }

            $result = AdminModel::deleteCategory($category_id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Category deleted successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Already deleted!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }
}
