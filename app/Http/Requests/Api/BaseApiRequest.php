<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

abstract class BaseApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle a failed validation attempt and format into predictable JSON.
     */
    protected function failedValidation(Validator $validator): void
    {
        $requestId = $this->attributes->get('request_id') ?: $this->header('X-Request-ID') ?: 'req_val';

        throw new HttpResponseException(
            new JsonResponse([
                'success' => false,
                'message' => 'The given request payload failed input validation rules.',
                'errors' => $validator->errors()->toArray(),
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'The given request payload failed input validation rules.',
                    'details' => $validator->errors()->toArray(),
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $requestId,
                ],
            ], 422)
        );
    }
}
