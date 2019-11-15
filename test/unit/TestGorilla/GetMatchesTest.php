<?php

/**
 * @group  regression
 * @covers Engines_MyMemory::get
 * User: dinies
 * Date: 28/04/16
 * Time: 16.12
 */
class GetMatchesTest extends AbstractTest {

    /**
     * @var EnginesModel_EngineStruct
     */
    protected $engine_struct_param;

    /**
     * @var array
     */
    protected $others_param;
    /**
     * @var Engines_MyMemory
     */
    protected $engine_MyMemory;
    /**
     * @var array
     */
    protected $config_param_of_get;

    protected $reflector;


    public function setUp() {
        parent::setUp();

        $engineDAO         = new EnginesModel_EngineDAO( Database::obtain( INIT::$DB_SERVER, INIT::$DB_USER, INIT::$DB_PASS, INIT::$DB_DATABASE ) );
        $engine_struct     = EnginesModel_EngineStruct::getStruct();
        $engine_struct->id = 1;
        $eng               = $engineDAO->read( $engine_struct );

        /**
         * @var $engineRecord EnginesModel_EngineStruct
         */
        $this->engine_struct_param = @$eng[ 0 ];


        $this->config_param_of_get = [
                'translation'   => "",
                'tnote'         => null,
                'source'        => "en-US",
                'target'        => "fr-FR",
                'email'         => null,
                'prop'          => null,
                'get_mt'        => null,
                'id_user'       => null,
                'num_result'    => null,
                'mt_only'       => null,
                'isConcordance' => null,
                'isGlossary'    => null,
        ];


    }

    /**
     * @group  regression
     * @covers Engines_MyMemory::get
     */
    public function test_get_matches() {

        $this->engine_MyMemory = new Engines_MyMemory( $this->engine_struct_param );

        $this->config_param_of_get[ 'segment' ] = "This is a test.";

        $result = $this->engine_MyMemory->get( $this->config_param_of_get );
        $this->assertEquals( 200, $result->responseStatus );
        $this->assertEquals( "", $result->responseDetails );
        $this->assertCount( 2, $result->responseData );
        $this->assertTrue( $result instanceof Engines_Results_MyMemory_TMS );

        $this->reflector = new ReflectionClass( $result );
        $property        = $this->reflector->getProperty( '_rawResponse' );
        $property->setAccessible( true );

        $this->assertEquals( "", $property->getValue( $result ) );

    }

    /**
     * @group  regression
     * @covers Engines_MyMemory::get
     */
    public function test_with_existing_translation_QE() {

        $this->engine_MyMemory = new Engines_MyMemory( $this->engine_struct_param );

        $this->config_param_of_get[ 'segment' ] = "This is a test.";
        $this->config_param_of_get[ 'translation' ] = "C'est un test.";

        $result = $this->engine_MyMemory->get( $this->config_param_of_get );
        $this->assertEquals( 200, $result->responseStatus );
        $this->assertEquals( "", $result->responseDetails );
        $this->assertCount( 2, $result->responseData );
        $this->assertTrue( $result instanceof Engines_Results_MyMemory_TMS );

        // QE specific checks
        $this->assertGreaterThanOrEqual( 2, $result->matches );
        $this->assertTrue( $result->matches[0] instanceof Engines_Results_MyMemory_Matches );
        $this->assertEquals( "0", $result->matches[0]->getMatches()['id'] );
        $this->assertEquals( "QE existing translation", $result->matches[0]->getMatches()['created_by'] );
        $this->assertEquals( "100%", $result->matches[0]->getMatches()['match']);

        $this->reflector = new ReflectionClass( $result );
        $property        = $this->reflector->getProperty( '_rawResponse' );
        $property->setAccessible( true );

        $this->assertEquals( "", $property->getValue( $result ) );

    }

    /**
     * Test that verified the behaviour of a get request for the translation
     * of a segment given personal tm with respective id_user.
     * @group  regression
     * @covers Engines_MyMemory::get
     */
    public function test_get_matches_with_id_user_initialized() {

        $this->engine_MyMemory = new Engines_MyMemory( $this->engine_struct_param );


        $this->config_param_of_get[ 'segment' ] = "This is a test.";
        $this->config_param_of_get[ 'id_user' ] = "somerandomkey";

        $result = $this->engine_MyMemory->get( $this->config_param_of_get );

        /**
         * general check on the keys of TSM object returned
         */
        $this->assertTrue( $result instanceof Engines_Results_MyMemory_TMS );
        $this->assertTrue( property_exists( $result, 'matches' ) );
        $this->assertTrue( property_exists( $result, 'responseStatus' ) );
        $this->assertTrue( property_exists( $result, 'responseDetails' ) );
        $this->assertTrue( property_exists( $result, 'responseData' ) );
        $this->assertTrue( property_exists( $result, 'error' ) );
        $this->assertTrue( property_exists( $result, '_rawResponse' ) );

    }

