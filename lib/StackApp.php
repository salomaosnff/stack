<?php
namespace Stack\Lib;

class StackApp extends Router {
    public function __construct () {
        parent::__construct('/', '\App\Routers');
    }
    public function init(Router ...$routers) {
        $request = HttpRequest::get_current();
        $response = new HttpResponse;
        
        $errors = parent::init($request, $response);
        
        $this->use(...$routers);
        $this->use(function($req, $res) {
            $res->text("Cannot $req->method $req->originalUrl");
            return false;
        });
        
        
        if (!MiddlewareStack::__check_value($errors)) return $errors;
        
        foreach ($routers as $router) {
            $errors = $router->init($request, $response);
            if ($errors === true) break;
            if (MiddlewareStack::__check_value($errors)) continue;
            else return $errors;
        }

        if (\is_null($errors) || ($error === true && count($this->sub_routers) <= 0)) {
            $error = new HttpError(HttpError::NOT_FOUND);
            $response->error($error);
        }

        $response->end();
        return null;
    }
}