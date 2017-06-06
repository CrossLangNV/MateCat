<?php

namespace Features\Dqf\Task ;

use Exception;
use INIT;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DqfAttributesDumpTask extends Command {

    protected function configure() {
        $this
                ->setName('dqf:attributes:dump')
                ->setDescription('Dumps to file various DQF attrbutes') ;


    }

    public function execute( InputInterface $input, OutputInterface $output ) {

        $map = [
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/catTool', 'path'  =>  'catTool.json' ],
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/contentType', 'path'  =>  'contentType.json' ],
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/errorCategory', 'path'  =>  'errorCategory.json' ],
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/industry', 'path'  =>  'industry.json' ],
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/language', 'path'  =>  'language.json' ],
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/mtEngine', 'path'  =>  'mtEngine.json' ],
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/process', 'path'  =>  'process.json' ],
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/qualitylevel', 'path'  =>  'qualitylevel.json' ],
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/segmentOrigin', 'path'  =>  'segmentOrigin.json' ],
                [ 'url'   =>  'http://dqf-api.ta-us.net/v3/severity', 'path'  =>  'severity.json' ],
        ];

        $output->writeln("Dumping data" ) ;

        foreach( $map as $attribute ) {
            $content = file_get_contents( $attribute['url'] );
            $path = INIT::$ROOT . '/inc/dqf/' . $attribute[ 'path' ];
            $output->writeln( $attribute['url'] . ' -> ' . $path );
            $json_content = json_decode( $content, true );

            if ( is_null( $json_content ) ) {
                throw new Exception('cannot convert content to json' . var_export( $content, true ) ) ;
            }

            $formatted_content = json_encode( $json_content, JSON_PRETTY_PRINT );

            $write = file_put_contents( $path , $formatted_content );
        }

    }

}
