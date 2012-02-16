# WHMCS Library for the lithium framework.

# Note: 
This library is far from finished and only a few API methods actually work at this. If you'd like an API method to be implemented before I get around to completing this libaray just send me a message!

## Install
Checkout as a submodule into app/libraries.

## Usage

### Load the library:

File: app/config/bootstrap/libraries.php.
<pre>Libraries::add('li3_whmcs');</pre>

### Add a connection like so to your bootstrap:

File: app/config/bootstrap/connections.php.
<pre>Connections::add('whmcs',array(
    'type' => 'http',
    'adapter' => 'Whmcs',
    'login' => '<username>',
    'host' => '<url>',
    'port' => 80,
    'password' => '<password>',
));</pre>

### Example:

<pre>namespace app\controllers;

use li3_whmcs\models\Products;

class WhmcsTestController extends \lithium\action\Controller {
    
    public function products(){
        $results = Products::find('all', array(
            'conditions' => array (
                'gid' => 1
            )
        ));

        foreach($results as $result){
            var_dump($result['name']);
        }
    }
    
}</pre>