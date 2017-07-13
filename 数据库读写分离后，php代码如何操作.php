<?php




    class Model
    {

    	protected $pdo;


 
    	//这个方法负责执行查询类型的SQL语句，不能写操作
        public function query($sql)
        {

        	$dsn = 'mysql:host=192.168.17.54;dbname=lamp27;charset=utf8';

    		$this->pdo = new PDO($dsn, 'root', '123456');

        	$this->pdo->query();
        }

        //负责写操作(update/delete/insert)
        public function exec($sql)
        {
			$dsn = 'mysql:host=192.168.17.66;dbname=lamp27;charset=utf8';

    		$this->pdo = new PDO($dsn, 'root', '123456');

    		$this->pdo->exec($sql);

        }
    }
