<?php

namespace App\Helpers;

class ApiResponseHelper
{
    public static function success($data, $message = 'Operation successful', $status_code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'status_code' => $status_code,
            'data' => $data,
        ], $status_code);
    }

    public static function error($message = 'Operation failed', $status_code = 400, $errors = [])
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'status_code' => $status_code,
            'errors' => $errors,
        ], $status_code);
    }
}
