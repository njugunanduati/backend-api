<?php

namespace App\Traits;

use Facade\FlareClient\Enums\MessageLevels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Logging\AppLogger;

/**
 * API Responses Trait
 */
trait ApiResponses
{
    /**
     * Wrapper to return successfull responses
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    private function successResponse($data, $code)
    {
        return response()->json([
            'data' => $data,
            'status' => $code,
        ], $code);
    }

    /**
     * Wrapper to return successfull XML responses
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    private function successXMLResponse($data, $code)
    {
        return response($data, $code, ['Content-Type' => 'application/xml']);
    }
    
    /**
     * Wrapper to return error XML responses
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    private function errorXMLResponse($message, $code)
    {
        return response($message, $code, ['Content-Type' => 'application/xml']);
    }

    /**
     * Wrapper to return error responses
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    protected function errorResponse($message, $code)
    {
        if (env('APP_ENV') == 'production' || env('APP_ENV') == 'staging' || env('APP_ENV') == 'development' || env('APP_ENV') == 'uat') {
            $logger = new AppLogger();
            $logger->logRequest('error', $message);
        }
        return response()->json([
            'error' => $message,
            'status' => $code,
        ], $code);
    }

    /**
     * Wrapper to return collection responses
     * @param array $collection
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    protected function showAll(Collection $collection, $code = 200)
    {
        /*
        return $this->successResponse([
        'data' => $collection,
        ], $code);
         */

        return $this->successResponse($collection, $code);
    }

    /**
     * Wrapper to return single model responses
     * @param array $instance
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    protected function showOne(Model $instance, $code = 200)
    {
        /*
        return $this->successResponse([
        'data' => $instance,
        ], $code);
         */

        return $this->successResponse($instance, $code);
    }

    /**
     * Wrapper to return successful responses that are not a collection or an instanace
     * @param array $message
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    protected function showMessage($message, $code = 200)
    {
        /*
        return $this->successResponse([
        'data' => $message,
        ], $code);
         */

        return $this->successResponse($message, $code);
    }

    /**
     * Wrapper to return successful single line responses
     * @param array $message
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    protected function singleMessage($message, $code = 200)
    {
        return $this->successResponse([
            'message' => $message,
        ], $code);
    }

    /**
     * Wrapper to return empty array responses
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    protected function emptyArrayMessage($code = 200)
    {
        return $this->successResponse([], $code);
    }
}
