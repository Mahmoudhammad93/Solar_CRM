<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, string $uuid, DocumentService $documents): StreamedResponse
    {
        $document = Document::query()->where('uuid', $uuid)->firstOrFail();

        Gate::authorize('download', $document);

        return $documents->download($document);
    }
}
