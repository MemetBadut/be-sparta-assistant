<?php

namespace App\Http\Controllers;

use App\Http\Requests\Troubleshooting\CreateTroubleshootingRequest;
use App\Http\Requests\Troubleshooting\FeedbackRequest;
use App\Http\Resources\TroubleshootingResultResource;
use App\Models\TroubleshootingResult;
use App\Services\TroubleshootingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TroubleshootingController extends Controller
{
    public function store(CreateTroubleshootingRequest $request, TroubleshootingService $service): TroubleshootingResultResource
    {
        return new TroubleshootingResultResource($service->create($request->user(), $request->string('category')->toString(), $request->string('description')->toString()));
    }

    public function feedback(FeedbackRequest $request, TroubleshootingResult $troubleshooting): JsonResponse
    {
        abort_unless($troubleshooting->user_id === $request->user()->id, 404);
        $troubleshooting->update(['helpful' => $request->boolean('helpful')]);

        return response()->json(['message' => 'Feedback saved.']);
    }
}
