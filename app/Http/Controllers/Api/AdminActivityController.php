<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\UpdateActivityAdminRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\ActivityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdminActivityController extends BaseApiController
{
    public function index(Request $request)
    {
        // TODO: enforce admin authorization here

        $status = $request->input('status', 'pending');

        $query = Activity::with(['user', 'circle', 'event'])
            ->where('status', $status);

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = [
            'items' => ActivityResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function show(Activity $activity)
    {
        $activity->load(['user', 'circle', 'event']);

        return $this->success(new ActivityResource($activity));
    }

    public function updateStatus(UpdateActivityAdminRequest $request, string $id)
    {
        $admin = $request->user();
        // TODO: enforce admin authorization here

        $data = $request->validated();

        try {
            DB::beginTransaction();

            $activity = Activity::where('id', $id)->lockForUpdate()->first();

            if (! $activity) {
                DB::rollBack();
                return $this->error('Activity not found', 404);
            }

            if ($activity->status !== 'pending') {
                DB::rollBack();
                return $this->error('Only pending activities can be updated', 422);
            }

            $fromStatus = $activity->status;
            $toStatus = $data['status'];
            $adminNotes = $data['admin_notes'] ?? null;
            $reference = $data['reference'] ?? ('activity_'.$activity->type);

            $activity->status = $toStatus;
            $activity->admin_notes = $adminNotes;
            $activity->requires_verification = false;
            $activity->verified_by_admin_id = $admin->id;
            $activity->verified_at = now();

            if ($toStatus === 'approved') {
                $coinsAwarded = (int) ($data['coins_awarded'] ?? 0);
                if ($coinsAwarded <= 0) {
                    DB::rollBack();
                    return $this->error('coins_awarded must be greater than zero when approving', 422);
                }

                $activity->coins_awarded = $coinsAwarded;

                $txRow = DB::selectOne(
                    "SELECT coins_apply_transaction(?, ?, ?, ?, ?) AS transaction_id",
                    [
                        $activity->user_id,
                        $activity->coins_awarded,
                        $activity->id,
                        $reference,
                        $admin->id,
                    ]
                );

                $activity->coins_ledger_id = $txRow->transaction_id ?? null;
            } else {
                $activity->coins_awarded = 0;
                $activity->coins_ledger_id = null;
            }

            $activity->save();

            ActivityAudit::create([
                'activity_id' => $activity->id,
                'changed_by' => $admin->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'change_reason' => $adminNotes ?: $reference,
                'created_at' => now(),
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return $this->error('Failed to update activity: '.$e->getMessage(), 500);
        }

        $activity->load(['user', 'circle', 'event']);

        return $this->success(new ActivityResource($activity), 'Activity updated');
    }
}
