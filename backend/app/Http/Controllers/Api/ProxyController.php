<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProxyRequest;
use App\Http\Requests\UpdateProxyRequest;
use App\Http\Resources\ProxyResource;
use App\Jobs\CheckProxyStatus;
use App\Models\Proxy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProxyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Proxy::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('host', 'like', "%{$search}%");
            });
        }

        return ProxyResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreProxyRequest $request): ProxyResource
    {
        $proxy = Proxy::create($request->validated());
        CheckProxyStatus::dispatch($proxy);
        return new ProxyResource($proxy);
    }

    public function show(Proxy $proxy): ProxyResource
    {
        return new ProxyResource($proxy);
    }

    public function update(UpdateProxyRequest $request, Proxy $proxy): ProxyResource
    {
        $proxy->update($request->validated());
        return new ProxyResource($proxy->fresh());
    }

    public function destroy(Proxy $proxy): JsonResponse
    {
        $proxy->delete();
        return response()->json(['message' => 'Proxy deleted successfully']);
    }

    public function check(Proxy $proxy): JsonResponse
    {
        $proxy->update(['status' => 'checking']);
        CheckProxyStatus::dispatch($proxy);
        return response()->json([
            'message' => 'Proxy check queued',
            'proxy'   => new ProxyResource($proxy->fresh()),
        ]);
    }

    public function checkAll(): JsonResponse
    {
        $proxies = Proxy::all();
        $proxies->each(function (Proxy $proxy) {
            $proxy->update(['status' => 'checking']);
            CheckProxyStatus::dispatch($proxy);
        });
        return response()->json([
            'message' => "Check queued for {$proxies->count()} proxies",
            'count'   => $proxies->count(),
        ]);
    }
}

