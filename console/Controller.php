<?php
namespace see\console;

class Controller extends \see\base\Controller
{
    public function bindActionParams($action, $params)
    {
        return $params;
    }
}
