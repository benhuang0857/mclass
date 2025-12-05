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
 * @OA\Server(
 *     url="http://143.198.93.171/api",
 *     description="Production Server"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8001/api",
 *     description="Local Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Enter token in format: Bearer {token}"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT token"
 * )
 */
abstract class Controller
{
    //
}
