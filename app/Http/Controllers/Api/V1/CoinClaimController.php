<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\CoinClaims\StoreCoinClaimRequest;
use App\Http\Resources\CoinClaimActivityResource;
use App\Http\Resources\CoinClaimRequestResource;
use App\Models\CoinClaimRequest;
use App\Models\FileModel;
use App\Support\CoinClaims\CoinClaimActivityRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CoinClaimController extends BaseApiController
{
    public function activities()
    {
        return $this->success([
            'items' => CoinClaimActivityResource::collection(collect(CoinClaimActivityRegistry::all())),
        ]);
    }

    public function store(StoreCoinClaimRequest $request)
    {
        $payload = $request->validated('fields', []);

        foreach ((CoinClaimActivityRegistry::byCode($request->validated('activity_code'))['fields'] ?? []) as $field) {
            if ($field['type'] !== 'file') {
                continue;
            }

            $file = $request->file('files.'.$field['key']);
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $stored = $this->storeFile($file, $request->user()?->id);
            $payload[$field['key'].'_id'] = (string) $stored->id;
        }

        $coinClaim = CoinClaimRequest::create([
            'user_id' => $request->user()->id,
            'activity_code' => $request->validated('activity_code'),
            'payload' => $payload,
            'status' => 'pending',
            'coins_awarded' => null,
        ]);

        return $this->success(new CoinClaimRequestResource($coinClaim), 'Coin claim submitted successfully.', 201);
    }

    public function my(Request $request)
    {
        $query = CoinClaimRequest::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(min((int) $request->query('per_page', 15), 100));

        return $this->success([
            'items' => CoinClaimRequestResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function storeFile(UploadedFile $file, ?string $userId): FileModel
    {
        $disk = config('filesystems.default', 'public');
        $ext = $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/'.now()->format('Y/m/d'), Str::uuid().($ext ? '.'.$ext : ''), $disk);

        return FileModel::create([
            'uploader_user_id' => $userId,
            's3_key' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'width' => null,
            'height' => null,
            'duration' => null,
        ]);
    }
}
