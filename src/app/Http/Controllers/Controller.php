<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="MClass Course Management API",
 *     version="1.0.0",
 *     description="API documentation for MClass course management system",
 *     @OA\Contact(
 *         email="support@mclass.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="MClass API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Enter token in format: Bearer {token}"
 * )
 */
abstract class Controller
{
    //
}
