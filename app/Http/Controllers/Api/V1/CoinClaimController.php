<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\CoinClaims\StoreCoinClaimRequest;
use App\Http\Resources\CoinClaimActivityResource;
use App\Http\Resources\CoinClaimRequestResource;
use App\Models\CoinClaimRequest;
use App\Models\FileModel;
use App\Services\CoinClaims\CoinClaimEmailService;
use App\Support\CoinClaims\CoinClaimActivityRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CoinClaimController extends BaseApiController
{
    public function __construct(
        private readonly CoinClaimActivityRegistry $registry,
        private readonly CoinClaimEmailService $emailService,
    ) {
    }

    public function activities(): JsonResponse
    {
        $items = CoinClaimActivityResource::collection(collect($this->registry->listForApi()));

        return $this->success(['items' => $items], null);
    }

    public function store(StoreCoinClaimRequest $request): JsonResponse
    {
        $requestId = (string) Str::uuid();
        $user = $request->user();

        Log::info('Coin claim store request received', [
            'request_id' => $requestId,
            'user_id' => $user?->id,
            'activity_code' => $request->input('activity_code'),
        ]);

        try {
            $activityCode = (string) $request->input('activity_code');
            $fieldMap = $this->registry->fieldMap($activityCode);
            $fields = (array) $request->input('fields', []);
            $uploaded = $request->file('files', []);

            $normalizedFields = $fields;
            $fileIds = [];

            foreach ($fieldMap as $fieldKey => $fieldDefinition) {
                if (($fieldDefinition['type'] ?? null) === 'phone' && isset($normalizedFields[$fieldKey])) {
                    $normalizedFields[$fieldKey . '_normalized'] = preg_replace('/\D+/', '', (string) $normalizedFields[$fieldKey]);
                }

                $file = $uploaded[$fieldKey] ?? null;
                if ($file instanceof UploadedFile) {
                    $fileIds[$fieldKey] = $this->storeClaimFile($file, (string) $user?->id);
                }
            }

            $claim = CoinClaimRequest::create([
                'user_id' => $user->id,
                'activity_code' => $activityCode,
                'payload' => [
                    'fields' => $normalizedFields,
                    'files' => $fileIds,
                ],
                'status' => 'pending',
                'coins_awarded' => null,
            ]);

            $claim->load('user:id,display_name,first_name,last_name,email,phone');

            $this->emailService->sendSubmitted($claim);

            return $this->success(new CoinClaimRequestResource($claim), 'Coin claim submitted successfully.', 201);
        } catch (ValidationException $exception) {
            Log::error('Coin claim validation failed', [
                'request_id' => $requestId,
                'user_id' => $user?->id,
                'errors' => $exception->errors(),
            ]);

            return $this->error('Validation failed.', 422, $exception->errors());
        } catch (\Throwable $exception) {
            Log::error('Coin claim submission failed', [
                'request_id' => $requestId,
                'user_id' => $user?->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->error($exception->getMessage(), 500);
        }
    }

    private function storeClaimFile(UploadedFile $file, string $userId): string
    {
        $disk = config('filesystems.default', 'public');
        $path = $file->store('uploads/' . now()->format('Y/m/d'), $disk);

        $record = FileModel::create([
            'uploader_user_id' => $userId,
            's3_key' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return (string) $record->id;
    }
}
