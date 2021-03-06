<?php
namespace see\console;
use see\exception\NotFoundException;


/**
 * Class Application
 * @package see\console
 */
class Application extends \see\base\Application
{
    public $defaultRoute = 'site';

    public $controllerNamespace = 'app\console';

    /**
     * @param \see\console\Request $request
     * @return mixed|Response|\see\console\Response
     */
    public function handleRequest($request)
    {
    }

    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => '\see\console\Request'],
            'response' => ['class' => '\see\console\Response'],
        ]);
    }

    /**
     * @param string $argv
     * @throws NotFoundException
     */
    public function run($argv='')
    {
        $request = \See::$app->getRequest();

        list($route, $params) = $request->resolve($argv);
        $this->requestedRoute = $route;
        \See::$log->addBasic('route', $route);
        $result = $this->runAction($route, $params);
        if ($request instanceof Response) {
            $response = $result;
        } else {
            $response = $this->getResponse();
            $response->data = $result;
        }

        \See::$log->notice("complete");
        $response->send();
    }
}