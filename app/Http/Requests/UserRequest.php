<?php

namespace App\Http\Requests;
use App\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\MessageBag;
class UserRequest extends FormRequest
{
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
        $rule = Rule::unique('users');
        
        $passRule = ['required','string','min:6','confirmed'];
        
        if($this->user)
        {
            $rule->ignore($this->user->id);
            $data = $this->all();

            if(empty($data['password']))
            {
                $this->request->remove('password');
                $this->request->remove('password_confirmation');
                $passRule = [];
            }

        }

        return [
                'name' => 'required|string|max:255',
                'email' => ['required','string','email','max:255',$rule],
                'password' => $passRule,
        ];

    }
}
