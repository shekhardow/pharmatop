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

            if (is_object($user)) {
                unset($user->password);
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
                if (is_object($user)) {
                    unset($user->password);
                }
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
                if (is_object($user)) {
                    unset($user->password);
                }
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

            $data = [
                'first_name' => !empty($request->has('first_name')) ? $request->input('first_name') : null,
                'last_name' => !empty($request->has('last_name')) ? $request->input('last_name') : null,
                'phone' => !empty($request->has('phone')) ? $request->input('phone') : null,
                'address' => !empty($request->has('address')) ? $request->input('address') : null,
                'logo' => !empty($logo) ? $logo : null,
                'favicon' => !empty($favicon) ? $favicon : null,
                'profile_image' => !empty($profile_image) ? $profile_image : null
            ];

            $result = AdminModel::updateProfile($user_id, $data);

            if ($result) {
                return response()->json(['result' => '1', 'msg' => 'Profile updated successfully', 'data' => $data]);
            } else {
                return response()->json(['result' => '-1', 'msg' => 'No changes were found!']);
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
}
