<?php

namespace App\Http\Requests;

use App\Services\OKApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiRequest extends FormRequest
{
    public const REGISTER_JOB = 'reg';
    public const GET_JOB_INFO = 'get';

    public string $type = "";


    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $input = $this->input();

        if(!isset($input['job'])) {
            return ['job' => 'required'];
        }

        $job = $input['job'];
        $this->type = $job;

        if($job === 'reg') {

            if(!isset($input['action'])) {
                return [
                    'action' => 'required'
                ];
            }

            if(!isset(OKApi::validationRules()[$input['action']])) {
                return ['action' => [
                    'required',
                    Rule::in(array_keys(OKApi::ACTIONS))
                ]];
            }

            return OKApi::validationRules()[$input['action']];
        }


        return [
            'job' => 'required', // reg/get
            'command' => ['required|string'],
            'body' => ['array']
        ];
    }

    public function messages()
    {
        return [
            'type.required' => "The Type must by onу of: " . implode(", ", OKApi::TYPES),
        ];
    }
}
