<?php

namespace App\Http\Requests\Api\V1\Concerns;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * API用FormRequestに共通の、バリデーション失敗時のJSONレスポンス整形。
 *
 * Laravel標準の要約メッセージ形式（1件目のエラー + "(and N more errors)"）を
 * 明示的なJSONレスポンスとして返す。Acceptヘッダーの有無に関わらず、
 * APIルートでは常にJSONで返すためにfailedValidation()を上書きしている。
 */
trait FormatsJsonValidationErrors
{
    /**
     * バリデーション失敗時、422 JSONで日本語エラーメッセージを返す
     */
    protected function failedValidation(ValidatorContract $validator)
    {
        $messages = $validator->errors()->all();
        $message = array_shift($messages);

        if ($additional = count($messages)) {
            $message .= $additional === 1
                ? " (and {$additional} more error)"
                : " (and {$additional} more errors)";
        }

        throw new HttpResponseException(
            response()->json([
                'message' => $message,
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}