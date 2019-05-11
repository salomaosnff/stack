<?php
namespace Stack\Lib;

require_once __DIR__ . '/Functions.php';

/**
 * Stack - Main router class
 * @package Stack\Lib
 */
class Stack extends Router {
    public $response;
    public $request;

    /**
     * @param string $controllers Base namespace for every controller in app
     */
    public function __construct ($controllers = '') {
        parent::__construct('/', $controllers);
        $this->request = HttpRequest::get_current();
        $this->response = new HttpResponse();
        $this->response->app = $this;
    }

    /**
     * Capture the request and start routing
     * @return null
     */
    public function start() {
        $request  = $this->request;
        $response = $this->response;
        
        try {
            $res = $this->dispatch($request, $response);

            if (\is_null($res)) {
              throw new HttpException(HttpException::NOT_FOUND,
                    "Cannot found $request->original_url.");
            }

            if ($res instanceof \Exception) throw $res;

            if (!($res instanceof HttpResponse)) {
                /**
                 * Show default value of type
                 */
                if(\is_bool($res) || \is_array($res) || \is_object($res)) {
                    $response->json($res);
                }
                else if(\is_string($res) || \is_numeric($res)) {
                    $response->write((string) $res);
                }
                else {
                    throw new HttpException(HttpException::INTERNAL_SERVER_ERROR, $res);
                }
            }
            
        } catch (\Exception $error) {
            $response->error($error);
        }

        $response->end();
        return null;
    }

    public function setViews (String $dir, String $ext = '.phtml') {
        $this->response->viewEngine->setViews($dir, $ext);
        return $this;
    }

    public function setViewEngine (ViewEngine $engine) {
        $this->response->viewEngine = $engine;
        return $this;
    }
}