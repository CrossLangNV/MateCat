<?php

namespace API\V2;

use API\V2\Json\SegmentVersion as JsonFormatter;
use API\V2\Validators\JobPasswordValidator;
use Jobs_JobStruct;
use INIT;
use Log;
use FilesStorage;
use SdlXliffSAXTranslationReplacer;


class SegmentsXliffController extends KleinController {

    /**
     * @var Jobs_JobStruct
     */
    protected $jStruct;

    const FILES_CHUNK_SIZE = 3;

    public function index() {

        $this->params = filter_var_array( $this->params, [
            'id_job'        => [ 'filter' => FILTER_SANITIZE_NUMBER_INT ],
            'password'      => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK
            ]
        ], true );

        $this->project = $this->jStruct->getProject();

        $this->featureSet->loadForProject( $this->project );

        //get storage object
        $fs        = new FilesStorage();
        // file id param can be left empty
        $files_job = $fs->getFilesForJob( $this->jStruct->id, "" );

        $nonew          = 0;
        $output_content = [];


        /*
           the procedure:
           1)original xliff file is read directly from disk; a file handler is obtained
           2)the file is read chunk by chunk by a stream parser: for each trans-unit that is encountered, target is replaced (or added) with the corresponding translation obtained from the DB
           3)the parsed portion of xliff in the buffer is flushed on temporary file
           4)the temporary file is sent to the converter and an original file is obtained
           5)the temporary file is deleted
         */

        //file array is chuncked. Each chunk will be used for a parallel conversion request.
        $files_job = array_chunk( $files_job, self::FILES_CHUNK_SIZE );
        foreach ( $files_job as $chunk ) {

            $files_to_be_converted = [];

            foreach ( $chunk as $file ) {

                $mime_type        = $file[ 'mime_type' ];
                $fileID           = $file[ 'id_file' ];
                $current_filename = $file[ 'filename' ];

                //get path for the output file converted to know it's right extension
                $_fileName  = explode( DIRECTORY_SEPARATOR, $file[ 'xliffFilePath' ] );
                $outputPath = INIT::$TMP_DOWNLOAD . '/' . $this->jStruct->id . '/' . $fileID . '/' . uniqid( '', true ) . "_.out." . array_pop( $_fileName );

                //make dir if doesn't exist
                if ( !file_exists( dirname( $outputPath ) ) ) {

                    Log::doJsonLog( 'Create Directory ' . escapeshellarg( dirname( $outputPath ) ) . '' );
                    mkdir( dirname( $outputPath ), 0775, true );

                }

                $data = getSegmentsDownload( $this->jStruct->id, $this->jStruct->password, $fileID, $nonew );

                $transUnits = [];

                foreach ( $data as $i => $k ) {
                    //create a secondary indexing mechanism on segments' array; this will be useful
                    //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
                    $internalId = $k[ 'internal_id' ];

                    $transUnits[ $internalId ] [] = $i;

                    $data[ 'matecat|' . $internalId ] [] = $i;

                }


                /**
                 * Because of a bug in the filters for the cjk languages ( Exception when downloading translations )
                 * we add an hook to allow some plugins to force the conversion parameters ( languages for example )
                 * TODO: ( 25/05/2018 ) Remove when the issue will be fixed
                 */
                $_target_lang = $this->featureSet->filter(
                        'changeXliffTargetLangCode',
                        $jobData[ 'target' ]
                        , $file[ 'xliffFilePath' ]
                );


                //instatiate parser
                $xsp = new SdlXliffSAXTranslationReplacer( $file[ 'xliffFilePath' ], $data, $transUnits, $_target_lang, $outputPath );

                //run parsing
                Log::doJsonLog( "work on " . $fileID . " " . $current_filename );
                $xsp->replaceTranslation( $this->featureSet );

                //free memory
                unset( $xsp );
                unset( $data );

                $output_content[ $fileID ][ 'document_content' ] = file_get_contents( $outputPath );
                $output_content[ $fileID ][ 'output_filename' ]  = $current_filename;
            }
        }

        $this->response->json(
                [
                        'job' => [
                                'id'                => $this->jStruct->id,
                                'password'          => $this->jStruct->password,
                                'output_content'    => $output_content
                        ]
                ]
        );

    }

    protected function afterConstruct() {
        $validJob = new JobPasswordValidator( $this );
        $this->jStruct = $validJob->getJob();
        $this->appendValidator( $validJob );
    }

}