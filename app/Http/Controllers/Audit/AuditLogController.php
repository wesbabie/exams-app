<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\User;
use App\Support\Audit;
use App\Support\AuditCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    private const EXPORT_LIMIT = 10000;

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('audit.index', [
            'logs' => AuditLog::filter($filters)->orderByDesc('id')->paginate(50)->withQueryString(),
            'filters' => $filters,
            'categories' => AuditCatalog::CATEGORIES,
            'people' => User::orderBy('name')->get(['id', 'name']),
            'exams' => Exam::orderByDesc('id')->get(['id', 'title']),
            'last24' => AuditLog::where('created_at', '>=', now()->subDay())->count(),
            'sensitive7' => AuditLog::whereIn('action', AuditCatalog::SENSITIVE)->where('created_at', '>=', now()->subDays(7))->count(),
            'failed7' => AuditLog::where('action', 'auth.failed')->where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);

        Audit::record('audit.export', 'accounts', 'Exported the audit log', ['meta' => ['filters' => array_filter($filters)]]);

        $name = 'audit-log-'.now()->format('Y-m-d-Hi').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads the characters correctly
            fputcsv($out, ['When', 'Person', 'Role', 'Area', 'What happened', 'About', 'Exam', 'Reason', 'Details', 'IP address']);

            AuditLog::filter($filters)->orderByDesc('id')->limit(self::EXPORT_LIMIT)->lazyById(500)->each(function (AuditLog $l) use ($out) {
                fputcsv($out, [
                    $l->created_at->format('Y-m-d H:i:s'),
                    $l->actor_name ?? 'System or signed out',
                    $l->actor_role,
                    $l->categoryLabel(),
                    $l->summary,
                    $l->subject_label,
                    $l->exam_id,
                    $l->reason,
                    $l->meta ? json_encode($l->meta, JSON_UNESCAPED_UNICODE) : '',
                    $l->ip_address,
                ]);
            });

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'in:'.implode(',', array_keys(AuditCatalog::CATEGORIES))],
            'actor' => ['nullable', 'integer'],
            'exam' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'sensitive' => ['nullable', 'boolean'],
        ]);

        return $data;
    }
}
