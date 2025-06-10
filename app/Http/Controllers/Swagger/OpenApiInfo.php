<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="HRIS Team 4 API Documentation",
 *     version="1.0.0",
 *     description="Dokumentasi API aplikasi HRIS Untuk PBL Team 4",
 *     @OA\Contact(
 *         email="your-email@example.com"
 *     )
 * )
 */
class OpenApiInfo
{
    // Tidak perlu isi apa-apa di sini, cukup untuk anotasi global.
}

/**
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Gunakan token Bearer",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="sanctum"
 * )
 */
