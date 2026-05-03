<?php

namespace App\Http\Controllers;

use App\Services\PasswordGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordGeneratorController extends Controller
{
    public function __construct(private PasswordGeneratorService $service) {}

    /**
     * Generate a new password
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'length' => 'required|integer|min:4|max:128',
            'uppercase' => 'required|boolean',
            'lowercase' => 'required|boolean',
            'numbers' => 'required|boolean',
            'symbols' => 'required|boolean',
            'exclude_ambiguous' => 'required|boolean',
        ]);

        // At least one charset must be selected
        $hasSelection = $validated['uppercase'] ||
            $validated['lowercase'] ||
            $validated['numbers'] ||
            $validated['symbols'];

        if (! $hasSelection) {
            return response()->json([
                'success' => false,
                'error' => 'At least one character set must be selected.',
            ], 422);
        }

        try {
            $result = $this->service->generate($validated);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
