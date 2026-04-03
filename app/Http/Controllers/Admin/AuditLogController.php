<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::query()->with(['causer', 'subject'])->latest();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->string('log_name'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }

        $activities = $query->paginate(30)->withQueryString();

        return Inertia::render('Admin/Audit/Index', [
            'activities' => $activities,
            'filters' => [
                'log_name' => $request->string('log_name')->toString(),
                'event' => $request->string('event')->toString(),
            ],
        ]);
    }
}
