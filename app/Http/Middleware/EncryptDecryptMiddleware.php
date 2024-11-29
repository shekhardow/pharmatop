<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EncryptDecryptMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Decrypt incoming request data if it exists
        $this->decryptRequestData($request);

        // Proceed with the request
        $response = $next($request);

        // Encrypt outgoing response data
        $this->encryptResponseData($response);

        return $response;
    }

    /**
     * Decrypt the incoming request payload.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function decryptRequestData(Request $request)
    {
        $encryptedData = $request->getContent();

        if ($encryptedData) {
            try {
                // Decrypt the data
                $decryptedData = decryptData($encryptedData); // Call your decryptData function here
                // Replace the request data with decrypted data
                $request->replace($decryptedData);
            } catch (\Exception $e) {
                // Return custom error message
                return response()->json([
                    'result' => -1,
                    'msg' => 'Decryption failed. ' . $e->getMessage()
                ], 400);  // 400 Bad Request
            }
        }
    }

    /**
     * Encrypt the outgoing response data.
     *
     * @param \Illuminate\Http\Response $response
     * @return void
     */
    protected function encryptResponseData($response)
    {
        $data = $response->getData(true);

        if ($data) {
            $encryptedData = encryptData($data); // Call your encryptData function here
            $response->setData($encryptedData);
        }
    }
}
