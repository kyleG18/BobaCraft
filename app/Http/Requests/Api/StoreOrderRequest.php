<?php

namespace App\Http\Requests\Api;

class StoreOrderRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'drink' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'in:Regular,Large'],
            'sugar_level' => ['nullable', 'string', 'in:0%,25%,50%,70%,100%'],
            'ice_level' => ['nullable', 'string', 'in:No Ice,Less Ice,Standard Ice,Extra Ice'],
            'toppings' => ['nullable', 'array'],
            'toppings.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Customer name or ticket title is required.',
            'customer_name.min' => 'Customer name must have at least 2 characters.',
            'customer_name.max' => 'Customer name cannot exceed 100 characters.',
            'size.in' => 'Size must be either Regular or Large.',
            'sugar_level.in' => 'Sugar level must be one of: 0%, 25%, 50%, 70%, 100%.',
            'ice_level.in' => 'Ice level must be one of: No Ice, Less Ice, Standard Ice, Extra Ice.',
        ];
    }
}
