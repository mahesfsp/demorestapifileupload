<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileController extends Controller {

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'file' => 'required|mimetypes:image/jpeg,image/png,application/pdf|max:500000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                // 'message' => __('staticwords.Bad Request'),   
                'message' =>$validator->errors(),   
            ], 400);
        } 
            
        $path = $request->file('file')->store('public/files');
        $publicUrl = env('APP_URL', null) . str_replace("public", "/storage", $path);
        
        $file = new File;
        $file->uuid = Str::uuid();
        $file->name = $request->name ? $request->name : null;
        $file->type = $request->file('file')->getMimeType();
        $file->url = $publicUrl;

        if ($file->save()) {
            return response()->json([
                'data' => $file
            ], 200);
        } else {
            return response()->json([
                'message' => __('staticwords.Internal server error.'),
            ], 500);
        }
    }

   
}
