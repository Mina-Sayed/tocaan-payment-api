<?php

namespace App\Http\Requests\Payments;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'method' => ['required', 'string', Rule::in(array_keys(config('payments.methods', [])))],
            'simulate_outcome' => ['nullable', 'string', Rule::in([
                Payment::STATUS_SUCCESSFUL,
                Payment::STATUS_FAILED,
            ])],
        ];
    }
}
