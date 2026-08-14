<?php
namespace App\Controllers\Api;

use App\ApiErrorRegistry;
use Core\Controller;

/**
 * API metadata for integrators (no auth required).
 */
class MetaController extends Controller
{
    public function errorCodes(): void
    {
        $this->apiSuccess([
            'version' => 1,
            'codes' => ApiErrorRegistry::all(),
        ]);
    }
}
