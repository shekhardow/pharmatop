<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminModel;
use App\Models\CommonModel;

class AdminController extends Controller
{
    public function getAdminById($id = null)
    {
        try {
            if (empty($id)) {
                return response()->json(['result' => 0, 'errors' => 'Id is required!']);
            }

            $result = AdminModel::getAdminById($id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Admin data fetched successfully', 'data' => $result]);
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
            $rules = [
                'user_id' => 'required'
            ];
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

            $user_id = $request->input('user_id');

            $remove_image = $request->query->get('remove_profile_image');
            if ($remove_image) {
                update('admins', 'id', $user_id, ['profile_image' => null]);
                return response()->json(['result' => 1, 'msg' => 'Profile picture removed!', 'data' => null]);
            }

            $user = AdminModel::getAdminById($user_id);

            $logoResult = $request->hasFile('logo') ? singleAwsUpload($request, 'logo') : $user->logo;
            $logo = $logoResult->url ?? $user->logo;
            $faviconResult = $request->hasFile('favicon') ? singleAwsUpload($request, 'favicon') : $user->favicon;
            $favicon = $faviconResult->url ?? $user->favicon;
            $profileImageResult = $request->hasFile('profile_image') ? singleAwsUpload($request, 'profile_image') : $user->profile_image;
            $profile_image = $profileImageResult->url ?? $user->profile_image;

            $data = [
                'logo' => !empty($logo) ? $logo : null,
                'favicon' => !empty($favicon) ? $favicon : null,
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

            $result = AdminModel::updateProfile($user_id, $data);
            if ($result) {
                $updatedUserDetails = CommonModel::getUserByEmail($user->email);
                return response()->json(['result' => 1, 'msg' => 'Profile updated successfully', 'data' => $updatedUserDetails]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Failed to update profile!']);
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

            if ($request->has('title') || $request->has('description') || $request->has('sub_heading') || $request->hasFile('banner_image')) {
                $data = [
                    'updated_at' => now()
                ];
                if ($request->has('title')) {
                    $data['title'] = $request->input('title');
                }
                if ($request->has('description')) {
                    $data['description'] = $request->input('description');
                }
                if ($request->has('sub_heading')) {
                    $data['sub_heading'] = $request->input('sub_heading');
                }
                if ($request->hasFile('banner_image')) {
                    $imgResult = singleAwsUpload($request, 'banner_image');
                    $banner_image = $imgResult->url;
                    $data['banner_image'] = !empty($banner_image) ? $banner_image : null;
                }
                $result = AdminModel::updateStaticContent($content_type, $data);
                if ($result) {
                    return response()->json(['result' => 1, 'msg' => "$content_type content updated successfully", 'data' => $result]);
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
            $total_users = select('users', 'id', [['status', '!=', 'Deleted']])->count();
            $total_courses = select('courses', 'id', [['status', '!=', 'Deleted']])->count();
            $earnings = select('user_payments', 'amount', [['status', '!=', 'Deleted']])->sum('amount');
            $top_category = select('course_categories', ['category_name', 'id'], [['status', '!=', 'Deleted'], ['upvoted', '=', 'Yes']])->first();
            $top_course = select('courses', ['course_name', 'id'], [['status', '!=', 'Deleted'], ['upvoted', '=', 'Yes']])->first();
            $active_users = select('users', 'id', ['login_status' => 1, 'status' => 'Active'])->count();
            $active_users_percentage = !empty($active_users) ? ($active_users * 100) / $total_users : 0;
            $new_users = select('users', 'id', [['status', '!=', 'Deleted'], ['created_at', '>=', date('Y-m-d H:i:s', strtotime('-1 day'))]])->count();
            $new_users_percentage = !empty($new_users) ? ($new_users * 100) / $total_users : 0;

            $monthly_earnings = [];
            for ($month = 1; $month <= 12; $month++) {
                $start_date = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, 1, date('Y')));
                $end_date = date('Y-m-t 23:59:59', mktime(0, 0, 0, $month, 1, date('Y')));
                $earnings_for_month = select('user_payments', 'amount', [['status', '!=', 'Deleted'], ['created_at', '>=', $start_date], ['created_at', '<=', $end_date]])->sum('amount');
                $month_name = date('F', mktime(0, 0, 0, $month, 10));
                $monthly_earnings[$month_name] = !empty($earnings_for_month) ? $earnings_for_month : 0;
            }

            $result = [
                'total_users' => !empty($total_users) ? number_format($total_users) : 0,
                'total_courses' => !empty($total_courses) ? number_format($total_courses) : 0,
                'earnings' => !empty($earnings) ? number_format($earnings) : 0,
                'top_category' => !empty($top_category) ? $top_category->category_name : null,
                'top_category_id' => !empty($top_category) ? $top_category->id : null,
                'top_course' => !empty($top_course) ? $top_course->course_name : null,
                'top_course_id' => !empty($top_course) ? $top_course->id : null,
                'active_users' => !empty($active_users_percentage) ? number_format($active_users_percentage) : 0,
                'new_users' => !empty($new_users_percentage) ? number_format($new_users_percentage) : 0,
                'revenue' => $monthly_earnings
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
            $search = $request->query('search') ?? null;
            $result = AdminModel::getAllUsers($per_page, $search);

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
            if ($alreadyExists) {
                return response()->json(['result' => -1, 'msg' => 'The category name has already been taken!']);
            }

            if ($request->hasFile('category_image')) {
                $imgResult = singleAwsUpload($request, 'category_image');
                $category_image = $imgResult->url;
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

            $alreadyExists = select('course_categories', 'id', ['category_name' => $request->input('category_name'), ['id', '!=', $category_id], ['status', '!=', 'Deleted']])->first();
            if ($alreadyExists) {
                return response()->json(['result' => -1, 'msg' => 'The category name has already been taken!']);
            }

            if ($request->hasFile('category_image')) {
                $imgResult = singleAwsUpload($request, 'category_image');
                $category_image = $imgResult->url;
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
                return response()->json(['result' => -1, 'msg' => 'No changes were found!']);
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

            $hasCourses = AdminModel::hasCourses($category_id);
            if ($hasCourses) {
                return response()->json(['result' => -1, 'msg' => 'Cannot delete category. It has associated courses.']);
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

    public function upvoteCategory(Request $request)
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

            $result = AdminModel::upvoteCategory($category_id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Category upvoted successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Already upvoted!']);
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
                    $videos = select('course_module_videos', '*', ['course_id' => $value->id, 'status' => 'Active']);
                    $documents = select('course_documents', '*', ['course_id' => $value->id, 'document_type' => 'pdf', 'status' => 'Active']);
                    $presentations = select('course_documents', '*', ['course_id' => $value->id, 'document_type' => 'ppt', 'status' => 'Active']);
                    $value->features = [
                        'videos' => !empty($videos) ? count($videos) : 0,
                        'documents' => !empty($documents) ? count($documents) : 0,
                        'presentations' => !empty($presentations) ? count($presentations) : 0,
                        'language' => !empty($value->language) ? json_decode($value->language) : null
                    ];
                    $value->videos = !empty($videos) ? $videos : null;
                    $value->documents = !empty($documents) ? $documents : null;
                    $value->presentations = !empty($presentations) ? $presentations : null;
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
                $result->enrolled_users = AdminModel::getEnrolledUsers($result->id);
                $category = AdminModel::getCategoryById($result->category_id);
                $result->category_name = !empty($category->category_name) ? $category->category_name : null;
                $result->skills = !empty($result->skills) ? json_decode($result->skills) : null;
                $videos = select('course_module_videos', '*', ['course_id' => $id, 'status' => 'Active']);
                $documents = select('course_documents', '*', ['course_id' => $id, 'document_type' => 'pdf', 'status' => 'Active']);
                $presentations = select('course_documents', '*', ['course_id' => $id, 'document_type' => 'ppt', 'status' => 'Active']);
                $result->features = [
                    'videos' => !empty($videos) ? count($videos) : 0,
                    'documents' => !empty($documents) ? count($documents) : 0,
                    'presentations' => !empty($presentations) ? count($presentations) : 0,
                    'language' => !empty($result->language) ? ($result->language) : null
                ];
                $result->videos = !empty($videos) ? $videos : null;
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
            if ($alreadyExists) {
                return response()->json(['result' => -1, 'msg' => 'The course name has already been taken!']);
            }

            if ($request->hasFile('course_image')) {
                $imgResult = singleAwsUpload($request, 'course_image');
                $course_image = $imgResult->url;
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

            $alreadyExists = select('courses', 'id', ['course_name' => $request->input('course_name'), ['id', '!=', $course_id], ['status', '!=', 'Deleted']])->first();
            if ($alreadyExists) {
                return response()->json(['result' => -1, 'msg' => 'The course name has already been taken!']);
            }

            if ($request->hasFile('course_image')) {
                $imgResult = singleAwsUpload($request, 'course_image');
                $course_image = $imgResult->url;
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
                return response()->json(['result' => -1, 'msg' => 'No changes were found!']);
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

    public function upvoteCourse(Request $request)
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

            $result = AdminModel::upvoteCourse($course_id);

            if (!empty($result)) {
                return response()->json(['result' => 1, 'msg' => 'Course upvoted successfully', 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'Already upvoted!']);
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
            if ($alreadyExists) {
                return response()->json(['result' => -1, 'msg' => 'The module title has already been taken!']);
            } else {
                if ($request->hasFile('thumbnail')) {
                    $imgResult = singleAwsUpload($request, 'thumbnail');
                    $thumbnail = $imgResult->url;
                } else {
                    return response()->json(['result' => -1, 'msg' => 'Upload thumbnail image!']);
                }

                if ($request->hasFile('video')) {
                    $videoResult = singleAwsUpload($request, 'video');
                    $video = $videoResult->url;
                } else {
                    return response()->json(['result' => -1, 'msg' => 'Upload video!']);
                }

                $data = [
                    'course_id' => !empty($request->input('course_id')) ? $request->input('course_id') : null,
                    'title' => !empty($request->input('title')) ? $request->input('title') : null,
                    'description' => !empty($request->input('description')) ? $request->input('description') : null,
                    'thumbnail' => !empty($thumbnail) ? $thumbnail : null,
                    'video' => !empty($video) ? $video : null,
                    'duration' => !empty($videoResult->duration) ? $videoResult->duration : null
                ];

                $result = AdminModel::addModuleVideo($data);

                if (!empty($result)) {
                    return response()->json(['result' => 1, 'msg' => 'Module video added successfully', 'data' => $result]);
                } else {
                    return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
                }
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

            $alreadyExists = select('course_documents', 'id', ['document_name' => $request->input('document_name'), 'document_type' => $request->input('document_type'), ['status', '!=', 'Deleted']])->first();
            if ($alreadyExists) {
                return response()->json(['result' => -1, 'msg' => 'The document name has already been taken!']);
            } else {
                if ($request->hasFile('document')) {
                    $extension = $request->file('document')->getClientOriginalExtension();
                    if ($request->input('document_type') === 'pdf' && $extension !== 'pdf') {
                        return response()->json(['result' => -1, 'msg' => 'File type and document_type do not match! Please upload a PDF.']);
                    }
                    $imgResult = singleAwsUpload($request, 'document');
                    $document = $imgResult->url;
                } else {
                    return response()->json(['result' => -1, 'msg' => 'Upload document!']);
                }

                $data = [
                    'course_id' => !empty($request->input('course_id')) ? $request->input('course_id') : null,
                    'document_name' => !empty($request->input('document_name')) ? $request->input('document_name') : null,
                    'document_type' => !empty($request->input('document_type')) ? $request->input('document_type') : null,
                    'document' => !empty($document) ? $document : null
                ];

                $result = AdminModel::addModuleDocument($data);

                if (!empty($result)) {
                    return response()->json(['result' => 1, 'msg' => 'Module document added successfully', 'data' => $result]);
                } else {
                    return response()->json(['result' => -1, 'msg' => 'Something went wrong!']);
                }
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

    public function getAllBillingInfo(Request $request)
    {
        try {
            $per_page = $request->query('per_page') ?? 10;
            $search = $request->query('search') ?? null;
            $result = AdminModel::getAllBillingInfo($per_page, $search);
            if ($result) {
                foreach ($result as $row) {
                    $course_names = [];
                    $course_ids = json_decode($row->course_ids);
                    if (!empty($course_ids)) {
                        foreach ($course_ids as $id) {
                            $name = AdminModel::getCourseById($id);
                            if (!empty($name->course_name)) {
                                $course_names[] = $name->course_name;
                            }
                        }
                    }
                    $row->course_names = $course_names;
                    $user_name = AdminModel::getUserById($row->user_id);
                    $row->user_name = !empty($user_name) ? $user_name->first_name . ' ' . $user_name->last_name : null;
                }
                return response()->json(['result' => 1, 'msg' => "Billing info fetched successfully", 'data' => $result]);
            } else {
                return response()->json(['result' => -1, 'msg' => 'No content found!']);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => -5, 'msg' => $e->getMessage()]);
        }
    }
}
