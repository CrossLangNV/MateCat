<?php

use RedisHandler;

class Engines_Judicio extends Engines_AbstractEngine {

    const Judicio_USER_AGENT = 'Judicio.MatecatPlugin/1.0.0';

    protected $_config = array(
            'segment'       => null,
            'translation'   => null,
            'source'        => null,
            'target'        => null,
            'id_user'       => null
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
        if ( is_string( $rawValue ) ) {
            $decoded = array(
                    'data' => array(
                            "translations" => array(
                                    array( 'translatedText' => $this->_resetSpecialStrings( $rawValue ) )
                            )
                    )
            );
        } else {
            $decoded = array(
                'error' => array(
                    'code' => '-1',
                    'message' => ''
                )
            );
        }

        $mt_result = new Engines_Results_MT($decoded);
        if ($mt_result->error->code < 0) {
            $mt_result = $mt_result->get_as_array();
            $mt_result['error'] = (array)$mt_result['error'];
            return $mt_result;
        }
        $mt_match_res = new Engines_Results_MyMemory_Matches(
            $this->_preserveSpecialStrings($parameters['text']),
            $mt_result->translatedText,
            100 - $this->getPenalty() . "%",
            "MT-" . $this->getName(),
            date("Y-m-d")
        );
        $mt_res = $mt_match_res->getMatches();
        return $mt_res;
    }

    public function get($_config) {
        $_config['segment'] = $this->_preserveSpecialStrings($_config['segment']);
        $_config['source'] = $this->_fixLangCode($_config['source']);
        $_config['target'] = $this->_fixLangCode($_config['target']);

        $_headers = array('wso2-app: ' . $this->wso2app, 'Authorization: Basic ' . base64_encode($this->auth), 'Content-Type: application/x-www-form-urlencoded');

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
        // get latest MT translation
        $mtResult = $this->get($_config);
        $mtTranslation = $mtResult['translation'];

        $_config['segment'] = $this->_preserveSpecialStrings($_config['segment']);
        $_config['translation'] = $this->_preserveSpecialStrings($_config['translation']);
        $_config['source'] = $this->_fixLangCode($_config['source']);
        $_config['target'] = $this->_fixLangCode($_config['target']);
        
        $parameters = array(
            'langpair'      => $_config['source'] . '-' . $_config['target'],
            'seg'           => $_config['segment'],
            'tra'           => $_config['translation'],
            'hyp'           => $mtTranslation
        );

        if ( !empty( $_config[ 'keys' ] ) ) {
            if ( !is_array( $_config[ 'keys' ] ) ) {
                $_config[ 'keys' ] = [ $_config[ 'keys' ] ];
            }
            $parameters[ 'key' ] = implode( ",", $_config[ 'keys' ] );
        }

        $this->_setJudicioUserAgent(); //Set Judicio User Agent

        $this->_setAdditionalCurlParams(
            array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($parameters)
            )
        );

        $this->callTM("contribute_relative_url", $parameters, true);

        return $this->result;
    }

    public function update($config) {

        //if engine does not implement UPDATE method, exit
        return true;
    }

    public function delete($_config) {

        //if engine does not implement DELETE method, exit
        return true;

    }

    private function callTM( $relativeUrl, Array $parameters = array(), $isPostRequest = false, $isJsonRequest = false) {
        $engineDAO = new EnginesModel_EngineDAO(Database::obtain(INIT::$DB_SERVER, INIT::$DB_USER, INIT::$DB_PASS, INIT::$DB_DATABASE ));
        $engine_struct = EnginesModel_EngineStruct::getStruct();
        $engine_struct->id = 1;
        $eng = $engineDAO->read($engine_struct);
        $engine_mouse = new Engines_MyMemory($eng[0]);
        // change base_url to the base_url of the TM
        $this->engineRecord->base_url = $engine_mouse->engineRecord->base_url;

        $this->call($relativeUrl, $parameters, $isPostRequest, $isJsonRequest);
    }

    /**
     *  Set Matecat + Judicio user agent
     */
    private function _setJudicioUserAgent() {
        $this->curl_additional_params[CURLOPT_USERAGENT] = self::Judicio_USER_AGENT . ' ' . INIT::MATECAT_USER_AGENT . INIT::$BUILD_NUMBER;
    }
}