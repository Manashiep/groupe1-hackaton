<?php

namespace App\Http\Requests;

use App\Enums\AssociationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * « Je m'en occupe » : date prévue, et éventuellement au nom de l'association que l'on dirige.
 */
class TakeChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'planned_at' => ['required', 'date', 'after_or_equal:today'],
            'association_id' => [
                'nullable',
                Rule::exists('associations', 'id')
                    ->where('leader_id', $this->user()->id)
                    ->where('status', AssociationStatus::Active->value),
            ],
            'taker_note' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'planned_at.required' => 'Choisissez le jour et l\'heure où vous passerez.',
            'planned_at.after_or_equal' => 'Choisissez une date à partir d\'aujourd\'hui.',
            'association_id.exists' => 'Vous ne pouvez agir qu\'au nom d\'une association que vous dirigez.',
        ];
    }
}
