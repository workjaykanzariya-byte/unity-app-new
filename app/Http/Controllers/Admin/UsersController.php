<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\City;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        [$query, $filters, $perPage] = $this->buildUserQuery($request);

        $users = $query->paginate($perPage)->withQueryString();

        $membershipStatuses = User::query()
            ->whereNotNull('membership_status')
            ->distinct()
            ->pluck('membership_status')
            ->sort()
            ->values();

        $cities = City::query()->orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'membershipStatuses' => $membershipStatuses,
            'cities' => $cities,
            'filters' => $filters,
        ]);
    }

    public function edit(string $userId): View
    {
        $user = User::query()->with(['city', 'roles'])->findOrFail($userId);
        $cities = City::query()->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->get();
        $membershipStatuses = $this->membershipStatuses();

        return view('admin.users.edit', [
            'user' => $user,
            'cities' => $cities,
            'roles' => $roles,
            'membershipStatuses' => $membershipStatuses,
            'userRoleIds' => $user->roles->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, string $userId)
    {
        $user = User::query()->findOrFail($userId);

        $membershipStatuses = $this->membershipStatuses();
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'designation' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'turnover_range' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            'dob' => ['nullable', 'date'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'experience_summary' => ['nullable', 'string'],
            'short_bio' => ['nullable', 'string'],
            'long_bio_html' => ['nullable', 'string'],
            'public_profile_slug' => ['nullable', 'string', 'max:80', 'unique:users,public_profile_slug,' . $user->id],
            'membership_status' => ['required', 'in:' . implode(',', $membershipStatuses)],
            'membership_expiry' => ['nullable', 'date'],
            'coins_balance' => ['required', 'integer', 'min:0'],
            'influencer_stars' => ['nullable', 'integer', 'min:0'],
            'is_sponsored_member' => ['boolean'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'city' => ['nullable', 'string', 'max:150'],
            'introduced_by' => ['nullable', 'exists:users,id'],
            'members_introduced_count' => ['nullable', 'integer', 'min:0'],
            'profile_photo_file_id' => ['nullable', 'uuid'],
            'cover_photo_file_id' => ['nullable', 'uuid'],
            'industry_tags' => ['nullable', 'string', 'max:10000'],
            'target_regions' => ['nullable', 'string', 'max:10000'],
            'target_business_categories' => ['nullable', 'string', 'max:10000'],
            'hobbies_interests' => ['nullable', 'string', 'max:10000'],
            'leadership_roles' => ['nullable', 'string', 'max:10000'],
            'special_recognitions' => ['nullable', 'string', 'max:10000'],
            'skills' => ['nullable', 'string', 'max:10000'],
            'interests' => ['nullable', 'string', 'max:10000'],
            'social_links' => ['nullable', 'string', 'max:10000'],
            'role_ids' => ['array'],
            'role_ids.*' => ['exists:roles,id'],
        ]);

        $csvFields = [
            'industry_tags',
            'target_regions',
            'target_business_categories',
            'hobbies_interests',
            'leadership_roles',
            'special_recognitions',
            'skills',
            'interests',
        ];

        foreach ($csvFields as $field) {
            $validated[$field] = $this->csvToArray($request->input($field, ''));
        }

        $validated['social_links'] = $this->parseSocialLinks($request->input('social_links'));

        $booleanFields = ['is_sponsored_member'];
        foreach ($booleanFields as $field) {
            $validated[$field] = $request->boolean($field);
        }

        $updatable = Arr::except($validated, ['role_ids', 'profile_photo_file_id', 'cover_photo_file_id']);

        DB::transaction(function () use ($user, $updatable, $validated, $request) {
            $user->fill($updatable);

            if ($request->filled('profile_photo_file_id')) {
                $user->profile_photo_file_id = $request->input('profile_photo_file_id');
            }

            if ($request->filled('cover_photo_file_id')) {
                $user->cover_photo_file_id = $request->input('cover_photo_file_id');
            }

            $user->save();

            if ($request->filled('role_ids')) {
                $adminUser = AdminUser::find($user->id);

                if (! $adminUser) {
                    $adminUser = AdminUser::create([
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->display_name ?? $user->first_name,
                    ]);
                }

                $adminUser->roles()->sync($validated['role_ids']);
            }
        });

        return redirect()->route('admin.users.edit', $user->id)
            ->with('status', 'User updated successfully.');
    }

    public function removeRole(Request $request, string $userId, string $roleId): RedirectResponse
    {
        $user = User::query()->findOrFail($userId);
        $adminUser = AdminUser::find($user->id);

        if (! $adminUser) {
            return back()->withErrors(['roles' => 'Admin user record not found for this user.']);
        }

        $adminUser->roles()->detach($roleId);

        $remainingRoles = $adminUser->roles()->count();

        if ($remainingRoles === 0) {
            $adminUser->delete();
        }

        return back()->with('success', 'Role removed successfully.');
    }

    public function importForm(): View
    {
        return view('admin.users.import');
    }

    public function import(Request $request): View
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            return view('admin.users.import', ['error' => 'Unable to read uploaded file.']);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            return view('admin.users.import', ['error' => 'CSV header is missing.']);
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);
        $allowed = [
            'id', 'email', 'first_name', 'last_name', 'display_name', 'phone', 'company_name', 'membership_status', 'city', 'coins_balance',
        ];

        $membershipStatuses = $this->membershipStatuses();
        $results = [
            'created' => 0,
            'updated' => 0,
            'failed' => [],
        ];

        while (($row = fgetcsv($handle)) !== false) {
            $data = [];
            foreach ($header as $index => $column) {
                if (! in_array($column, $allowed, true)) {
                    continue;
                }
                $data[$column] = trim($row[$index] ?? '');
            }

            if (empty($data['email'])) {
                $results['failed'][] = ['row' => $data, 'reason' => 'Email is required'];
                continue;
            }

            $membership = $data['membership_status'] ?? null;
            if ($membership && ! in_array($membership, $membershipStatuses, true)) {
                $membership = null;
            }

            try {
                $user = User::query()->where('email', $data['email'])->first();

                if ($user) {
                    $updateFields = Arr::only($data, ['first_name', 'last_name', 'display_name', 'phone', 'company_name', 'membership_status', 'city', 'coins_balance']);
                    $updateFields = array_filter($updateFields, fn ($v) => $v !== '');

                    if ($membership) {
                        $updateFields['membership_status'] = $membership;
                    }

                    if (isset($updateFields['coins_balance']) && $updateFields['coins_balance'] !== '') {
                        $updateFields['coins_balance'] = (int) $updateFields['coins_balance'];
                    }

                    $user->fill($updateFields);
                    $user->save();
                    $results['updated']++;
                } else {
                    $payload = [
                        'email' => $data['email'],
                        'first_name' => $data['first_name'] ?: 'Unknown',
                        'last_name' => $data['last_name'] ?? null,
                        'display_name' => $data['display_name'] ?: ($data['first_name'] ?? 'User'),
                        'phone' => $data['phone'] ?? null,
                        'company_name' => $data['company_name'] ?? null,
                        'membership_status' => $membership ?: 'visitor',
                        'city' => $data['city'] ?? null,
                        'coins_balance' => isset($data['coins_balance']) && $data['coins_balance'] !== '' ? (int) $data['coins_balance'] : 0,
                        'password_hash' => bcrypt(Str::random(32)),
                    ];

                    User::create($payload);
                    $results['created']++;
                }
            } catch (\Throwable $e) {
                $results['failed'][] = ['row' => $data, 'reason' => $e->getMessage()];
            }
        }

        fclose($handle);

        return view('admin.users.import', ['results' => $results]);
    }

    public function exportPdf(Request $request)
    {
        [$query] = $this->buildUserQuery($request);

        $selectedIds = array_filter(explode(',', (string) $request->input('selected_ids', '')));
        if (! empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        }

        $users = $query->get();
        $pdfContent = $this->generateSimplePdf($users);
        $fileName = 'users_export_' . now()->format('Ymd_His') . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    private function membershipStatuses(): array
    {
        return [
            'visitor',
            'premium',
            'charter',
            'suspended',
        ];
    }

    private function csvToArray(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $parts = array_map('trim', explode(',', $value));
        $parts = array_filter($parts, fn ($v) => $v !== '');

        return array_values($parts);
    }

    private function parseSocialLinks(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $parts = array_filter(array_map('trim', explode(',', $value)));

        $isKeyValue = false;
        foreach ($parts as $p) {
            if (str_contains($p, '=')) {
                $isKeyValue = true;
                break;
            }
        }

        if ($isKeyValue) {
            $obj = [];
            foreach ($parts as $p) {
                if (! str_contains($p, '=')) {
                    continue;
                }
                [$k, $v] = array_map('trim', explode('=', $p, 2));
                if ($k !== '' && $v !== '') {
                    $obj[$k] = $v;
                }
            }
            return $obj;
        }

        return array_values($parts);
    }

    private function buildUserQuery(Request $request): array
    {
        $query = User::query()
            ->select([
                'id',
                'email',
                'phone',
                'first_name',
                'last_name',
                'display_name',
                'designation',
                'company_name',
                'profile_photo_url',
                'short_bio',
                'long_bio_html',
                'business_type',
                'industry_tags',
                'turnover_range',
                'city_id',
                'membership_status',
                'membership_expiry',
                'introduced_by',
                'members_introduced_count',
                'target_regions',
                'target_business_categories',
                'hobbies_interests',
                'leadership_roles',
                'is_sponsored_member',
                'public_profile_slug',
                'special_recognitions',
                'gdpr_deleted_at',
                'anonymized_at',
                'is_gdpr_exported',
                'coins_balance',
                'influencer_stars',
                'last_login_at',
                'created_at',
                'updated_at',
                'city',
                'skills',
                'interests',
                'gender',
                'dob',
                'experience_years',
                'experience_summary',
                'profile_photo_file_id',
                'cover_photo_file_id',
                'deleted_at',
            ])
            ->with('city');

        $search = $request->input('q', $request->input('search'));
        $membership = $request->input('membership_status');
        $cityId = $request->input('city_id', $request->input('city'));
        $phone = $request->input('phone');
        $company = $request->input('company_name');
        $perPage = $request->integer('per_page') ?: 20;

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%");
            });
        }

        if ($membership && $membership !== 'all') {
            $query->where('membership_status', $membership);
        }

        if ($cityId && $cityId !== 'all') {
            $query->where('city_id', $cityId);
        }

        if ($phone) {
            $query->where('phone', 'ILIKE', "%{$phone}%");
        }

        if ($company) {
            $query->where('company_name', 'ILIKE', "%{$company}%");
        }

        $sortable = ['display_name', 'coins_balance', 'last_login_at', 'created_at'];
        $sort = $request->input('sort');
        $direction = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByDesc('last_login_at');
        }

        $perPage = in_array($perPage, [10, 20, 25, 50, 100], true) ? $perPage : 20;

        $filters = [
            'search' => $search,
            'membership_status' => $membership,
            'city_id' => $cityId,
            'phone' => $phone,
            'company_name' => $company,
            'per_page' => $perPage,
            'sort' => $sort,
            'dir' => $direction,
        ];

        return [$query, $filters, $perPage];
    }

    private function generateSimplePdf($users): string
    {
        $lines = [];
        $header = 'ID | Name | Email | Phone | Company | Membership | City | Coins | Status | Created At';
        $lines[] = $header;

        foreach ($users as $user) {
            $status = $user->deleted_at ? 'Deleted' : 'Active';
            $lines[] = sprintf(
                '%s | %s | %s | %s | %s | %s | %s | %s | %s | %s',
                substr((string) $user->id, 0, 8),
                $user->display_name ?? trim($user->first_name . ' ' . $user->last_name),
                $user->email,
                $user->phone,
                $user->company_name,
                $user->membership_status,
                $user->city?->name ?? $user->city ?? '',
                $user->coins_balance,
                $status,
                optional($user->created_at)->format('Y-m-d')
            );
        }

        $commands = [
            'BT',
            '/F1 10 Tf',
        ];

        $y = 770;
        foreach ($lines as $line) {
            $commands[] = sprintf('1 0 0 1 40 %d Tm (%s) Tj', $y, $this->escapePdfText($line));
            $y -= 14;
        }
        $commands[] = 'ET';

        $stream = implode("\n", $commands);

        $objects = [];
        $this->appendPdfObject($objects, '<< /Type /Catalog /Pages 2 0 R >>');
        $this->appendPdfObject($objects, '<< /Type /Pages /Kids [3 0 R] /Count 1 >>');
        $this->appendPdfObject($objects, '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>');
        $this->appendPdfObject($objects, '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream");
        $this->appendPdfObject($objects, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n{$object}\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefPosition}\n%%EOF";

        return $pdf;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function appendPdfObject(array &$objects, string $content): void
    {
        $objects[] = $content;
    }
}
