<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller;

/**
 * IndexController
 *
 * @package App\Http\Controllers
 */
class IndexController extends Controller
{
    /**
     * IndexController constructor
     */
    public function __construct()
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'response' => true
        ]);
    }
}