    /**
     * It tests the behaviour with the return of the inner method _call simulated by a mock object.
     * This test certificates the righteousness of code without involving the _call method.
     * @group  regression
     * @covers Engines_MyMemory::get
     */
    public function test_get_segment_with_mock_for__call0() {

        $this->config_param_of_get[ 'segment' ] = "This is a test.";

        $curl_mock_param = [
                CURLOPT_POSTFIELDS  =>
                        [
                                'q'             => 'This is a test.',
                                'langpair'      => 'en-US|fr-FR',
                                'translation'   => '',
                                'de'            => null,
                                'mt'            => null,
                                'numres'        => null
                        ],
                CURLINFO_HEADER_OUT => true,
                CURLOPT_TIMEOUT     => 120
        ];

        $url_mock_param   = "http://gorilla:8081/get";
        $mock_json_return = <<<'TAB'
{"responseData":{"translatedText":null,"match":null},"responseDetails":"","responseStatus":200,"responderId":null,"matches":[]}
TAB;

        /**
         * @var Engines_MyMemory
         */
        $this->engine_MyMemory = $this->getMockBuilder( '\Engines_MyMemory' )->setConstructorArgs( [ $this->engine_struct_param ] )->setMethods( [ '_call' ] )->getMock();
        $this->engine_MyMemory->expects( $this->exactly( 1 ) )->method( '_call' )->with( $url_mock_param, $curl_mock_param )->willReturn( $mock_json_return );

        $result = $this->engine_MyMemory->get( $this->config_param_of_get );
        /**
         * general check on the keys of TSM object returned
         */
        $this->assertTrue( $result instanceof Engines_Results_MyMemory_TMS );
        $this->assertTrue( property_exists( $result, 'matches' ) );
        $this->assertTrue( property_exists( $result, 'responseStatus' ) );
        $this->assertTrue( property_exists( $result, 'responseDetails' ) );
        $this->assertTrue( property_exists( $result, 'responseData' ) );
        $this->assertTrue( property_exists( $result, 'error' ) );
        $this->assertTrue( property_exists( $result, '_rawResponse' ) );
        /**
         * specific check
         */
        $this->assertEquals( [], $result->matches );
        $this->assertEquals( 200, $result->responseStatus );
        $this->assertEquals( "", $result->responseDetails );
        $this->assertCount( 2, $result->responseData );
        $this->assertNull( $result->error );
        $this->assertNull( $result->responseData[ 'translatedText' ] );
        $this->assertNull( $result->responseData[ 'match' ] );
        $this->reflector = new ReflectionClass( $result );
        $property        = $this->reflector->getProperty( '_rawResponse' );
        $property->setAccessible( true );

        $this->assertEquals( "", $property->getValue( $result ) );

    }

    /**
     * @group  regression
     * @covers Engines_MyMemory::get
     */
    public function test_get_with_error_from_mocked__call() {

        $this->config_param_of_get[ 'segment' ] = "This is a test.";

        $curl_mock_param = [
                CURLOPT_POSTFIELDS  =>
                        [
                                'q'             => 'This is a test.',
                                'langpair'      => 'en-US|fr-FR',
                                'translation'   => '',
                                'de'            => null,
                                'mt'            => null,
                                'numres'        => null
                        ],
                CURLINFO_HEADER_OUT => true,
                CURLOPT_TIMEOUT     => 120
        ];

        $url_mock_param   = "http://gorilla:8081/get";

        $rawValue_error = [
                'error'          => [
                        'code'     => -6,
                        'message'  => "Could not resolve host: gorilla. Server Not Available (http status 0)",
                        'response' => "",
                ],
                'responseStatus' => 0
        ];


        /**
         * @var Engines_MyMemory
         */
        $this->engine_MyMemory = $this->getMockBuilder( '\Engines_MyMemory' )->setConstructorArgs( [ $this->engine_struct_param ] )->setMethods( [ '_call' ] )->getMock();
        $this->engine_MyMemory->expects( $this->once() )->method( '_call' )->with( $url_mock_param, $curl_mock_param )->willReturn( $rawValue_error );

        $result = $this->engine_MyMemory->get( $this->config_param_of_get );
        /**
         * general check on the keys of TSM object returned
         */
        $this->assertTrue( $result instanceof Engines_Results_MyMemory_TMS );
        $this->assertTrue( property_exists( $result, 'matches' ) );
        $this->assertTrue( property_exists( $result, 'responseStatus' ) );
        $this->assertTrue( property_exists( $result, 'responseDetails' ) );
        $this->assertTrue( property_exists( $result, 'responseData' ) );
        $this->assertTrue( property_exists( $result, 'error' ) );
        $this->assertTrue( property_exists( $result, '_rawResponse' ) );
        /**
         * specific check
         */
        $this->assertEquals( [], $result->matches );
        $this->assertEquals( 0, $result->responseStatus );
        $this->assertEquals( "", $result->responseDetails );
        $this->assertEquals( "", $result->responseData );
        $this->assertTrue( $result->error instanceof Engines_Results_ErrorMatches );

        $this->assertEquals( -6, $result->error->code );
        $this->assertEquals( "Could not resolve host: gorilla. Server Not Available (http status 0)", $result->error->message );

        $this->reflector = new ReflectionClass( $result );
        $property        = $this->reflector->getProperty( '_rawResponse' );
        $property->setAccessible( true );

        $this->assertEquals( "", $property->getValue( $result ) );

    }

}