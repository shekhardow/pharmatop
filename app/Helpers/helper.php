<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function generateOtp()
{
    return 1234;
    // return rand(1111, 9999);
}

function generateToken()
{
    $token = openssl_random_pseudo_bytes(16);
    $token = bin2hex($token);
    return $token;
}

// For Id Encryption
function encryptionID($id)
{
    $result = substr(uniqid(), 0, 10) . $id . substr(uniqid(), 0, 10);
    return $result;
}

// For Id Decryption
function decryptionID($result_id)
{
    $id = substr($result_id, 10);
    $result_id = substr($id, 0, -10);
    return $result_id;
}

// Common function to get the data from database
function select($table, $col = '*', $where = null)
{
    $data = DB::table($table);
    if (!empty($col)) {
        $data->addSelect($col);
    }
    if (!empty($where)) {
        $data->where($where);
    }
    return $data->get();
}

// Common function to insert the data into database
function insert($table, $data = [])
{
    DB::table($table)->insert($data);
    return DB::getPdo()->lastInsertId();
}

// Common function to update the data into database
function update($table, $wherecol, $wherevalue, $data, $wherecondition = '=')
{
    $affected_row = DB::table($table)->where($wherecol, $wherecondition, $wherevalue)->update($data);
    return $affected_row;
}

// Common function to delete the data from database
function delete($table, $wherecol, $wherevalue)
{
    $affected_row = DB::table($table)->where($wherecol, $wherevalue)->delete();
    return $affected_row;
}

// Common function to change the status
function change_status($id, $status, $table, $wherecol, $status_variable, $wherecondition = '=')
{
    $data = array(
        $status_variable => $status,
    );
    $affected_row = DB::table($table)->where($wherecol, $wherecondition, $id)->update($data);
    return $affected_row;
}

// String Encryption
function encryptionString($string)
{
    $ciphering = "AES-128-CTR";
    $iv_length = openssl_cipher_iv_length($ciphering);
    $options = 0;
    $encryption_iv = '1234567890987654321';
    $encryption_key = "DESIGNOWEB";
    $encryption = openssl_encrypt($string, $ciphering, $encryption_key, $options, $encryption_iv);
    return $encryption;
}

// String Decryption
function decryptionString($encryption)
{
    $options = 0;
    $ciphering = "AES-128-CTR";
    $decryption_iv = '1234567890987654321';
    $decryption_key = "DESIGNOWEB";
    $decryption = openssl_decrypt($encryption, $ciphering, $decryption_key, $options, $decryption_iv);
    return $decryption;
}

// Date Validation
function validateDate($mystring)
{
    $invaliddate = "1970";
    if (strpos($mystring, $invaliddate) !== false) {
        return true;
    } else {
        return false;
    }
}

// Change Date Format
function formatDate($date, $type = '')
{
    if ($type == 'short') {
        $format = 'd M Y';
    } else {
        $format = 'd M Y, h:i A';
    }
    return date($format, strtotime($date));
}

// For Single Upload
function singleUpload($request, $file_name, $path)
{
    if ($request->hasfile($file_name)) {
        $file = $request->file($file_name);
        $name = time() . '.' . $file->extension();
        sleep(1);
        $file->move(base_path('uploads/') . $path, $name);
        return $name;
    } else {
        return false;
    }
}

// For Multiple Upload
function multipleUploads($request, $file_name, $path)
{
    if ($request->hasfile($file_name)) {
        $data = [];
        foreach ($request->file($file_name) as $file) {
            $name = time() . '.' . $file->extension();
            sleep(1);
            $file->move(base_path('uploads/') . $path, $name);
            $data[] = $name;
        }
        return $data;
    } else {
        return false;
    }
}

// For Single AWS Upload
function singleAwsUpload(Request $request, $file_name, $path)
{
    if ($request->hasfile($file_name)) {
        $file = $request->file($file_name);
        $path = Storage::disk('s3')->put($path, $file);
        return $path ? Storage::disk('s3')->url($path) : false;
    }
    return false;
}

// For Multiple AWS Uploads
function multipleAwsUploads(Request $request, $file_name, $path)
{
    if ($request->hasfile($file_name)) {
        $data = array_map(function ($file) use ($path) {
            $filePath = Storage::disk('s3')->put($path, $file);
            return Storage::disk('s3')->url($filePath);
        }, $request->file($file_name));

        return !empty($data) ? $data : false;
    }
    return false;
}

function generateSlug($string, $separator = '-', $maxLength = 100)
{
    $slug = strtolower($string);
    $slug = preg_replace("/[^a-z0-9]+/", $separator, $slug);
    $slug = trim($slug, $separator);
    $slug = substr($slug, 0, $maxLength);
    return $slug;
}

function limitWords($description, $max_words, $ellipsis = '...', $separator = ' ')
{
    $description = strip_tags($description);
    $words = explode($separator, $description);
    $limited_words = array_slice($words, 0, $max_words);
    $limited_description = implode($separator, $limited_words) . $ellipsis;
    return $limited_description;
}

function uniqueId()
{
    $str = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $nstr = str_shuffle($str);
    $unique_id = substr($nstr, 0, 10);
    return $unique_id;
}

function sendMail($data)
{
    $from = env('MAIL_FROM_ADDRESS', 'example@gmail.com');
    $to = $data['to'];
    $subject = $data['subject'];
    $view = 'mail.' . $data['view_name'];
    Mail::send($view, $data, function ($message) use ($from, $to, $subject) {
        $message->from($from, 'Pharmatop');
        $message->to($to);
        $message->subject($subject);
    });
    return true;
}

function getUserByToken($token)
{
    return DB::table('users')
        ->select('users.*', 'user_authentications.user_token', 'user_authentications.firebase_token')
        ->leftJoin('user_authentications', 'users.id', '=', 'user_authentications.user_id')
        ->where('user_authentications.user_token', $token)->get()->first();
}

/* User Authentication Function */
function userAuthentication(Request $request)
{
    $token = $request->header('token');
    if (empty($token)) {
        return response()->json(['result' => -2, 'msg' => 'Header token is required!'], 401);
    }
    $user = getUserByToken($token);
    if (empty($user) || $user == null) {
        return response()->json(['result' => -2, 'msg' => 'Invalid token!'], 401);
    }
    if ($user->is_verified === 'no') {
        return response()->json(['result' => -2, 'msg' => 'Please verify yourself. We have resent the verification link to your email. Please check your mail.'], 401);
    }
    if ($user->status === 'Deleted') {
        return response()->json(['result' => -2, 'msg' => 'This account has been deleted.'], 401);
    }
    if ($user->status === 'Disabled') {
        return response()->json(['result' => -2, 'msg' => 'This account is disabled.'], 401);
    }
    if ($user->status === 'Blocked') {
        return response()->json(['result' => -2, 'msg' => 'This account is blocked.'], 401);
    }
    if ($user->status === 'Inactive') {
        return response()->json(['result' => -2, 'msg' => 'This account has been inactive by admin.'], 401);
    }
    return response()->json(['result' => 1, 'msg' => 'Authentication successful', 'data' => $user]);
}
