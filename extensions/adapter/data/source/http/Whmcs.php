<?php

namespace li3_whmcs\extensions\adapter\data\source\http;

use lithium\util\String;
use lithium\util\Inflector;

class Whmcs extends \lithium\data\source\Http {

    /**
     * default config.
     * @var array
     */
    private $_defaults = array(
        'auth' => 'Basic',
        'version' => '1.1',
        'basePath' => '/includes/api.php'
    );
    
    /**
     * Paths to API.. because lithium is lovely we can simply
     * use the modem "name" and cast this to the API method. 
     * 
     * model => corrosponding API action.
     * @var array
     */
    private $_paths = array(
        'products' => 'getproducts',
    );
    
    /**
     * Lithium dependancy injection, I've chosen Document rather than record as
     * this best fits our use-case.
     * @var array
     */
    protected $_classes = array(
        'service' => 'lithium\net\http\Service',
        'entity' => 'lithium\data\entity\Document',
        'set' => 'lithium\data\collection\DocumentSet',
    );

    /**
     * Not doing much here, just cobining our default and user provided config!
     * @param array $config 
     */
    public function __construct(array $config = array()) {

        $config += $this->_defaults;
        parent::__construct($config);
    }

    /**
     * We need to override read() to inspect the Query object and make our
     * calls to the lovely (not really) WHMCS API.
     * 
     * @param object $query a query object that is passed.
     * @param array $options 
     */
    public function read($query, array $options = array()) {

        $params = $query->export($this, array('source', 'conditions'));

        $action = $params['source']; //the action or method used in Whmcs API
        $params = empty($params['conditions']) ? array() : $params['conditions']; // array of params where the key is param, the value is the value!
        
        $xml = $this->_call($action,$params);
        if ($xml) {
            $products = $xml->products;
            $arr = array();
            $this->objToArray($products, $arr); //item() needs array.
            return $this->item($query->model(), $arr['product'], array('class' => 'set'));
        }
        
        return null;
        
    }
    
    /**
     * Calls the API with action and params provided.
     * 
     * @param type $action
     * @param array $params
     * @return  
     */
    private function _call($action, array $params){
        
        if (!isset($this->_paths[$action])) { //if the api method isn't mapped or doesn't exist, we'll bail out.
            return false;
        }
        
        $params += array(
            'action' => $this->_paths[$action],
            'username' => $this->_config['login'],
            'password' => md5($this->_config['password']) //could lithify this, but it's 2am, and my eyes are fucked.
        );
        
        $result = $this->connection->post($this->_config['basePath'], $params);
        return simplexml_load_string($result);
        
    }
    
    /**
     * We're overriding cast so that we can put each array into a document object
     * in our documentset.
     * 
     * @param object $entity
     * @param array $data
     * @param array $options
     * @return type 
     */
    public function cast($entity, array $data, array $options = array()) {
        $model = $entity->model();
        foreach ($data as $key => $val) {
            if (!is_array($val)) {
                continue;
            }
            $data[$key] = $this->item($model, $val, array('class' => 'entity'));
        }
        return parent::cast($entity, $data, $options);
    }

    /**
     * Nast nast nast method, I hate it. It essential converts a simplexml object
     * into an array so that it's compatible with $this->item(); 
     * 
     * @param type $obj
     * @param type $arr
     * @return type 
     */
    protected function objToArray($obj, &$arr) {
        $children = $obj->children();
        $executed = false;
        foreach ($children as $index => $node) {
            if (array_key_exists($index, (array) $arr)) {
                if (array_key_exists(0, $arr[$index])) {
                    $i = count($arr[$index]);
                    $this->objToArray($node, $arr[$index][$i]);
                } else {
                    $tmp = $arr[$index];
                    $arr[$index] = array();
                    $arr[$index][0] = $tmp;
                    $i = count($arr[$index]);
                    $this->objToArray($node, $arr[$index][$i]);
                }
            } else {
                $arr[$index] = array();
                $this->objToArray($node, $arr[$index]);
            }

            $attributes = $node->attributes();
            if (count($attributes) > 0) {
                $arr[$index]['@attributes'] = array();
                foreach ($attributes as $attr_name => $attr_value) {
                    $attr_index = strtolower(trim((string) $attr_name));
                    $arr[$index]['@attributes'][$attr_index] = trim((string) $attr_value);
                }
            }

            $executed = true;
        }
        if (!$executed && $children->getName() == "") {
            $arr = (String) $obj;
        }

        return;
    }

}