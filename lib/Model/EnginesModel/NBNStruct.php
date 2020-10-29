<?php

/**
 * Class Engine_NBNStruct
 *
 * This class contains the default parameters for a NBN Engine CREATION
 *
 */
class EnginesModel_NBNStruct extends EnginesModel_EngineStruct {

    /**
     * @var string
     */
    public $description = "NBN";

    /**
     * @var string
     */
    public $base_url = "https://mtapi.mice.crosslang.com";

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
        'engine' => "nbn",
        'wso2app'=> "nbn",
        'projectname' => ""
    );

    /**
     * @var string
     */
    public $class_load = Constants_Engines::NBN;


    /**
     * @var int
     */
    public $google_api_compliant_version = 2;

    /**
     * An empty struct
     * @return EnginesModel_EngineStruct
     */
    public static function getStruct() {
        return new EnginesModel_NBNStruct();
    }

}