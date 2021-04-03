<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EmailController
 *
 * @package App\Http\Controllers
 */
class EmailController extends Controller
{
    /**
     * Sends an email to SQS to be sent
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws ValidationException
     */
    public function send(Request $request): JsonResponse
    {
        $this->validate($request, [
            'from' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string'
        ], [
            'from.required' => 'Please enter a from address.',
            'from.email' => 'Please enter a valid from address.',
            'subject.required' => 'Please enter a subject.',
            'subject.string' => 'Please enter a valid subject.',
            'message.required' => 'Please enter a message.',
            'message.string' => 'Please enter a valid message.',
        ]);

        return response()->json([
            'success' => 'Your email has been sent.'
        ], Response::HTTP_ACCEPTED);
    }
}
