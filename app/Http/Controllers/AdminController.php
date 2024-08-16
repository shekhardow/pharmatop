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
            $search = $request->query('search') ?? null;
            $result = AdminModel::getAllCategories($per_page, $search);

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

            $alreadyExists = select('course_categories', 'id', ['category_name' => $request->input('category_name'), ['status', '!=', 'Deleted']])->first();
            if (!empty($alreadyExists)) {
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

            $alreadyExists = select('course_categories', 'id', ['category_name' => $request->input('category_name'), ['status', '!=', 'Deleted']])->first();
            if (!empty($alreadyExists)) {
                return response()->json(['result' => -1, 'msg' => 'The category name has already been taken!']);
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

    public function getAllCourses(Request $request)
    {
        try {
            $per_page = $request->query('per_page') ?? 10;
            $search = $request->query('search') ?? null;
            $result = AdminModel::getAllCourses($per_page, $search);

            if (!empty($result)) {
                foreach ($result as $value) {
                    $category = AdminModel::getCategoryById($value->category_id);
                    $value->category_name = !empty($category->category_name) ? $category->category_name : null;
                    $value->skills = !empty($value->skills) ? json_decode($value->skills) : null;
                    $videos = select('course_module_videos', 'id', ['course_id' => $value->id, 'status' => 'Acive']);
                    $documents = select('course_documents', 'id', ['course_id' => $value->id, 'document_type' => 'pdf', 'status' => 'Acive']);
                    $presentations = select('course_documents', 'id', ['course_id' => $value->id, 'document_type' => 'ppt', 'status' => 'Acive']);
                    $value->features = [
                        'videos' => !empty($videos) ? count($videos) : 0,
                        'documents' => !empty($documents) ? count($documents) : 0,
                        'presentations' => !empty($presentations) ? count($presentations) : 0,
                        'language' => !empty($value->language) ? json_decode($value->language) : null
                    ];
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

            $result = AdminModel::getCourseById($id);

            if (!empty($result)) {
                $category = AdminModel::getCategoryById($result->category_id);
                $result->category_name = !empty($category->category_name) ? $category->category_name : null;
                $result->skills = !empty($result->skills) ? json_decode($result->skills) : null;
                $videos = select('course_module_videos', 'id', ['course_id' => $result->id, 'status' => 'Acive']);
                $documents = select('course_documents', 'id', ['course_id' => $result->id, 'document_type' => 'pdf', 'status' => 'Acive']);
                $presentations = select('course_documents', 'id', ['course_id' => $result->id, 'document_type' => 'ppt', 'status' => 'Acive']);
                $result->features = [
                    'videos' => !empty($videos) ? count($videos) : 0,
                    'documents' => !empty($documents) ? count($documents) : 0,
                    'presentations' => !empty($presentations) ? count($presentations) : 0,
                    'language' => !empty($result->language) ? json_decode($result->language) : null
                ];
                return response()->json(['result' => 1, 'msg' => 'Course data fetched successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No data found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function addCourse(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'course_name' => 'required',
                'price' => 'required',
                'skills' => 'required',
                'description' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $alreadyExists = select('courses', 'id', ['course_name' => $request->input('course_name'), ['status', '!=', 'Deleted']])->first();
            if (!empty($alreadyExists)) {
                return response()->json(['result' => -1, 'msg' => 'The course name has already been taken!']);
            }

            if ($request->hasfile('course_image')) {
                $course_image = singleUpload($request, 'course_image', 'admin');
            } else {
                return response()->json(['result' => -1, 'msg' => 'Upload course image!']);
            }

            $data = [
                'category_id' => !empty($request->input('category_id')) ? $request->input('category_id') : null,
                'course_name' => !empty($request->input('course_name')) ? $request->input('course_name') : null,
                'language' => !empty($request->input('language')) ? $request->input('language') : null,
                'price' => !empty($request->input('price')) ? $request->input('price') : null,
                'skills' => !empty($request->input('skills')) ? json_encode($request->input('skills')) : null,
                'description' => !empty($request->input('description')) ? $request->input('description') : null,
                'course_image' => !empty($course_image) ? $course_image : null
            ];

            $result = AdminModel::addCourse($data);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Course added successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function updateCourse(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required',
                'category_id' => 'required',
                'course_name' => 'required',
                'price' => 'required',
                'skills' => 'required',
                'description' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $course_id = $request->input('course_id');
            $course = select('courses', '*', ['id' => $course_id])->first();
            if (empty($course)) {
                return response()->json(['result' => -1, 'msg' => 'Invalid Id!']);
            }

            $alreadyExists = select('courses', 'id', ['course_name' => $request->input('course_name'), ['status', '!=', 'Deleted']])->first();
            if (!empty($alreadyExists)) {
                return response()->json(['result' => -1, 'msg' => 'The course name has already been taken!']);
            }

            if ($request->hasfile('course_image')) {
                $course_image = singleUpload($request, 'course_image', 'admin');
            } else {
                $course_image = $course->course_image;
            }

            $data = [
                'category_id' => !empty($request->input('category_id')) ? $request->input('category_id') : null,
                'course_name' => !empty($request->input('course_name')) ? $request->input('course_name') : null,
                'language' => !empty($request->input('language')) ? $request->input('language') : null,
                'price' => !empty($request->input('price')) ? $request->input('price') : null,
                'skills' => !empty($request->input('skills')) ? json_encode($request->input('skills')) : null,
                'description' => !empty($request->input('description')) ? $request->input('description') : null,
                'course_image' => !empty($course_image) ? $course_image : null
            ];

            $result = AdminModel::updateCourse($course_id, $data);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Course updated successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function deleteCourse(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $course_id = $request->input('course_id');
            $course = select('courses', '*', ['id' => $course_id])->first();
            if (empty($course)) {
                return response()->json(['result' => -1, 'msg' => 'Invalid Id!']);
            }

            $result = AdminModel::deleteCourse($course_id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Course deleted successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Already deleted!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function addModuleVideo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required',
                'title' => 'required',
                'description' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $alreadyExists = select('course_module_videos', 'id', ['title' => $request->input('title'), ['status', '!=', 'Deleted']])->first();
            if (!empty($alreadyExists)) {
                return response()->json(['result' => -1, 'msg' => 'The module title has already been taken!']);
            }

            if ($request->hasfile('thumbnail')) {
                $thumbnail = singleUpload($request, 'thumbnail', 'admin');
            } else {
                return response()->json(['result' => -1, 'msg' => 'Upload thumbnail image!']);
            }

            if ($request->hasfile('video')) {
                $video = singleUpload($request, 'video', 'admin');
            } else {
                return response()->json(['result' => -1, 'msg' => 'Upload video!']);
            }

            $data = [
                'course_id' => !empty($request->input('course_id')) ? $request->input('course_id') : null,
                'title' => !empty($request->input('title')) ? $request->input('title') : null,
                'description' => !empty($request->input('description')) ? $request->input('description') : null,
                'thumbnail' => !empty($thumbnail) ? $thumbnail : null,
                'video' => !empty($video) ? $video : null
            ];

            $result = AdminModel::addModuleVideo($data);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Module video added successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function deleteModuleVideo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'video_id' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $video_id = $request->input('video_id');
            $course = select('course_module_videos', '*', ['id' => $video_id])->first();
            if (empty($course)) {
                return response()->json(['result' => -1, 'msg' => 'Invalid Id!']);
            }

            $result = AdminModel::deleteModuleVideo($video_id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Video deleted successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Already deleted!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function addModuleDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required',
                'document_name' => 'required',
                'document_type' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $alreadyExists = select('course_documents', 'id', ['document_name' => $request->input('document_name'), ['status', '!=', 'Deleted']])->first();
            if (!empty($alreadyExists)) {
                return response()->json(['result' => -1, 'msg' => 'The documen name has already been taken!']);
            }

            if ($request->hasfile('ducument')) {
                $extension = $request->file('ducument')->getClientOriginalExtension();
                if ($extension === 'pdf' && $request->input('document_type') !== 'pdf') {
                    return response()->json(['result' => -1, 'msg' => 'File type and document_type do not match! Please upload a PDF.']);
                }
                $ducument = singleUpload($request, 'ducument', 'admin');
            } else {
                return response()->json(['result' => -1, 'msg' => 'Upload ducument!']);
            }

            $data = [
                'course_id' => !empty($request->input('course_id')) ? $request->input('course_id') : null,
                'document_name' => !empty($request->input('document_name')) ? $request->input('document_name') : null,
                'document_type' => !empty($request->input('document_type')) ? $request->input('document_type') : null,
                'ducument' => !empty($ducument) ? $ducument : null
            ];

            $result = AdminModel::addModuleDocument($data);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Module document added successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }

    public function deleteModuleDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'document_id' => 'required'
            ], [
                'required' => 'The :attribute field is required'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }

            $document_id = $request->input('document_id');
            $course = select('course_documents', '*', ['id' => $document_id])->first();
            if (empty($course)) {
                return response()->json(['result' => -1, 'msg' => 'Invalid Id!']);
            }

            $result = AdminModel::deleteModuleDocument($document_id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Document deleted successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Already deleted!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }
}
