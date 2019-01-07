<?php
namespace Stack\Lib;

class HttpError extends \Exception implements \Throwable {

    const BAD_REQUEST           = [400, 'Bad Request'];
    const UNAUTHORIZED          = [401, 'Unauthorized'];
    const FORBIDDEN             = [403, 'Forbidden'];
    const NOT_FOUND             = [404, 'Not Found'];
    const METHOD_NOT_ALLOWED    = [405, 'Method Not Allowed'];
    const NOT_ACCEPTABLE        = [406, 'Not Acceptable'];
    const REQUEST_TIMEOUT       = [408, 'Request Timeout'];
    const INTERNAL_SERVER_ERROR = [500, 'Internal Server Error'];
    const SERVICE_UNAVAILABLE   = [503, 'Service Unavailable'];

    public $info = null;
    public $timestamp = null;

    public function __construct ($status, $message = null, $info = null) {
        if (is_array($status)) {
            $info = $message;
            $message = $status[1];
            $status = $status[0];
        }

        parent::__construct($message, $status);

        $this->info = ($info instanceof \Exception) ? [
            'message' => $info->getMessage(),
            'code' => $info->getCode(),
        ] : $info;

        $this->timestamp = time();
    }
}
