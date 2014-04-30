<?php
/**
 * Created by PhpStorm.
 * Date: 27/01/14
 * Time: 18.57
 *
 */

/**
 * Abstract Class controller
 */
abstract class controller {

    /**
     * Controllers Factory
     *
     * Initialize the Controller Instance and route the
     * API Calls to the right Controller
     *
     * @return mixed
     */
    public static function getInstance() {

        if( isset( $_REQUEST['api'] ) && filter_input( INPUT_GET, 'api', FILTER_VALIDATE_BOOLEAN ) ){

            if( !isset( $_REQUEST['action'] ) || empty( $_REQUEST['action'] ) ){
                header( "HTTP/1.1 200 OK" );
                echo "OK";
                die();
            }

            $_REQUEST[ 'action' ][0] = strtoupper( $_REQUEST[ 'action' ][ 0 ] );

            //PHP 5.2 compatibility, don't use a lambda function
            $func                 = create_function( '$c', 'return strtoupper($c[1]);' );

            $_REQUEST[ 'action' ] = preg_replace_callback( '/_([a-z])/', $func, $_REQUEST[ 'action' ] );
            $_POST[ 'action' ]    = $_REQUEST[ 'action' ];

            //set the log to the API Log
            Log::$fileName = 'API.log';

        }

        //Default :  catController
        $action = ( isset( $_POST[ 'action' ] ) ) ? $_POST[ 'action' ] : ( isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : 'cat' );
        $className = $action . "Controller";
        return new $className();

    }

    /**
     * When Called it perform the controller action to retrieve/manipulate data
     *
     * @return mixed
     */
    abstract function doAction();

    /**
     * Called to get the result values in the right format
     *
     * @return mixed
     */
    abstract function finalize();

    /**
     * Set No Cache headers
     *
     */
    protected function nocache() {
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * Class constructor
     *
     */
    protected function __construct() {

        try {

            //Put here all actions we want to be performed by ALL controllers

            require_once INIT::$MODEL_ROOT . '/queries.php';
            require_once INIT::$UTILS_ROOT . '/AuthCookie.php';

        } catch ( Exception $e ) {
            echo "<pre>";
            print_r($e);
            echo "\n\n\n";
            echo "</pre>";
            exit;
        }

    }

    /**
     * Get the values from GET OR POST global Vars
     *
     * @deprecated
     *
     * @param $varname
     *
     * @return null
     */
    protected function get_from_get_post($varname) {
        $ret = null;
        $ret = isset($_GET[$varname]) ? $_GET[$varname] : (isset($_POST[$varname]) ? $_POST[$varname] : null);
        return $ret;
    }

}