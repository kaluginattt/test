<?php
// пусть это будет класс драйвера к ElasticSearch
class ESDriver implements ElasticSearchDriver
{
    ...
}

// пусть это будет класс драйвера к MySQL
class MSDriver implements MySQLDriver
{
    ...
}

class ProductController
{
    //Переключатель базы: 0 - ElasticSearch, 1 - MySQL
    private $dbSource = 0;
   
    public function setDBSource(int $db)
    {
        $this->dbSource = $db;
    }
   
    //Возвращает продукт в формате JSON
    public function detail(int $id)
    {
        $cachePr = new CacheProductInFile();
        $cacheCnt = new CacheCountInFile();
        $prStr = $cachePr->findProduct($id);
        if(strlen($productStr) == 0) {
            switch ($this->dbSource) {
                case 0:
                    $driver = new ESDriver();
                    $prArray = $driver->findById($id);
                    break;
                case 1:
                    $driver = new MSDriver();
                    $prArray = $driver->findProduct($id);
                    break;
                default:
                    $prArray = array();
            }
            $prStr = json_encode($prArray);
            $cachePr->saveProduct($prStr);
            $cacheCnt->saveNewProduct($id);
        }
        else $cacheCnt->incProduct($id);
    }
    return $prStr;
}

interface CacheProductData
{
    public function findProduct($id): string;
    public function saveProduct($id, $product);
}

interface CacheProductCount
{
    public function saveNewProduct($id);
    public function incProduct(int $id);
}

class CacheProductInFile implements CacheProductData
{
    //файл кеша и путь к нему
    private $fileName = 'cacheProduct.txt';
    private $cacheFile = '/cache/'.$fileName;
   
    /**
    * Ищет продукт в файле и возвращает в формате JSON
    * Строка файла имеет формат:
    * id:данные продукта в формате JSON
    * @return string
    */
    public function findProduct($id)
    {
        $find = $id.':';
        $cnt = strlen($find);
        $product = '';
        if (file_exists($this->$cacheFile) {
            $fp = fopen($this->$cacheFile, 'r');
            if ($fp) {
                while (($s = fgets($fp)) !== false) {
                    if (substr($s, 0, $cnt) == $find) {
                        $product = rtrim(substr($s, $cnt));
                        break;
                    }
                }
                fclose($fp);
            }
        }
        return $product;
    }
   
    //Сохраняет продукт в файле
    public function saveProduct($id, $product)
    {
        if (file_exists($this->$cacheFile) {
            $fp = fopen($this->$cacheFile, 'a');
            if ($fp) {
                fwrite($fp, $id.':'.$product.PHP_EOL);
                fclose($fp);
            }
        }
    }
}

class CacheCountInFile implements CacheProductCount
{
    //файл кеша и путь к нему
    private $fileName = 'cacheCount.txt';
    private $cacheFile = '/cache/'.$fileName;
    /**
    * Сохраняет количество запросов продукта в файле
    * Строка файла имеет формат:
    * id:количество запросов
    */
    public function incProduct($id)
    {
        if (file_exists($this->$cacheFile) {
            $rows = file($this->$cacheFile);
            if (is_array($rows)) {
                foreach($rows as $k=>$v) {
                    $data = explode(':', $v);
                    if (sizeof($data) == 2 && ($data[0] == $find)) {
                        $rows[$k] = $id.':'.(is_numeric($data[1]) ? $data[1]+1 : 1).PHP_EOL;
                        break;
                    }
                }
                file_put_contents($this->$cacheFile, $rows, LOCK_EX);
            }
        }
    }
   
    //Сохраняет первый запрос продукта в файле
    public function saveNewProduct($id)
    {
        if (file_exists($this->$cacheFile) {
            $fp = fopen($this->$cacheFile, 'a');
            if ($fp) {
                fwrite($fp, $id.':1'.PHP_EOL);
                fclose($fp);
            }
        }
    }
}

