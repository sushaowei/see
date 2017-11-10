<?php
/**
 * Created by PhpStorm.
 * User: ShaoWeiSu
 * Date: 2016/6/8 0008
 * Time: 下午 12:26
 */
namespace see;
use Exception;
/**
 * 定义框架路径
 */
use see\base\Application;
use see\base\Logger;
use see\base\Object;

defined("SEE_PATH") or define('SEE_PATH', __DIR__);

/**
 * 框架开始时间
 */
defined("SEE_BEGIN_TIME") or define('SEE_BEGIN_TIME', microtime(true));

/**
 * Class BaseSee
 * @package see
 */
class BaseSee
{
    /**
     * @var Application|\see\web\Application
     */
    public static $app;
    /**
     * @var Logger
     */
    public static $log;

    public static $classMap = [];

    public static $aliases = ['@see' => __DIR__];

    /**
     * @param  string $alias 别名名称
     * @param string $path 值
     * @throws \Exception
     */
    public static function setAlias($alias, $path)
    {
        //添加别名标志 @
        if (strncmp($alias, '@', 1)) {
            $alias = '@' . $alias;
        }

        //取根别名
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if ($path !== null) {
            //如果值是别名,则获取其值, 如: $path = '@foo/bar' 则先获取 @foo 的值
            $path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);

            if (!isset(static::$aliases[$root])) {//如果根别名没有设置
                if ($pos === false) {
                    // setAlias('@foo', '/path' ) 存储为 static::$aliases = ['@foo'=>'/path']
                    static::$aliases[$root] = $path;
                } else {
                    // setAlias('foo/bar', '/path/bar') 存储为 static::$aliases = [ '@foo'=>[ '@foo/bar'=>'/path/bar' ] ]
                    static::$aliases[$root] = [$alias => $path];
                }
            } elseif (is_string(static::$aliases[$root])) { //如果根别名已经设置
                if ($pos === false) {
                    //setAlias('@foo' ,'/path') 设置新的值
                    static::$aliases[$root] = $path;
                } else {
                    //setAlias('@foo/bar', '/path') 则存储为 static::$aliases=[ '@foo'=> [ '@foo'=>'/path', '@foo/bar' => '/path/bar'] ]
                    static::$aliases[$root] = [
                        $alias => $path,
                        $root => static::$aliases[$root],
                    ];
                }
            } else {
                static::$aliases[$root][$alias] = $path;
                krsort(static::$aliases[$root]);
            }
            //如果$path === null 删除别名
        } elseif (isset(static::$aliases[$root])) {
            if (is_array(static::$aliases[$root])) {
                unset(static::$aliases[$root][$alias]);
            } elseif ($pos === false) {
                unset(static::$aliases[$root]);
            }
        }
    }

    /**
     * 解析别名路径
     * @param string $alias 别名
     * @param bool $throwException
     * @return bool|mixed|string
     * @throws \Exception
     */
    public static function getAlias($alias, $throwException = true)
    {
        if (strncmp($alias, '@', 1)) {
            // 不是别名,直接返回
            return $alias;
        }

        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
            } else {
                foreach (static::$aliases[$root] as $name => $path) {
                    if (strpos($alias . '/', $name . '/') === 0) {
                        //setAlias('@foo', '/path'),  getAlias('@foo/path2/file.php') 返回: '/path/path2/file.php'
                        return $path . substr($alias, strlen($name));
                    }
                }
            }
        }

        if ($throwException) {
            throw new \Exception("Invalid path alias: $alias");
        } else {
            return false;
        }
    }

    /**
     * 自动加载方法
     * @param string $className
     * @throws \Exception
     */
    public static function autoload($className)
    {
        $classFile = false;
        if (isset(static::$classMap[$className])) {
            $classFile = static::$classMap[$className];
            if ($classFile[0] === '@') {
                $classFile = static::getAlias($classFile);
            }
        } elseif (strpos($className, '\\') !== false) {
            $classFile = static::getAlias('@' . str_replace('\\', '/', $className) . '.php', false);
        }
        if ($classFile === false || !is_file($classFile)) {
            return ;
        }
        
        include($classFile);
    }

    /**
     * 设置对象的配置
     * @param \see\base\Object $object
     * @param array $properties
     * @return mixed
     */
    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }
    public static function createObject($type, array $params = [])
    {
       return static::$app->createObject($type, $params);
    }

}