<?php

/**
 * Car 汽车信息
 */
class Car
{
    public $gearbox;
    public $engine;

    /**
     * @param $gearbox // 变速箱
     * @param $engine // 汽车引擎
     */
    public function __construct($gearbox = '4AT', $engine = 'V8')
    {
        $this->gearbox = $gearbox;
        $this->engine = $engine;
    }
}

/**
 * Brand 汽车品牌
 */
class Brand
{
    public $car;
    public $name;

    const WHEEL = 4; // 4个轮子

    /**
     * @param Car $car // 依赖 Car 类
     * @param $name // 品牌名称
     */
    public function __construct(Car $car, $name = '五菱')
    {
        $this->car = $car;
        $this->name = $name;
    }

    public function carInfo()
    {
        printf($this->name . '汽车是一辆' . $this->car->gearbox . '变速箱' . $this->car->engine . '发动机的' . self::WHEEL . '轮车。');
    }
}

//$reflection = new ReflectionClass(Brand::class);
//$constructor = $reflection->getConstructor();
//var_dump($reflection->getConstants()); // 反射类的常量
//var_dump($reflection->getMethods()); // 反射类中定义的方法
//var_dump($reflection->getConstructor()); // 反射类中定义的方法
//var_dump($constructor->getParameters()); // 反射类中定义的方法


/**
 * 构建类的对象
 * @param $className
 * @return object|null
 * @throws ReflectionException
 */
function make($className)
{
    $reflectionClass = new ReflectionClass($className);
    $constructor = $reflectionClass->getConstructor(); // 获取构造函数方法
    $parameters  = $constructor->getParameters(); // 获取构造函数的参数
    $dependencies = getDependencies($parameters);

    return $reflectionClass->newInstanceArgs($dependencies);
}

/**
 * 依赖解析
 * @param $parameters
 * @return array
 * @throws ReflectionException
 */
function getDependencies($parameters)
{
    $dependencies = [];
    foreach($parameters as $parameter) {
        $dependency = $parameter->getClass();
        if (is_null($dependency)) {
            if($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                //不是可选参数的为了简单直接赋值为字符串0
                //针对构造方法的必须参数这个情况
                //laravel是通过service provider注册closure到IocContainer,
                //在closure里可以通过return new Class($param1, $param2)来返回类的实例
                //然后在make时回调这个closure即可解析出对象
                //具体细节我会在另一篇文章里面描述
                $dependencies[] = '0';
            }
        } else {
            //递归解析出依赖类的对象
            $dependencies[] = make($parameter->getClass()->name);
        }
    }

    return $dependencies;
}