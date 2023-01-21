<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class WebCrawlerRequest extends FormRequest 
{
    public function rules()
    {
        return [
            'url' => 'required|url',
            'numPages' => 'required|int|min:4|max:6',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
    
        $response = response()->json([
            'message' => 'Invalid data',
            'details' => $errors->messages(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    
        throw new HttpResponseException($response);
    }
}