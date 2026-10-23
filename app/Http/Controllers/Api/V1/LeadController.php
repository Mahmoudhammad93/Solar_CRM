<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeadApiResource;
use App\Models\Lead;
use App\Support\RecordScoping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $user = $request->user();
        if (! $user?->hasPermission('leads.view')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $token = $request->attributes->get('api_token');
        $abilities = $token?->abilities ?? [];
        // Empty abilities must not grant access (fail closed).
        if (! in_array('*', $abilities, true) && ! in_array('leads:read', $abilities, true)) {
            return response()->json(['message' => 'Token lacks leads:read ability.'], 403);
        }

        $query = Lead::query()->with('assignee');
        RecordScoping::applyAssignedScope($query, $user, 'assigned_to');

        if ($request->filled('stage')) {
            $query->where('stage', $request->string('stage')->toString());
        }
        if ($request->filled('source')) {
            $query->where('source', $request->string('source')->toString());
        }

        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        return LeadApiResource::collection($query->orderByDesc('id')->paginate($perPage));
    }
}
