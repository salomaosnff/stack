<?php
use Stack\Lib\Router;
use Stack\Lib\HttpRequest;

/**
 * Normalize a method string, remove '@' and place '::'
 * with controllers base namespace
 *
 * @param string|callable|Routeable $method
 * @param string|null $controllers Namespace base para os controladores
 * @return string|string[]|null
 */
function normalize_method($method, $controllers = '') {
  if(is_string($method)) {
    $controllers = is_null($controllers) ? '' : trim("$controllers", "\\");
    $method = preg_replace('/\@+/', '::', $method);

    if(empty($controllers)) {
      return $method;
    }
    else if(substr($method, 0, 1) === '\\') {
      $controllers = '';
    }
    return "\\" . trim("$controllers\\$method", "\\");
  }
  return $method;
}

/**
 * Normalize a URL
 *
 * @param string ...$url
 * @return string|string[]|null
 */
function normalize_url(string...$url) {
  array_unshift($url, '/');
  $url = join('/', $url);
  $url = \preg_replace('@/+@', '/', $url);
  $url = \preg_replace('@(?<=.)/$@', '', $url);
  return $url;
}

/**
 * Parse the URL params
 *
 * @param string $url
 * @param bool $end
 * @return array
 */
function url_params(string $url, bool $end = true) {
  $params = [];
  $regex = ['@^'];

  $url = \preg_replace_callback('@:([\w-_]+)([^\/]*)@', function ($match) use (&$params) {
    $name = $match[1];
    $params[] = $name;

    if ($match[2] === '?') {
      $match[2] = "?([^/]*)/*";
    }
    return empty($match[2]) ? '([^/]+)' : $match[2];
  }, $url);

  if (!$end && $url !== '/') {
    $url .= '\b';
  }

  $regex[] = $url;

  if ($end) {
    $regex[] = '$';
  }

  $regex[] = '@';
  $regex = join('', $regex);

  return [
    'params' => $params,
    'regex' => $regex,
  ];
}

/**
 * Test a URL with the regex
 *
 * @param HttpRequest $request
 * @param bool $removeBaseURL
 * @param Router|Route $router Router instance
 * @return bool
 */
function test_url(
  HttpRequest &$request, 
  bool $removeBaseURL = true, 
  $router
) {
  preg_match($router->regex, $request->url, $matches);

  if (count($matches) <= 0) {
    return false;
  }

  if(count($matches) === 1) {
    $matches = [trim(array_shift($matches), '/')];
  } else {
    $matches = array_slice($matches, 1);
  }

  if ($removeBaseURL) {
    $request->url = normalize_url(\preg_replace($router->regex, '', $request->url));
  }

  if (empty($request->params)) {
    $request->params = [];
  }

  $params = @array_combine($router->params, $matches);

  if(is_array($params)) {
    $request->params = array_merge($request->params, $params);
  }

  return true;
}

/**
 * Resolve namespaces
 */
function resolve_namespace($baseNamespace = '', $namespace = '') {
  if(substr($namespace, 0, 1) === '\\') {
      $baseNamespace = '';
  }
  $namespace = '\\' . $baseNamespace . '\\' . $namespace;
  return trim(preg_replace('@\\+@', '\\', $namespace), '\\');
}
