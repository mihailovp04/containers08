<?php

class Page {
    private $template;

    public function __construct($template) {
        $this->template = $template;
    }

    public function Render($data) {
        ob_start();
        extract($data);
        include $this->template;
        return ob_get_clean();
    }
}