<?php

namespace App\Http\Controllers\Api;

trait ApiResponse
{
    protected function success($data = null, ?string $message = null, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => null,
        ], $status);
    }

    protected function error(string $message, int $status = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
