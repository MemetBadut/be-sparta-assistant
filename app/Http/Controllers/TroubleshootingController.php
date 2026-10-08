<?php

namespace App\Http\Controllers;

use App\Http\Requests\Troubleshooting\CreateTroubleshootingRequest;
use App\Http\Requests\Troubleshooting\FeedbackRequest;
use App\Http\Resources\TroubleshootingResultResource;
use App\Models\TroubleshootingResult;
use App\Services\Troubleshooting\CategoryClassifier;
use App\Services\TroubleshootingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TroubleshootingController extends Controller
{
    public function store(
        CreateTroubleshootingRequest $request,
        TroubleshootingService $service,
        CategoryClassifier $classifier,
    ): TroubleshootingResultResource|JsonResponse {
        $description = $request->string('description')->toString();
        $category = $request->string('category')->toString();

        if ($category === '') {
            $classification = $classifier->classify($description);
            if ($classification['category'] === null) {
                return response()->json(['data' => [
                    'status' => 'needs_category',
                    'issue_summary' => $description,
                    'candidates' => $classification['candidates'],
                ]]);
            }
            $category = $classification['category'];
        }

        $result = $service->create($request->user(), $category, $description);

        return response()->json([
            'data' => (new TroubleshootingResultResource($result))->resolve($request),
        ], 201);
    }

    public function show(Request $request, TroubleshootingResult $troubleshooting): TroubleshootingResultResource
    {
        abort_unless($troubleshooting->user_id === $request->user()->id, 404);

        return new TroubleshootingResultResource($troubleshooting);
    }

    public function feedback(FeedbackRequest $request, TroubleshootingResult $troubleshooting): JsonResponse
    {
        abort_unless($troubleshooting->user_id === $request->user()->id, 404);
        $troubleshooting->update(['helpful' => $request->boolean('helpful')]);

        return response()->json(['message' => 'Feedback saved.']);
    }
}
