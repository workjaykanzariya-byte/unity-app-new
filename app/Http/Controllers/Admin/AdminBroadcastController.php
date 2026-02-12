<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\FileController as ApiFileController;
use App\Jobs\SendAdminBroadcastJob;
use App\Models\AdminBroadcast;
use App\Support\AdminAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminBroadcastController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        $broadcasts = AdminBroadcast::query()
            ->with('createdBy')
            ->latest('created_at')
            ->paginate(20);

        return view('admin.broadcasts.index', [
            'broadcasts' => $broadcasts,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        return view('admin.broadcasts.form', [
            'broadcast' => new AdminBroadcast([
                'status' => 'draft',
                'recurrence' => 'none',
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $broadcast = new AdminBroadcast();
        $this->fillFromRequest($broadcast, $request);
        $broadcast->created_by_admin_id = (string) $request->user('admin')->id;
        $broadcast->save();

        if ($request->input('delivery_type') === 'send_now') {
            $this->markSendingAndDispatch($broadcast);

            return redirect()
                ->route('admin.broadcasts.index')
                ->with('success', 'Broadcast dispatch started successfully.');
        }

        return redirect()
            ->route('admin.broadcasts.edit', $broadcast)
            ->with('success', $this->successMessage($request));
    }

    public function edit(Request $request, AdminBroadcast $broadcast): View
    {
        $this->authorizeSuperAdmin($request);

        return view('admin.broadcasts.form', [
            'broadcast' => $broadcast,
            'mode' => 'edit',
            'nextRunPreview' => $broadcast->computeNextRunAt(),
        ]);
    }

    public function update(Request $request, AdminBroadcast $broadcast): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $this->fillFromRequest($broadcast, $request);
        $broadcast->save();

        if ($request->input('delivery_type') === 'send_now') {
            $this->markSendingAndDispatch($broadcast);

            return redirect()
                ->route('admin.broadcasts.index')
                ->with('success', 'Broadcast dispatch started successfully.');
        }

        return redirect()
            ->route('admin.broadcasts.edit', $broadcast)
            ->with('success', $this->successMessage($request));
    }

    public function sendNow(Request $request, AdminBroadcast $broadcast): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        if (in_array($broadcast->status, ['sending', 'sent'], true)) {
            return back()->with('error', 'Broadcast is already sending or sent.');
        }

        $this->markSendingAndDispatch($broadcast);

        return back()->with('success', 'Broadcast dispatch started successfully.');
    }

    public function schedule(Request $request, AdminBroadcast $broadcast): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $this->fillFromRequest($broadcast, $request, true);
        $broadcast->status = 'scheduled';
        $broadcast->next_run_at = $broadcast->computeNextRunAt();
        $broadcast->save();

        return redirect()
            ->route('admin.broadcasts.edit', $broadcast)
            ->with('success', 'Broadcast scheduled successfully.');
    }

    public function cancel(Request $request, AdminBroadcast $broadcast): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $broadcast->status = 'cancelled';
        $broadcast->next_run_at = null;
        $broadcast->save();

        return back()->with('success', 'Broadcast cancelled successfully.');
    }

    private function fillFromRequest(AdminBroadcast $broadcast, Request $request, bool $forceSchedule = false): void
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string'],
            'image' => ['nullable', 'file', 'image', 'max:10240'],
            'delivery_type' => ['required', 'string', 'in:send_now,schedule_once,recurring_daily,recurring_weekly,recurring_monthly,draft'],
            'schedule_once_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'daily_time' => ['nullable', 'date_format:H:i'],
            'weekly_day' => ['nullable', 'integer', 'between:0,6'],
            'weekly_time' => ['nullable', 'date_format:H:i'],
            'monthly_day' => ['nullable', 'integer', 'between:1,28'],
            'monthly_time' => ['nullable', 'date_format:H:i'],
        ]);

        $deliveryType = $forceSchedule ? $validated['delivery_type'] : ($validated['delivery_type'] ?? 'draft');

        $broadcast->title = $validated['title'] ?? null;
        $broadcast->message = $validated['message'];

        if ($request->hasFile('image')) {
            $uploadRequest = Request::create('/', 'POST', [], [], [
                'file' => $request->file('image'),
            ]);
            $uploadRequest->setUserResolver($request->getUserResolver());

            $uploadResponse = app(ApiFileController::class)->upload($uploadRequest);
            $uploadData = $uploadResponse->getData(true);
            $broadcast->image_file_id = data_get($uploadData, 'data.id');
        }

        $broadcast->send_at = null;
        $broadcast->time_of_day = null;
        $broadcast->day_of_week = null;
        $broadcast->day_of_month = null;
        $broadcast->recurrence = 'none';

        if ($deliveryType === 'schedule_once') {
            if (empty($validated['schedule_once_at'])) {
                abort(422, 'One-time schedule date and time is required.');
            }
            $broadcast->recurrence = 'none';
            $broadcast->send_at = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $validated['schedule_once_at'], 'Asia/Kolkata')->utc();
        }

        if ($deliveryType === 'recurring_daily') {
            if (empty($validated['daily_time'])) {
                abort(422, 'Daily time is required.');
            }
            $broadcast->recurrence = 'daily';
            $broadcast->time_of_day = $validated['daily_time'] . ':00';
        }

        if ($deliveryType === 'recurring_weekly') {
            if ($validated['weekly_day'] === null || empty($validated['weekly_time'])) {
                abort(422, 'Weekly day and time are required.');
            }
            $broadcast->recurrence = 'weekly';
            $broadcast->time_of_day = $validated['weekly_time'] . ':00';
            $broadcast->day_of_week = (int) $validated['weekly_day'];
        }

        if ($deliveryType === 'recurring_monthly') {
            if ($validated['monthly_day'] === null || empty($validated['monthly_time'])) {
                abort(422, 'Monthly day and time are required.');
            }
            $broadcast->recurrence = 'monthly';
            $broadcast->time_of_day = $validated['monthly_time'] . ':00';
            $broadcast->day_of_month = (int) $validated['monthly_day'];
        }

        $broadcast->normalizeScheduleInputs();

        if ($deliveryType === 'draft') {
            $broadcast->status = 'draft';
            $broadcast->next_run_at = null;

            return;
        }

        if ($deliveryType === 'send_now') {
            $broadcast->status = 'draft';
            $broadcast->next_run_at = null;

            return;
        }

        if ($forceSchedule || str_starts_with($deliveryType, 'recurring_') || $deliveryType === 'schedule_once') {
            $broadcast->status = 'scheduled';
            $broadcast->next_run_at = $broadcast->computeNextRunAt();
        }
    }


    private function markSendingAndDispatch(AdminBroadcast $broadcast): void
    {
        try {
            DB::transaction(function () use ($broadcast): void {
            $fresh = AdminBroadcast::query()->lockForUpdate()->findOrFail($broadcast->id);

            if (in_array($fresh->status, ['sending', 'sent'], true)) {
                return;
            }

            $fresh->status = 'sending';
            $fresh->last_sent_at = now();
            $fresh->next_run_at = null;
            $fresh->save();

            $queueConnection = (string) config('queue.default');

            if ($queueConnection === 'sync') {
                (new SendAdminBroadcastJob((string) $fresh->id))->handle();

                return;
            }

            SendAdminBroadcastJob::dispatch((string) $fresh->id);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to start broadcast dispatch.', [
                'broadcast_id' => (string) $broadcast->id,
                'error' => $e->getMessage(),
            ]);

            $broadcast->status = $broadcast->isRecurring() ? 'scheduled' : 'draft';
            $broadcast->next_run_at = $broadcast->isRecurring() ? $broadcast->computeNextRunAt() : null;
            $broadcast->save();

            throw $e;
        }
    }

    private function successMessage(Request $request): string
    {
        return match ($request->input('delivery_type')) {
            'send_now' => 'Broadcast dispatch started successfully.',
            'schedule_once', 'recurring_daily', 'recurring_weekly', 'recurring_monthly' => 'Broadcast scheduled successfully.',
            default => 'Broadcast draft saved successfully.',
        };
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        if (! AdminAccess::isGlobalAdmin($request->user('admin'))) {
            abort(403);
        }
    }
}
