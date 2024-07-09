<?php

namespace EcospoldExplorer;

/**
* Response
* This class provides methods to return JSON responses
*/
class Response
{

    /**
     * This method send an HTTP response under a JSON format and stops the current script
     *
     * @param int $responseCode as a valid HTTP code (see the list here: https://www.php.net/manual/en/function.http-response-code.php#107261)
     * @param string $responseMessage as an optional message
     * @param array $responseData as an array to return json data
     *
     * @return void
     */
    public static function send(int $responseCode, string $responseMessage = null, array $responseData = []): void
    {
        $response['status'] = $responseCode == 200 ? "success" : "error";

        if (!is_null($responseMessage)) {
            $response['message'] = $responseMessage;
        }

        if (!empty($responseData)) {
            $response['data'] = $responseData;
        }

        http_response_code($responseCode);
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

}