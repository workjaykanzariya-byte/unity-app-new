<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\FileController as BaseFileController;
use Illuminate\Http\Request;

class FileController extends BaseFileController
{
    public function upload(Request $request)
    {
        $filesInput = $request->file('file');

        if (is_array($filesInput)) {
            $request->validate([
                'file' => ['required', 'array'],
                'file.*' => ['file', 'max:512000'],
            ]);
        } else {
            $request->validate([
                'file' => ['required', 'file', 'max:512000'],
            ]);
        }

        return parent::upload($request);
    }
}
