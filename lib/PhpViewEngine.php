<?php
namespace Stack\Lib;

class PhpViewEngine extends ViewEngine {
  protected $views = 'views';
  protected $ext = '.phtml';

  /**
   * Define a views directory
   * @param String $dir Views Directory
   * @param String $dir View Extension
   */
  public function setViews (String $dir, String $ext = '.phtml') {
    $this->views = $dir;
    $this->ext = $ext;
    return $this;
  }


  /**
   * Render a view
   * @param String $view View name
   * @param Array $data View variables
   */
  public function render(String $view, Array $data = []):String {
    $__PHTML_DATA__ = $data;
    $__PHTML_NAME__ = $this->views . "/$view{$this->ext}";

    if (!file_exists($__PHTML_NAME__)) {
      throw new \Exception("View ".$__PHTML_NAME__." not exists!");
    }
    
    ob_start();

    (function ($__PHTML_NAME__, $__PHTML_DATA__) {
      extract($__PHTML_DATA__);
      include $__PHTML_NAME__;
    })($__PHTML_NAME__, $__PHTML_DATA__);

    $out = ob_get_contents();
    ob_end_clean();

    return $out;
  }

  /**
   * Include a view
   * @param String $view View name
   * @param Array $data View variables
   */
  public function include(String $partial, Array $data = []):void {
    echo $this->render($partial, $data);
  }
}