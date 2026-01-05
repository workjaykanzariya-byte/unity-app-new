@extends('admin.layouts.app')

@section('content')
    <div style="display: grid; gap: 18px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <h2 style="margin: 0 0 6px; font-size: 22px;">Welcome back</h2>
                <div style="color: #94a3b8; font-size: 14px;">Stay on top of admin activity with a quick glance.</div>
            </div>
            <div style="padding: 10px 14px; background: #0ea5e91a; color: #7dd3fc; border: 1px solid #0ea5e933; border-radius: 12px;">
                Secure Session Active
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div style="background:#0f172a; border:1px solid #1f2937; padding:16px; border-radius:14px;">
                <div style="color:#94a3b8; font-size:13px;">Signed in as</div>
                <div style="font-size:17px; margin-top:6px; font-weight:700;">{{ $adminUser?->email }}</div>
            </div>
            <div style="background:#0f172a; border:1px solid #1f2937; padding:16px; border-radius:14px;">
                <div style="color:#94a3b8; font-size:13px;">Role badge</div>
                <div style="font-size:17px; margin-top:6px; font-weight:700;">Authorized Admin</div>
            </div>
            <div style="background:#0f172a; border:1px solid #1f2937; padding:16px; border-radius:14px;">
                <div style="color:#94a3b8; font-size:13px;">OTP login</div>
                <div style="font-size:17px; margin-top:6px; font-weight:700;">Session secured</div>
            </div>
        </div>
    </div>
@endsection
