<?php

namespace App\Http\Controllers;

use Validator;
use Notification;
use Carbon\Carbon;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\CustomFileUpload;
use App\Http\Resources\CustomFileUploadResource;

class S3FileUploadDownloadController extends Controller
{
    use ApiResponses;

    public function fileUpload(Request $request)
    {
        $rules = [
            'file_name' => 'required|string',
            'file' => 'file',
        ];

        $messages = [
            'file_name.required' => 'File name is required',
            'file.required' => 'File attachment is required',
        ];


        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }
    

        $file = $request->file('file');
        $s3 = Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $key = date('mdYhia') . '_' . str_random(6) . '.' . $file->getClientOriginalExtension();
        $url = $client->putObject([
            'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
            'Key'        => 'coaching-resources/lessons/custom/images/' . $key,
            'ACL'          => 'private',
            'ContentType' => $file->getMimeType(),
            'SourceFile' => $file,
        ]);

        $url = $url->get('ObjectURL');
        
        $myfile = new CustomFileUpload();
        $myfile->name = $file->getClientOriginalName();
        $myfile->url = $url;
        $myfile->key = $key;
        $myfile->type = $file->getMimeType();
        $myfile->save();

        $transform = new CustomFileUploadResource($myfile);

        return $this->successResponse($transform, 201);
    }

    public function getFile(Request $request)
    {
        $rules = [
            'key' => 'required|string',
        ];

        $messages = [
            'key.required' => 'File name is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        $s3 = Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+2 minutes";
        $key = $request->key;

        $cmd = $client->getCommand('GetObject', [
            'Bucket' => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
            'Key' => 'coaching-resources/lessons/custom/images/' . $key,
            'ACL' => 'private',
        ]);

        $request = $client->createPresignedRequest($cmd, $expiry);

        $presignedUrl = (string)$request->getUri();

        return response()->json(['url' => $presignedUrl], 201);
    }


}
