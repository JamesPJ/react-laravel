<?php namespace React;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ReactServiceProvider extends ServiceProvider {

  public function boot() {

    Blade::directive('react_component', function($view) {
      $pattern = $this->createMatcher('react_component');
      list($component, $props, $options) = $this->cleanExpression($view);
      $out = app('LaravelReact')->render($component, $props, $options);
      return preg_replace($pattern, "<?php echo $out ?>", $view);
    });

    $prev = __DIR__ . '/../';

    $this->publishes([
      $prev . 'assets'            => public_path('vendor/react-laravel'),
    ], 'assets');

    $this->publishes([
      $prev . 'config/config.php' => config_path('react.php'),
    ], 'config');
  }

  public function register() {

    $this->app->bind('LaravelReact', function() {
      $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'react');

      $reactSource = file_get_contents(config('react.source'));

      $componentsSource = null;
      $components = config('react.components');
      if (!is_array($components)) {
        $components = [$components];
      }
      foreach ($components as $component) {
        $componentsSource .= file_get_contents($component);
      }

      return new LaravelReact($reactSource, $componentsSource);
    });
  }

  protected function cleanExpression($view) {
    list($component, $props, $options) = preg_split('/[,]+(?![^\[]*\])/', $view);
    $props = $this->cleanArray($props);
    $options = $this->cleanArray($options);

    return [$component, $props, $options];
  }

  protected function cleanArray($input) {
    $rows = preg_split('/[,]+(?![^\[]*\])/', preg_replace('/(\[|\])+/', '', $input));
    $array = [];
    foreach($rows as $row){
        $kv = explode("=>",$row);
        if(!empty($kv) && count($kv) > 1) {
          $array[$kv[0]] = $kv[1];
        }
    }
    return $array;
  }

  protected function createMatcher($function) {
    return '/(?<!\w)(\s*)@' . $function . '(\s*\([\s\S]*?\))/';
  }
}
