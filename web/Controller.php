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
        parent::__construct($id, $module, $config);
        $this->request = \See::$app->getRequest();
    }


}
