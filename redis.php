<?php
Class RedisDb{
    private $host = "127.0.0.1";
    private $port = "6379";
    private $redis;
    private static $instance;

    private function __construct(){
        $this->redis = new Redis();
        $this->redis->connect($this->host, $this->port);
       // return $redis;
    }

    public static function getInstance(){
        if (!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __clone(){}

    public function __destruct()
    {
        self::getInstance()->redis->close();
        self::$instance = null;
    }

    public function redis(){
        return $this->redis;
    }
    
}