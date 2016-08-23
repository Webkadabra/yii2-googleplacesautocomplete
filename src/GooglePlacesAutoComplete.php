<?php

namespace PetraBarus\Yii2\GooglePlacesAutoComplete;

use yii\web\JsExpression;
use yii\widgets\InputWidget;
use yii\helpers\Html;


class GooglePlacesAutoComplete extends InputWidget
{
    const API_URL = '//maps.googleapis.com/maps/api/js?';

    /** 
     * @var bool - if true, a widget will behave in a way to try and ensure user did select an option before submission
     */
    public $tryEnsure = false;
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

        if ($this->tryEnsure) {
            $view->registerJs(<<<JS
/**
* @link http://stackoverflow.com/questions/7865446/google-maps-places-api-v3-autocomplete-select-first-option-on-enter
* @param input
*/
function pacSelectFirst(input) {
    // store the original event binding function
    var _addEventListener = (input.addEventListener) ? input.addEventListener : input.attachEvent;
    function addEventListenerWrapper(type, listener) {
        // Simulate a 'down arrow' keypress on hitting 'return' when no pac suggestion is selected,
        // and then trigger the original listener.
        if (type == "keydown") {
            var orig_listener = listener;
            listener = function(event) {
                 if (event.which == 13) {
                var suggestion_selected = $(".pac-item-selected").length > 0;
                var is_pac_open = $(".pac-item:visible").length > 0;
                 if (!suggestion_selected && is_pac_open) {
                    var simulated_downarrow = $.Event("keydown", {
                        keyCode: 40,
                        which: 40
                    });
                  setTimeout(function(){
                  orig_listener.apply(input, [simulated_downarrow]);
                    orig_listener.apply(input, [event]);
                  },100);
                 }
                 if (is_pac_open) {
                  event.preventDefault();
                  setTimeout(function(){
                    orig_listener.apply(input, [event]);
                  },100);
                 }
                } else {
                  orig_listener.apply(input, [event]);
                }
            };
        }
        _addEventListener.apply(input, [type, listener]);
    }

    var options = {$scriptOptions};

    input.addEventListener = addEventListenerWrapper;
    input.attachEvent = addEventListenerWrapper;

    var {$id}autocomplete = new google.maps.places.Autocomplete(input, options);
    {$listeners}
}
(function(){
    var input = document.getElementById('{$elementId}');
    pacSelectFirst(input);
})();
JS
                , \yii\web\View::POS_READY);
        } else {
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
}
