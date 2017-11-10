<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/5
 * Time: 下午2:47
 */
namespace see\smarty;

use see\base\Object;
use see\base\ViewRenderInterface;

require (__DIR__."/smarty-3.1.29/libs/Smarty.class.php");
class ViewRender extends Object implements ViewRenderInterface{
    /**
     * @var \Smarty
     */
    public $smarty;

    public $cachePath = "@runtime/Smarty/cache";

    public $compilePath = "@runtime/Smarty/compile";

    public $options = [];

    public $imports = [];

    public function init(){
        $this->smarty = new \Smarty();
        $this->smarty->setCacheDir(\See::getAlias($this->cachePath));
        $this->smarty->setCompileDir(\See::getAlias($this->compilePath));

        foreach($this->options as $key=>$value){
            $this->smarty->$key = $value;
        }
        if(\See::$app->controller){
            $dir = [
                \See::$app->getViewPath() . DIRECTORY_SEPARATOR . \See::$app->controller->id,
                \See::$app->getViewPath(),
            ];
        }else{
            $dir = [
                \See::$app->getViewPath(),
            ];
        }
        $this->smarty->setTemplateDir($dir);
        
        foreach($this->imports as $id=>$class){
            $this->smarty->registerClass($id,$class);
        }
        $this->smarty->assign('params',\See::$app->params);
    }


    public function render($view, $viewFile, $params)
    {
        /* @var $template \Smarty_Internal_Template */
        $template = $this->smarty->createTemplate($viewFile, null, null, empty($params) ? null : $params, false);
        // Make Yii params available as smarty config variables
        $template->assign('app', \See::$app);
        $template->assign('this', $view);
        return $template->fetch();
    }
}