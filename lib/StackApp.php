<?php
namespace Stack\Lib;

/**
 * StackApp - Main router class
 * @package Stack\Lib
 */
class StackApp extends Router {

    /**
     * Base namespace for every controllers in app
     *
     * @var string
     */
    static $stack_controllers = '';

    /**
     * @var HttpRequest
     */
    static $request;

    /**
     * @var HttpResponse
     */
    static $response;

    /**
     * @param string $controllers Base namespace for every controller in app
     */
    public function __construct ($controllers = '') {
        self::$stack_controllers = $controllers;
        parent::__construct('/');
    }

    /**
     * Capture the request and start routing
     *
     * @return null
     */
    public function start() {
        self::$request = HttpRequest::get_current();
        self::$response = new HttpResponse();

        $request = &self::$request;
        $response = &self::$response;
        $request->app = &$this;
        
        try {
            $res = $this->init($request, $response);

            if (\is_null($res)) {
                // if ($response->status) {
                //     $response->clear();
                //     throw new HttpError(HttpError::INTERNAL_SERVER_ERROR, [
                //         'code' => 'stack_app_error',
                //         'message' => 'The middleware stack is gone, but has not returned any HttpResponse object.'
                //     ]);
                // }

                throw new HttpError(HttpError::NOT_FOUND,
                    "Cannot found $request->original_url.");
            }

            if ($res instanceof HttpError) {
                throw $res;
            }

            if (!($res instanceof HttpResponse) && $res !== true) {
                throw new HttpError(HttpError::INTERNAL_SERVER_ERROR, $res);
            }
            
        } catch (\Exception $error) {
            $response->error($error);
        }

        $response->end();
        return null;
    }
}