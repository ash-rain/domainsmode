<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentRequest;
use App\Models\Content;
use App\Models\Domain;
use App\Policies\DomainPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function __construct(
        private DomainPolicy $policy,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->policy->viewAny($request)) {
            abort(403, 'Not allowed to list domains');
        }

        $domains = Domain::with('contents')->get();

        return response()->json($domains);
    }

    public function storeContent(StoreContentRequest $request, Domain $domain): JsonResponse
    {
        if (! $this->policy->createContent($request, $domain)) {
            abort(403, 'Not allowed to create content on this domain');
        }

        $userId = $request->header('X-User-Id');

        $exists = Content::where('domain_id', $domain->id)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Content already exists for this domain',
            ], 409);
        }

        $content = Content::create([
            'domain_id' => $domain->id,
            'user_id'   => $userId,
            'title'     => $request->validated('title'),
            'body'      => $request->validated('body'),
        ]);

        return response()->json($content, 201);
    }
}
