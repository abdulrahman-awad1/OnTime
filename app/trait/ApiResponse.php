<?php

namespace App\trait;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

trait ApiResponse
{
    private array $defaultMessages = [
        'name'     => 'الاسم غير مناسب',
        'email'    => 'الايميل غلط',
        'password' => 'خطاء ف الباسورد',
        'phone'    => 'رقم الموبايل غلط',
    ];

    private function getCurrentLang(): string
    {
        return app()->getLocale();
    }

    private function logError(string $errNum, string $msg, array $extra = []): void
    {
        Log::error("API Error [$errNum]: $msg", $extra);
    }

    public function returnError(string $errNum, string $msg, int $httpCode = Response::HTTP_BAD_REQUEST, array $extra = [])
    {
        $this->logError($errNum, $msg, $extra);

        return response()->json([
            'status' => false,
            'errNum' => $errNum,
            'msg'    => $msg,
        ], $httpCode);
    }

    public function returnValidationError(string $errNum, $validator)
    {
        return $this->returnError($errNum, $validator->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function successMessage(string $msg = 'success', int $httpCode = Response::HTTP_OK, array $extra = [])
    {
        return response()->json(array_merge([
            'status' => true,
            'errNum' => '0000',
            'msg'    => $msg,
        ], $extra), $httpCode);
    }

    public function returnData(string $key, $value, string $msg = 'success', int $httpCode = Response::HTTP_OK, array $extra = [])
    {
        return response()->json(array_merge([
            'status' => true,
            'errNum' => '0000',
            'msg'    => $msg,
            $key     => $value
        ], $extra), $httpCode);
    }

    public function returnCodeAccordingToInput($validator): string
    {
        $inputs = array_keys($validator->errors()->toArray());
        return $this->getErrorCode($inputs[0]);
    }

    public function getErrorCode(string $input): string
    {
        return $this->defaultMessages[$input] ?? 'حدث خطأ غير معروف';
    }
}
