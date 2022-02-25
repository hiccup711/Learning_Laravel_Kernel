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
 * 构建类与类的依赖
 * @param $className
 * @return object|null
 * @throws ReflectionException
 */
function make($className)
{
    $reflectionClass = new ReflectionClass($className);
    $constructor = $reflectionClass->getConstructor(); // 获取构造函数方法
    $parameters  = $constructor->getParameters(); // 获取构造函数的参数
    $dependencies = getDependencies($parameters);  // 开始解析依赖

    return $reflectionClass->newInstanceArgs($dependencies); // 返回实体类
}

/**
 * 依赖解析 递归调用
 * @param $parameters // 由 ReflectionParameter 对象组成的数组
 * @return array
 * @throws ReflectionException
 */
function getDependencies($parameters)
{
    $dependencies = [];

    foreach($parameters as $parameter) {

        /**
         * 通过参数名称，使用 getClass() 方法， 获取 ReflectionClass 类的对象
         * 我们 Brand 的构造函数第一个参数是 Car 类，这里的代码流程就是：
         * 第一次循环时： $parameter 的参数为 car， $dependency 此时是通过 getClass() 方法获取到了 Car 的反射类。
         *   - 因为获取到了反射类，所以不会走 if(is_null())，而是走到下面的 else 的递归调用。
         *   - 再次返回到上面的 make() 方法，此时获取的反射类是 Car Car Car！ $paramters 有两个参数，这两个参数分别是 Car 的构造方法中的 $gearbox 和 $engine
         *   - 当再次进入这个 foreach 循环时， $parameter 无法再通过 getClass() 方法获取反射类，就会走到 if(is_null($dependency)) 方法，isDefaultValueAvailable() 方法是用于判断构造函数的参数是否有默认值，如果有将它加入到 $dependencies 数组中。如果没有，我们需要「补0」，用于构造函数必须含有参数的情况。
         *   - 到此，Car 类就被依赖注入成功了。
         * 第二次循环时：因为 $dependency 无法通过 $name 的值获取到反射类，那和上面的 $gearbox 与 $engine 的流程相同。
         */
        $dependency = $parameter->getClass();

        if (is_null($dependency)) {

            if($parameter->isDefaultValueAvailable()) {

                $dependencies[] = $parameter->getDefaultValue();

            } else {
                // 不是可选参数的为了简单直接赋值为字符串0
                // 针对构造方法的必须参数这个情况
                // Laravel 是通过 service provider 注册 closure 到 IocContainer,
                // 在 closure 里可以通过 return new Class($param1, $param2) 来返回类的实例
                // 然后在 make 时回调这个 closure 即可解析出对象
                // 具体细节我会在另一篇文章里面描述
                $dependencies[] = '0';
            }
        } else {

            //递归解析出依赖类的对象
            $dependencies[] = make($parameter->getClass()->name);

        }
    }

    return $dependencies;
}

$brand = make(Brand::class);
$brand->carInfo();