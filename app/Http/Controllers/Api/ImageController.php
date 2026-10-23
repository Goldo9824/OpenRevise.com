<?php

namespace App\Http\Controllers\Api;

use App\Content\ImageStore;
use App\Models\Content\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ImageController extends ApiController
{
    /** GET /v1/images: every uploaded diagram and the tag that shows it. */
    public function index(Request $request): JsonResponse
    {
        $this->summary($request, 'Listed images');

        return $this->respond([
            'images' => Image::orderBy('name')->get()->map(fn ($i) => Presenter::image($i))->all(),
        ]);
    }

    /**
     * POST /v1/images: a diagram, sent either as a multipart "file" upload
     * or as JSON with the file base64-encoded in "data" (or SVG markup in "svg").
     */
    public function store(Request $request, ImageStore $store): JsonResponse
    {
        $maxKb = config('openrevise.api.max_image_kb');

        $request->validate([
            'name' => ['required_without:file', 'nullable', 'string', 'max:120'],
            'file' => ['nullable', 'file', "max:$maxKb"],
            'data' => ['nullable', 'string'],
            'svg' => ['nullable', 'string'],
            'alt' => ['nullable', 'string', 'max:250'],
            'replace' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('file')) {
            $bytes = (string) file_get_contents($request->file('file')->getRealPath());
            $name = $request->input('name') ?: $request->file('file')->getClientOriginalName();
        } elseif ($request->filled('svg')) {
            $bytes = $request->input('svg');
            $name = $request->input('name');
        } elseif ($request->filled('data')) {
            $data = preg_replace('/^data:[^;]+;base64,/', '', $request->input('data'));
            $bytes = base64_decode($data, true);
            $name = $request->input('name');

            if ($bytes === false) {
                throw ValidationException::withMessages(['data' => '"data" must be base64-encoded.']);
            }
        } else {
            throw ValidationException::withMessages(['file' => 'Send the image as a "file" upload, as base64 in "data", or as SVG markup in "svg".']);
        }

        if (strlen($bytes) > $maxKb * 1024) {
            throw ValidationException::withMessages(['file' => "Images must be under {$maxKb} KB."]);
        }

        $image = $store->store($bytes, (string) $name, $request->input('alt'), $request->boolean('replace'));

        $this->summary($request, "Uploaded image \"{$image->name}\"");

        return $this->respond(Presenter::image($image), $image->wasRecentlyCreated ? 201 : 200);
    }
}
