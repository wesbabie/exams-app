<?php

namespace App\Http\Controllers\Intern;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    /** An applicant can open only their own uploaded files. */
    public function show(Request $request, string $kind): BinaryFileResponse
    {
        abort_unless(in_array($kind, ['passport', 'credential'], true), 404);

        $path = $request->user('applicant')->{$kind.'_path'};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }
}
