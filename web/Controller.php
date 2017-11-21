<?php
namespace see\web;

class Controller extends \see\base\Controller
{
    /**
     * @var \see\web\Request
     */
    public $request;

    public function __construct($id, $module, array $config= [])
    {
        $this->request = \See::$app->getRequest();
        parent::__construct($id, $module, $config);
    }

}
