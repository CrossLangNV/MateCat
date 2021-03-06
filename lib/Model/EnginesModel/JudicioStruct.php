<?php

/**
 * Class Engine_JudicioStruct
 *
 * This class contains the default parameters for a Judicio Engine CREATION
 *
 */
class EnginesModel_JudicioStruct extends EnginesModel_EngineStruct {

    /**
     * @var string
     */
    public $description = "Judicio";

    /**
     * @var string
     */
    public $base_url = "https://portal.staging.judic.io/translations";

    /**
     * @var string
     */
    public $translate_relative_url = "/translations/blocking";

    /**
     * @var string
     */
    public $contribute_relative_url = "/hypothesis";

    /**
     * @var array
     */
    public $extra_parameters = array(
        'engine' => "",
        'wso2app'=> "",
        'projectname' => "",
        'auth' => ""
    );

    /**
     * @var string
     */
    public $class_load = Constants_Engines::JUDICIO;


    /**
     * @var int
     */
    public $google_api_compliant_version = 2;

    /**
     * An empty struct
     * @return EnginesModel_EngineStruct
     */
    public static function getStruct() {
        return new EnginesModel_JudicioStruct();
    }

}