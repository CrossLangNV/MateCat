<?php

class changeJobsStatusController extends ajaxController {

    private $res_type;
    private $res_id;
    private $new_status = Constants_JobStatus::STATUS_ACTIVE;
    private $password = "fake wrong password";

    public function __construct() {

        //SESSION START
        parent::__construct();
        parent::readLoginInfo();

        $filterArgs = array(
                'res'           => [
                        'filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ],
                'id'            => [ 'filter' => FILTER_SANITIZE_NUMBER_INT ],
                'password'      => [
                        'filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ],
                'new_status'    => [
                        'filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ],
                'pn'            => [ 'filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_LOW ],

        );

        $postInput = filter_input_array( INPUT_POST, $filterArgs );

        ( !empty( $postInput[ 'password' ] ) ? $this->password = $postInput[ 'password' ] : null );

        $this->res_type   = $postInput[ 'res' ];
        $this->res_id     = $postInput[ 'id' ];

        if ( Constants_JobStatus::isAllowedStatus( $postInput[ 'new_status' ] ) ) {
            $this->new_status = $postInput[ 'new_status' ];
        } else {
            throw new Exception( "Invalid Status" );
        }

    }

   public function doAction() {

        if ( ! $this->userIsLogged ) {
            throw new Exception( "User Not Logged." );
        }

        if ( $this->res_type == "prj" ) {

            $pCheck = new AjaxPasswordCheck();
            $projectData = getProjectJobData( $this->res_id );
            $access = $pCheck->grantProjectAccess( $projectData, $this->password );

            //check for Password correctness
            if ( !$access ) {
                $msg = "Error : wrong password provided for Change Project Status \n\n " . var_export( $_POST, true ) . "\n";
                Log::doJsonLog( $msg );
                Utils::sendErrMailReport( $msg );
                return null;
            }

            $strOld     = '';
            foreach ( $projectData as $item ) {
                $strOld .= $item[ 'id' ] . ':' . $item[ 'status_owner' ] . ',';
            }
            $strOld = trim( $strOld, ',' );

            $this->result[ 'old_status' ] = $strOld;

            updateJobsStatus( $this->res_type, $this->res_id, $this->new_status );

            $this->result[ 'code' ]    = 1;
            $this->result[ 'data' ]    = "OK";
            $this->result[ 'status' ]  = $this->new_status;

        } else {

            updateJobsStatus( $this->res_type, $this->res_id, $this->new_status, $this->password );

            $this->result[ 'code' ]   = 1;
            $this->result[ 'data' ]   = "OK";
            $this->result[ 'status' ] = $this->new_status;

            // purge ActiveMQ analysis queues if job was cancelled
            if ( $this->new_status == 'cancelled' ) {
                $curl = new MultiCurlHandler();
                $options = array(
                    CURLOPT_HEADER         => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_USERAGENT      => INIT::MATECAT_USER_AGENT . INIT::$BUILD_NUMBER,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_HTTPHEADER     => array( 'Authorization: Basic ' . base64_encode( "admin:admin" ) )
                );
                $curl->createResource(
                    INIT::$QUEUE_JMX_ADDRESS . "/api/jolokia/exec/org.apache.activemq:type=Broker,brokerName=localhost,destinationType=Queue,destinationName=0_analysis_queue_P1/purge",
                    $options
                );
                $curl->createResource(
                    INIT::$QUEUE_JMX_ADDRESS . "/api/jolokia/exec/org.apache.activemq:type=Broker,brokerName=localhost,destinationType=Queue,destinationName=0_analysis_queue_P2/purge",
                    $options
                );
                $curl->createResource(
                    INIT::$QUEUE_JMX_ADDRESS . "/api/jolokia/exec/org.apache.activemq:type=Broker,brokerName=localhost,destinationType=Queue,destinationName=0_analysis_queue_P3/purge",
                    $options
                );
                $curl->multiExec();
            }
        }
    }

}
