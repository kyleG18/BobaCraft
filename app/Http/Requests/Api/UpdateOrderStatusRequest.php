<?php

namespace App\Http\Requests\Api;

use App\Models\Order;

class UpdateOrderStatusRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . implode(',', Order::VALID_STATUSES)],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The order status is required.',
            'status.in' => 'The status must be one of: ' . implode(', ', Order::VALID_STATUSES) . '.',
        ];
    }
}
