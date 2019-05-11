<?php
namespace Stack\Lib;

class ViewEngine {
  protected $views = 'views';
  protected $ext = '';

  public function setViews (String $dir, String $ext = '') {
    $this->views = $dir;
    $this->ext = $ext;
    return $this;
  }

  public function render(String $view, Array $data = []):String {
    $engine = static::class;
    throw new \Exception("$engine#render() not implemented!");
  }

  public function include (String $view, Array $data = []):void {
    throw new \Exception("include() not implemented!");
  }
}