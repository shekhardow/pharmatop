<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\CommonModel;

class CommonController extends Controller
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

            $user = CommonModel::getUserByEmail($request->input('email'));

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

            $user = CommonModel::getUserByEmail($request->input('email'));

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
                update($user->source, 'id', $user->id, ['otp' => $otp]);
                $user = CommonModel::getUserByEmail($user->email);
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
                'email' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $user = CommonModel::getUserByEmail($request->input('email'));

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
                update($user->source, 'id', $user->id, ['otp' => $otp]);
                $user = CommonModel::getUserByEmail($user->email);
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
                'email' => 'required',
                'otp' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $otp = $request->input('otp');
            $user = CommonModel::getUserByEmail($request->input('email'));

            if (!empty($user)) {
                if ($user->status == 'Inactive' || $user->status == 'Blocked') {
                    return response()->json(['result' => -2, 'msg' => "This account is $user->status!"]);
                }
                if ($otp === $user->otp) {
                    update($user->source, 'id', $user->id, ['reset_password_verified' => 'Yes']);
                }
                $user = CommonModel::getUserByEmail($user->email);
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
                'email' => 'required',
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

            $email = $request->input('email');
            $user = CommonModel::getUserByEmail($email);

            if (!empty($user)) {
                $password = Hash::make($request->input('password'));
                $result = CommonModel::resetPassword($email, $password);
                if ($result) {
                    update($user->source, 'id', $user->id, ['otp' => null, 'reset_password_verified' => 'No']);
                    return response()->json(['result' => 1, 'msg' => 'Password reset successfully']);
                } else {
                    return response()->json(['result' => -1, 'msg' => 'Account does not exist or verification failed!']);
                }
            } else {
                return response()->json(['result' => -1, 'msg' => 'User not found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }
}
