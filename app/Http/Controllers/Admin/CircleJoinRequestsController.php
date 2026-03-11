<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Circle;
use App\Models\CircleJoinRequest;
use App\Services\Circles\CircleJoinRequestService;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CircleJoinRequestsController extends Controller
{
    public function __construct(private readonly CircleJoinRequestService $service)
    {
    }

    public function index(Request $request): View
    {
        $admin = Auth::guard('admin')->user();
        $query = CircleJoinRequest::query()->with(['user', 'circle', 'cdApprovedBy', 'idApprovedBy']);
        $query->visibleToAdminUser($admin);

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $query->whereHas('user', fn ($q) => $q->where('display_name', 'ILIKE', $like)
                ->orWhere('email', 'ILIKE', $like)
                ->orWhere('phone', 'ILIKE', $like)
                ->orWhere('company_name', 'ILIKE', $like)
                ->orWhere('city', 'ILIKE', $like));
        }

        $query->when($request->query('circle_id'), fn ($q, $v) => $q->where('circle_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('date_from'), fn ($q, $v) => $q->whereDate('requested_at', '>=', $v))
            ->when($request->query('date_to'), fn ($q, $v) => $q->whereDate('requested_at', '<=', $v));

        return view('admin.circle_join_requests.index', [
            'requests' => $query->latest('created_at')->paginate(25)->appends($request->query()),
            'circles' => Circle::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'circle_id', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function show(string $id): View
    {
        $admin = Auth::guard('admin')->user();
        $record = CircleJoinRequest::query()->with(['user', 'circle', 'cdApprovedBy', 'cdRejectedBy', 'idApprovedBy', 'idRejectedBy'])->findOrFail($id);
        abort_unless(AdminAccess::isSuper($admin) || in_array($record->circle_id, AdminAccess::allowedCircleIds($admin), true), 403);

        return view('admin.circle_join_requests.show', ['record' => $record]);
    }

    public function approveCd(string $id): RedirectResponse
    {
        return $this->runAction($id, fn ($record, $actor) => $this->service->approveByCd($record, $actor));
    }

    public function rejectCd(Request $request, string $id): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return $this->runAction($id, fn ($record, $actor) => $this->service->rejectByCd($record, $actor, (string) $request->input('reason')));
    }

    public function approveId(string $id): RedirectResponse
    {
        return $this->runAction($id, fn ($record, $actor) => $this->service->approveById($record, $actor));
    }

    public function rejectId(Request $request, string $id): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return $this->runAction($id, fn ($record, $actor) => $this->service->rejectById($record, $actor, (string) $request->input('reason')));
    }

    private function runAction(string $id, callable $callback): RedirectResponse
    {
        $actor = AdminAccess::resolveAppUser(Auth::guard('admin')->user());
        abort_unless($actor !== null, 403);

        try {
            $record = CircleJoinRequest::query()->with('circle')->findOrFail($id);
            $callback($record, $actor);

            return back()->with('success', 'Action completed successfully.');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }
    }
}
