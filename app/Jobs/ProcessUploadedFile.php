<?php

namespace App\Jobs;

use App\Exceptions\MediaProcessingException;
use App\Models\FileModel;
use App\Services\Media\MediaProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUploadedFile implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $fileId)
    {
    }

    public function handle(MediaProcessor $processor): void
    {
        $file = FileModel::find($this->fileId);

        if (! $file) {
            return;
        }

        try {
            $processor->process($file);
        } catch (MediaProcessingException $e) {
            $this->markFailed($file, $e->getMessage());
        } catch (\Throwable $e) {
            $this->markFailed($file, 'Unexpected media processing failure.');
            Log::error('Media processing job failed', [
                'file_id' => $this->fileId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function markFailed(FileModel $file, string $message): void
    {
        $meta = $file->meta ?? [];
        $meta['processing_status'] = 'failed';
        $meta['processing_error'] = $message;
        $file->meta = $meta;
        $file->save();
    }
}
