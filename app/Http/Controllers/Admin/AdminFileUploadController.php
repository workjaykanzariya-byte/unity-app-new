<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\FileController as ApiFileController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminFileUploadController extends Controller
{
    public function upload(Request $request, ApiFileController $fileController)
    {
        return $fileController->upload($request);
    }
}
