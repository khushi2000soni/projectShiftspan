<?php

namespace App\Http\Requests\SubAdminDetail;

use App\Rules\NoMultipleSpacesRule;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        abort_if(Gate::denies('sub_admin_detail_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];
        if($this->has('sub_admin_id')){
            $rules['sub_admin_id'] = ['required', 'exists:users,uuid,deleted_at,NULL'];
        }

        if($this->has('location_id')){
            $rules['location_id'] = ['required', 'exists:locations,uuid,deleted_at,NULL'];
        }

        // $rules['name'] = ['required', 'regex:/^[a-zA-Z\s]+$/', new NoMultipleSpacesRule, 'max:255', 'unique:client_details,name,'. $this->subAdminDetail.',uuid,deleted_at,NULL'];
        $rules['name'] = ['required', 'regex:/^[a-zA-Z\s\-\'\.\,\(\)\[\]\{\}\<\>\*\&\^\%\$\#\@\!\~\`\|\+\=\;\:\?\"\\\©]+$/u', new NoMultipleSpacesRule, 'max:255', 'unique:client_details,name,'. $this->subAdminDetail.',uuid,deleted_at,NULL'];

        $rules['address'] = ['required', new NoMultipleSpacesRule];

        $rules['shop_description'] = ['required', 'string', 'max:'.config('constant.shop_description_length')];
        $rules['travel_info'] = ['required', 'string', 'max:'.config('constant.travel_info_length')];

        $rules['building_image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg'];

        return $rules;
    }

    public function attributes()
    {
        return [
            'sub_admin_id' => 'Client name'
        ];
    }
}
