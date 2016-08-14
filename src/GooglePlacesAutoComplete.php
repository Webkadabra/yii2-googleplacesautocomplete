<?php

namespace PetraBarus\Yii2\GooglePlacesAutoComplete;

use yii\web\JsExpression;
use yii\widgets\InputWidget;
use yii\helpers\Html;


class GooglePlacesAutoComplete extends InputWidget
{
    const API_URL = '//maps.googleapis.com/maps/api/js?';
    public $libraries = 'places';
    public $sensor = true;
    public $language = 'en-US';
    public $key      = null;
    public $autocompleteOptions = [];
    /**
     * @var array
     */
    public $listeners = [];

    /**
     * Renders the widget.
     */
    public function run()
    {
        $this->registerClientScript();
        if ($this->hasModel()) {
            echo Html::activeTextInput($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textInput($this->name, $this->value, $this->options);
        }
    }

    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript()
    {
        $elementId     = $this->options['id'];
        $key           = isset($this->options['key']) ? $this->options['key'] : null;
        $scriptOptions = json_encode($this->autocompleteOptions);
        $view          = $this->getView();
        $listeners = '';
        $id = $this->id;
        if ($this->listeners) {
            foreach ($this->listeners as $event => $handler) {
                $listeners .= "{$id}autocomplete.addListener('$event', ". new JsExpression("function(){{$handler}({$id}autocomplete)}").");\n";
            }
        }
        $view->registerJsFile(self::API_URL . http_build_query([
                'libraries' => $this->libraries,
                'sensor'    => $this->sensor ? 'true' : 'false',
                'language'  => $this->language,
                'key'       => $key,
            ]));
        $view->registerJs(<<<JS
(function(){
    var input = document.getElementById('{$elementId}');
    var options = {$scriptOptions};
    {$id}autocomplete = new google.maps.places.Autocomplete(input, options);
    {$listeners}
})();
JS
            , \yii\web\View::POS_READY);
    }
}
