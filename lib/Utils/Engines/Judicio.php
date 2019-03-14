<?php

use RedisHandler;

class Engines_Judicio extends Engines_AbstractEngine {

    const Judicio_USER_AGENT = 'Judicio.MatecatPlugin/1.0.0';

    protected $_config = array(
            'segment' => null,
            'source'  => null,
            'target'  => null
        );

    public function __construct($engineRecord) {
        parent::__construct($engineRecord);
        if ($this->engineRecord->type != "MT") {
            throw new Exception("Engine {$this->engineRecord->id} is not a MT engine, found {$this->engineRecord->type} -> {$this->engineRecord->class_load}");
        }
    }

    /**
     * @param $lang
     *
     * @return mixed
     * @throws Exception
     */
    protected function _fixLangCode($lang) {
        $r = explode("-", strtolower(trim($lang)));

        return $r[0];
    }

    /**
     * @param $rawValue
     *
     * @return array
     */
    protected function _decode($rawValue, $parameters = null, $function = null) {

        $all_args =  func_get_args();
        $all_args[ 1 ][ 'text' ] = $all_args[ 1 ][ 'text' ];

        if ( is_string( $rawValue ) ) {
            $decoded = json_decode( $rawValue, true );
            if ( isset( $decoded[ "data" ] ) ) {
                return $this->_composeResponseAsMatch( $all_args, $decoded );
            } else {
                $decoded = [
                        'error' => [
                                'code'    => $decoded[ "code" ],
                                'message' => $decoded[ "message" ]
                        ]
                ];
            }
        } else {
            $resp = json_decode( $rawValue[ "error" ][ "response" ], true );
            if ( isset( $resp[ "error" ][ "code" ] ) && isset( $resp[ "error" ][ "message" ] ) ) {
                $rawValue[ "error" ][ "code" ]    = $resp[ "error" ][ "code" ];
                $rawValue[ "error" ][ "message" ] = $resp[ "error" ][ "message" ];
            }
            $decoded = $rawValue; // already decoded in case of error
        }

        return $decoded;

    }

    public function get($_config) {
        $_config['segment'] = $this->_preserveSpecialStrings($_config['segment']);
        $_config['source'] = $this->_fixLangCode($_config['source']);
        $_config['target'] = $this->_fixLangCode($_config['target']);

        $_headers = array('wso2-app: ' . $this->wso2app, 'Content-Type: application/x-www-form-urlencoded');

        $parameters = array(
            'lp'            => $_config['source'] . '-' . $_config['target'],
            'text'          => $_config['segment'],
            'engine'        => $this->engine,
            'projectname'   => $this->projectname
        );

        $this->_setJudicioUserAgent(); //Set Judicio User Agent

        $this->_setAdditionalCurlParams(
            array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($parameters),
                CURLOPT_HTTPHEADER => $_headers
            )
        );

        $this->call("translate_relative_url", $parameters, true);

        return $this->result;

    }

    public function set($_config) {

        //if engine does not implement SET method, exit
        return true;
    }

    public function update($config) {

        //if engine does not implement UPDATE method, exit
        return true;
    }

    public function delete($_config) {

        //if engine does not implement DELETE method, exit
        return true;

    }

    /**
     *  Set Matecat + Judicio user agent
     */
    private function _setJudicioUserAgent() {
        $this->curl_additional_params[CURLOPT_USERAGENT] = self::Judicio_USER_AGENT . ' ' . INIT::MATECAT_USER_AGENT . INIT::$BUILD_NUMBER;
    }
}