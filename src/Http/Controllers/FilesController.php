<?php

namespace Circle33\Flysystem\Qcloud\Http\Controllers;

use File;
use Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Circle33\Flysystem\Qcloud\Models\Circle33File;
use Circle33\Flysystem\Qcloud\Http\Resources\FileResource;

class FilesController extends ApiController
{
    public function __construct()
    {
        $this->storage = Storage::disk('qcloud_oss');
    }

    public function index(Request $request)
    {
        if ($directory = $request->get('directory')) {
            $circle33files = Circle33File::query()->where('path', $directory)->paginate();
            return FileResource::collection($circle33files);
        }

        \abort(400);
    }

    public function exists(Request $request)
    {
        if ($filename = $request->get('filename')) {
            $circle33file = Circle33File::query()->where('filename', 'like', '%'. $filename .'%')->get();
            return FileResource::collection($circle33file);
        }

        \abort(400);
    }

    public function show(Request $request)
    {
        if ($filename = $request->get('filename')) {
            $circle33file = Circle33File::query()->where('filename', $filename)->get();
            return FileResource::collection($circle33file);
        }

        \abort(400);
    }

    public function store(Request $request)
    {
        $file = Input::file('file');
        $path = $file->getClientOriginalName();
        $body = File::get($file);

        try {
            $this->storage->write($path, $body);
        } catch (\Exception $e) {
            \abort(502, $e->getMessage());
        }

        $circle33file = Circle33File::create([
            'filename' => $path,
            'size'     => $file->getSize(),
            'path'     => '/',
            'mime'     => $file->getMimeType(),
            'url'      => '',
        ]);

        return response()->json([
            'message' => '文件存储成功！',
        ]);
    }

    public function rename(Circle33File $circle33file, Request $request)
    {
        if (Circle33File::query()->find($circle33file->id)) {
            $newFilename = $request->get('newFilename');

            try {
                $this->storage->rename($circle33file->filename, $newFilename);
            } catch (\Exception $e) {
                \abort(502, $e->getMessage());
            }

            $circle33file->update(['filename' => $newFilename]);

            return new FileResource($circle33file);
        }

        \abort(400);
    }

    public function destroy(Circle33File $circle33file)
    {
        if (Circle33File::query()->find($circle33file->id)) {
            try {
                $this->storage->delete($circle33file->filename);
            } catch (\Exception $e) {
                \abort(502, $e->getMessage());
            }

            $circle33file->delete();

            return response('', 204);
        }

        \abort(400);
    }

    public function copy(Circle33File $circle33file, Request $request)
    {
        if (Circle33File::query()->find($circle33file->id)) {
            $newFilename = $request->get('newFilename');

            try {
                $this->storage->copy($circle33file->filename, $newFilename);
            } catch (\Exception $e) {
                \abort(502, $e->getMessage());
            }

            $newFile = Circle33File::create([
                'filename' => $newFilename,
                'size'     => $circle33file->size,
                'path'     => $circle33file->path,
                'mime'     => $circle33file->mime,
                'url'      => ''
            ]);

            return new FileResource($newFile);
        }

        \abort(400);
    }
}
