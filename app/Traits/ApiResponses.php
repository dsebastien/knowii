<?php

namespace App\Traits;

use App\KnowiiApiResponse;
use App\KnowiiApiResponseCategory;
use App\KnowiiApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

/**
 * Trait for handling API responses and ensuring API design consistency.
 */
trait ApiResponses
{
  /**
   * Newly created entity
   * @param JsonResource $data
   * @param string|null $message
   * @param array|null $metadata
   * @return JsonResponse
   */
  final public static function created(JsonResource $data, ?string $message = "Success", ?array $metadata = null): JsonResponse
  {
    return self::success($message, $metadata, $data, Response::HTTP_CREATED);
  }

  /**
   * Success
   * @param string $message
   * @param array|null $metadata
   * @param array|JsonResource|null $data
   * @param int|null $statusCode
   * @return JsonResponse
   */
  final public static function success(string $message = "Success", ?array $metadata = null, array|JsonResource|null $data = null, ?int $statusCode = Response::HTTP_OK): JsonResponse
  {
    $knowiiResponse = new KnowiiApiResponse(KnowiiApiResponseCategory::Business, KnowiiApiResponseType::Success, $message, $metadata, $data, null);

    return response()->json($knowiiResponse->jsonSerialize(), $statusCode);
  }

  /**
   * Return an authentication issue response.
   *
   * @param string $message
   * @param array|null $metadata
   * @param array|null $errorDetails
   * @return JsonResponse
   */
  final public static function authenticationIssue(string $message, ?array $metadata = null, ?array $errorDetails = []): JsonResponse
  {
    $knowiiResponse = new KnowiiApiResponse(KnowiiApiResponseCategory::Security, KnowiiApiResponseType::AuthenticationIssue, $message, $metadata, null, $errorDetails);
    return response()->json($knowiiResponse->jsonSerialize(), Response::HTTP_UNAUTHORIZED);
  }

  /**
   * Return an authorization issue response.
   *
   * @param string $message
   * @param array|null $metadata
   * @param array|null $errorDetails
   * @return JsonResponse
   */
  final public static function authorizationIssue(string $message, ?array $metadata = null, ?array $errorDetails = []): JsonResponse
  {
    $knowiiResponse = new KnowiiApiResponse(KnowiiApiResponseCategory::Security, KnowiiApiResponseType::AuthorizationIssue, $message, $metadata, null, $errorDetails);
    return response()->json($knowiiResponse->jsonSerialize(), Response::HTTP_FORBIDDEN);
  }

  /**
   * Return a not found issue response.
   *
   * @param ?string $message
   * @return JsonResponse
   */
  final public static function notFoundIssue(?string $message = "Not found"): JsonResponse
  {
    $knowiiResponse = new KnowiiApiResponse(KnowiiApiResponseCategory::Technical, KnowiiApiResponseType::NotFound, $message, null, null, null);
    return response()->json($knowiiResponse->jsonSerialize(), Response::HTTP_NOT_FOUND);
  }

  /**
   * Return a validation issue response.
   * Reference: https://github.com/NationalBankBelgium/REST-API-Design-Guide/wiki/HTTP-Status-Codes-Client-Error-%284xx%29
   *
   * @param string $message
   * @param array $errorDetails
   * @param array|null $metadata
 * @return JsonResponse
   */
  final public static function validationIssue(string $message, array $errorDetails, ?array $metadata = null): JsonResponse
  {
    $knowiiResponse = new KnowiiApiResponse(KnowiiApiResponseCategory::Business, KnowiiApiResponseType::ValidationIssue, $message, $metadata, null, $errorDetails);
    return response()->json($knowiiResponse->jsonSerialize(), Response::HTTP_BAD_REQUEST);
  }

  /**
   * Return a business issue response.
   * Reference: https://github.com/NationalBankBelgium/REST-API-Design-Guide/wiki/HTTP-Status-Codes-Client-Error-%284xx%29
   *
   * @param string $message
   * @param array $errorDetails
   * @param array|null $metadata
   * @return JsonResponse
   */
  final public static function businessIssue(string $message, array $errorDetails, ?array $metadata = null): JsonResponse
  {
    $knowiiResponse = new KnowiiApiResponse(KnowiiApiResponseCategory::Business, KnowiiApiResponseType::BusinessIssue, $message, $metadata, null, $errorDetails);
    return response()->json($knowiiResponse->jsonSerialize(), Response::HTTP_UNPROCESSABLE_ENTITY);
  }

  /**
   * Return a technical issue response.
   * Reference: https://github.com/NationalBankBelgium/REST-API-Design-Guide/wiki/HTTP-Status-Codes-Server-Error-%285xx%29
   *
   * @param string $message
   * @param array $errorDetails
   * @param array|null $metadata
   * @return JsonResponse
   */
  final public static function technicalIssue(string $message, array $errorDetails, ?array $metadata = null): JsonResponse
  {
    $knowiiResponse = new KnowiiApiResponse(KnowiiApiResponseCategory::Technical, KnowiiApiResponseType::InternalError, $message, $metadata, null, $errorDetails);
    return response()->json($knowiiResponse->jsonSerialize(), Response::HTTP_INTERNAL_SERVER_ERROR);
  }

}