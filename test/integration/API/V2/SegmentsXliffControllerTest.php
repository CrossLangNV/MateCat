<?php

require 'lib/Controller/API/V2/Validators/JobPasswordValidator.php';



class SegmentsXliffControllerTest extends IntegrationTest {

    private $test_data = array();

    function setup() {
        
        $this->test_data = new stdClass();
        $this->test_data->user = Factory_User::create();

        $feature = Factory_OwnerFeature::create( array(
            'uid'          => $this->test_data->user->uid,
            'feature_code' => Features::TRANSLATION_VERSIONS
        ) );

        $this->test_data->api_key = Factory_ApiKey::create( array(
            'uid' => $this->test_data->user->uid,
        ) );

        $this->test_data->headers = array(
            "X-MATECAT-KEY: {$this->test_data->api_key->api_key}",
            "X-MATECAT-SECRET: {$this->test_data->api_key->api_secret}"
        );
    }

    function test_xliff_with_dummy_translation() {

        $project = integrationCreateTestProject( array(
            'headers' => $this->test_data->headers
        ));

        $this->params = array(
            'id_project' => $project->id_project,
            'password' => $project->project_pass
        );

        $chunksDao = new Chunks_ChunkDao( Database::obtain() ) ;
        $chunks = $chunksDao->getByProjectID( $project->id_project );
        $chunk = $chunks[0];

        $this->assertTrue( count($chunks) == 1);

        $segments = $chunk->getSegments();
        $segment = $segments[0];

        $translations = $chunk->getTranslations();
        $translation = $translations[0];

        $test          = new CurlTest();
        $test->path    = sprintf("/api/v2/jobs/%s/%s/xliff", $chunk->id, $chunk->password );
        $test->method  = 'GET';
        $test->headers = $this->test_data->headers;

        $fs        = new FilesStorage();
        // file id param can be left empty
        $files_job = $fs->getFilesForJob( $chunk->id, "" );
        // in this test, only 1 file was uploaded
        $file_id   = $files_job[0][ 'id_file' ];

        $response = $test->getResponse();

        $expected_job_id = $chunk->id;
        $expected_job_password = $chunk->password;
        $expected_xliff = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><xliff xmlns=\"urn:oasis:names:tc:xliff:document:1.2\" xmlns:its=\"http://www.w3.org/2005/11/its\" " . 
            "xmlns:itsxlf=\"http://www.w3.org/ns/its-xliff/\" xmlns:okp=\"okapi-framework:xliff-extensions\" its:version=\"2.0\" version=\"1.2\"><file datatype=\"x-xlf\" " .
            "filter=\"com.matecat.filters.basefilters.DefaultFilter\" original=\"amex-test.docx.xlf\" source-language=\"en-US\" target-language=\"it-IT\" " . 
            "tool-id=\"matecat-converter 1.2.5\"><header><reference><internal-file " . 
            "form=\"base64\">PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHhsaWZmIHZlcnNpb249IjEuMiIgeG1sbnM9InVybjpvYXNpczpuYW1lczp0Yzp4bGlmZjpkb2N1bWVudDoxL" .
            "jIiIHhtbG5zOm9rcD0ib2thcGktZnJhbWV3b3JrOnhsaWZmLWV4dGVuc2lvbnMiIHhtbG5zOml0cz0iaHR0cDovL3d3dy53My5vcmcvMjAwNS8xMS9pdHMiIHhtbG5zOml0c3hsZj0iaHR0cDovL3d3dy" .
            "53My5vcmcvbnMvaXRzLXhsaWZmLyIgaXRzOnZlcnNpb249IjIuMCI+CjxmaWxlIG9yaWdpbmFsPSJ3b3JkL3N0eWxlcy54bWwiIHNvdXJjZS1sYW5ndWFnZT0iZW4tZW4iIHRhcmdldC1sYW5ndWFnZT0" .
            "iZW4tZW4iIGRhdGF0eXBlPSJ4LXVuZGVmaW5lZCI+Cjxib2R5Pgo8L2JvZHk+CjwvZmlsZT4KPGZpbGUgb3JpZ2luYWw9IndvcmQvZG9jdW1lbnQueG1sIiBzb3VyY2UtbGFuZ3VhZ2U9ImVuLWVuIiB0" .
            "YXJnZXQtbGFuZ3VhZ2U9ImVuLWVuIiBkYXRhdHlwZT0ieC11bmRlZmluZWQiPgo8Ym9keT4KPHRyYW5zLXVuaXQgaWQ9InR1MSIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+Cjxzb3VyY2UgeG1sOmxhbmc9I" .
            "mVuLWVuIj5BbWVyaWNhbiBFeHByZXNzwqA6IHBvdXIgZGVzIHJhaXNvbnMgaW5kw6lwZW5kYW50ZXMgZGUgbm90cmUgdm9sb250w6ksIG5vdXMgbmUgcG91cnJvbnMgZWZmZWN0dWVyIGxlIGTDqWJpdC" .
            "BxdeKAmWVuIEV1cm9zLiBNZXJjaSBkZSB2b3RyZSBjb21wcsOpaGVuc2lvbjx4IGlkPSIxIi8+PC9zb3VyY2U+CjxzZWctc291cmNlPjxtcmsgbWlkPSIwIiBtdHlwZT0ic2VnIj5BbWVyaWNhbiBFeHB" .
            "yZXNzwqA6PC9tcms+IDxtcmsgbWlkPSIxIiBtdHlwZT0ic2VnIj5wb3VyIGRlcyByYWlzb25zIGluZMOpcGVuZGFudGVzIGRlIG5vdHJlIHZvbG9udMOpLCBub3VzIG5lIHBvdXJyb25zIGVmZmVjdHVl" .
            "ciBsZSBkw6liaXQgcXXigJllbiBFdXJvcy48L21yaz4gPG1yayBtaWQ9IjIiIG10eXBlPSJzZWciPk1lcmNpIGRlIHZvdHJlIGNvbXByw6loZW5zaW9uPHggaWQ9IjEiLz48L21yaz48L3NlZy1zb3VyY" .
            "2U+Cjx0YXJnZXQgeG1sOmxhbmc9Iml0LWl0Ij48bXJrIG1pZD0iMCIgbXR5cGU9InNlZyI+QW1lcmljYW4gRXhwcmVzc8KgOjwvbXJrPgo8bXJrIG1pZD0iMSIgbXR5cGU9InNlZyI+cG91ciBkZXMgcm" .
            "Fpc29ucyBpbmTDqXBlbmRhbnRlcyBkZSBub3RyZSB2b2xvbnTDqSwgbm91cyBuZSBwb3Vycm9ucyBlZmZlY3R1ZXIgbGUgZMOpYml0IHF14oCZZW4gRXVyb3MuPC9tcms+IDxtcmsgbWlkPSIyIiBtdHl" .
            "wZT0ic2VnIj5NZXJjaSBkZSB2b3RyZSBjb21wcsOpaGVuc2lvbjx4IGlkPSIxIi8+PC9tcms+PC90YXJnZXQ+CjwvdHJhbnMtdW5pdD4KPC9ib2R5Pgo8L2ZpbGU+CjxmaWxlIG9yaWdpbmFsPSJ3b3Jk" .
            "L3NldHRpbmdzLnhtbCIgc291cmNlLWxhbmd1YWdlPSJlbi1lbiIgdGFyZ2V0LWxhbmd1YWdlPSJlbi1lbiIgZGF0YXR5cGU9IngtdW5kZWZpbmVkIj4KPGJvZHk+CjwvYm9keT4KPC9maWxlPgo8ZmlsZ" .
            "SBvcmlnaW5hbD0iZG9jUHJvcHMvY29yZS54bWwiIHNvdXJjZS1sYW5ndWFnZT0iZW4tZW4iIHRhcmdldC1sYW5ndWFnZT0iZW4tZW4iIGRhdGF0eXBlPSJ4LXVuZGVmaW5lZCI+Cjxib2R5Pgo8L2JvZH" .
            "k+CjwvZmlsZT4KPC94bGlmZj4K</internal-file></reference></header><body/></file><file datatype=\"x-rkm\" " . 
            "filter=\"com.matecat.filters.basefilters.DefaultFilter\" original=\"manifest.rkm\" source-language=\"en-US\" target-language=\"it-IT\" " . 
            "tool-id=\"matecat-converter 1.2.5\"><header><reference><internal-file form=\"base64\">PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPCEtLT09PT09PT0" .
            "9PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09LS0+CjwhLS1QTEVBU0UsIERPIE5PVCBSRU5BTUUsIE1PVkUsIE1PRElGWSBPUiBBTFRFUiBJTiBB" .
            "TlkgV0FZIFRISVMgRklMRS0tPgo8IS0tPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0tLT4KPG1hbmlmZXN0IHZlcnNpb249IjIiI" .
            "GxpYlZlcnNpb249IjEuMi41IiBwcm9qZWN0SWQ9Ik5FMkZFODZFNCIgcGFja2FnZUlkPSI5YTJmNGNjYS00YmM0LTRmNzEtYmRjNC1lOWU0MTNkN2M5ZjAiIHNvdXJjZT0iZW4tVVMiIHRhcmdldD0iaX" .
            "QtSVQiIG9yaWdpbmFsU3ViRGlyPSJvcmlnaW5hbCIgc2tlbGV0b25TdWJEaXI9InNrZWxldG9uIiBzb3VyY2VTdWJEaXI9IndvcmsiIHRhcmdldFN1YkRpcj0id29yayIgbWVyZ2VTdWJEaXI9ImRvbmU" .
            "iIHRtU3ViRGlyPSIiIGRhdGU9IjIwMTktMTEtMTkgMDg6NTY6MjArMDAwMCIgdXNlQXBwcm92ZWRPbmx5PSIwIiB1cGRhdGVBcHByb3ZlZEZsYWc9IjAiPgo8Y3JlYXRvclBhcmFtZXRlcnM+PC9jcmVh" .
            "dG9yUGFyYW1ldGVycz4KPGRvYyB4bWw6c3BhY2U9InByZXNlcnZlIiBkb2NJZD0iMSIgZXh0cmFjdGlvblR5cGU9InhsaWZmIiByZWxhdGl2ZUlucHV0UGF0aD0iYW1leC10ZXN0LmRvY3gueGxmIiBma" .
            "Wx0ZXJJZD0ib2tmX3hsaWZmIiBpbnB1dEVuY29kaW5nPSJVVEYtOCIgcmVsYXRpdmVUYXJnZXRQYXRoPSJhbWV4LXRlc3QuZG9jeC5vdXQueGxmIiB0YXJnZXRFbmNvZGluZz0iVVRGLTgiIHNlbGVjdG" .
            "VkPSIxIj5JM1l4Q25WelpVTjFjM1J2YlZCaGNuTmxjaTVpUFhSeWRXVUtabUZqZEc5eWVVTnNZWE56UFdOdmJTNWpkR011ZDNOMGVDNXpkR0Y0TGxkemRIaEpibkIxZEVaaFkzUnZjbmtLWm1Gc2JHSmh" .
            "ZMnRVYjBsRUxtSTlabUZzYzJVS1pYTmpZWEJsUjFRdVlqMTBjblZsQ21Ga1pGUmhjbWRsZEV4aGJtZDFZV2RsTG1JOWRISjFaUXB2ZG1WeWNtbGtaVlJoY21kbGRFeGhibWQxWVdkbExtSTlkSEoxWlFw" .
            "dmRYUndkWFJUWldkdFpXNTBZWFJwYjI1VWVYQmxMbWs5TUFwcFoyNXZjbVZKYm5CMWRGTmxaMjFsYm5SaGRHbHZiaTVpUFdaaGJITmxDbUZrWkVGc2RGUnlZVzV6TG1JOVptRnNjMlVLWVdSa1FXeDBWS" .
            "EpoYm5OSFRXOWtaUzVpUFhSeWRXVUtaV1JwZEVGc2RGUnlZVzV6TG1JOVptRnNjMlVLYVc1amJIVmtaVVY0ZEdWdWMybHZibk11WWoxMGNuVmxDbWx1WTJ4MVpHVkpkSE11WWoxMGNuVmxDbUpoYkdGdV" .
            "kyVkRiMlJsY3k1aVBYUnlkV1VLWVd4c2IzZEZiWEIwZVZSaGNtZGxkSE11WWoxbVlXeHpaUXAwWVhKblpYUlRkR0YwWlUxdlpHVXVhVDB3Q25SaGNtZGxkRk4wWVhSbFZtRnNkV1U5Ym1WbFpITXRkSEp" .
            "oYm5Oc1lYUnBiMjRLWVd4M1lYbHpWWE5sVTJWblUyOTFjbU5sTG1JOWRISjFaUXB4ZFc5MFpVMXZaR1ZFWldacGJtVmtMbUk5ZEhKMVpRcHhkVzkwWlUxdlpHVXVhVDB3Q25WelpWTmtiRmhzYVdabVYz" .
            "SnBkR1Z5TG1JOVptRnNjMlVLY0hKbGMyVnlkbVZUY0dGalpVSjVSR1ZtWVhWc2RDNWlQV1poYkhObENuVnpaVWwzYzFoc2FXWm1WM0pwZEdWeUxtSTlabUZzYzJVS2FYZHpRbXh2WTJ0R2FXNXBjMmhsW" .
            "kM1aVBYUnlkV1VLYVhkelZISmhibk5UZEdGMGRYTldZV3gxWlQxbWFXNXBjMmhsWkFwcGQzTlVjbUZ1YzFSNWNHVldZV3gxWlQxdFlXNTFZV3hmZEhKaGJuTnNZWFJwYjI0S2FYZHpRbXh2WTJ0TWIyTn" .
            "JVM1JoZEhWekxtSTlabUZzYzJVS2FYZHpRbXh2WTJ0VWJWTmpiM0psTG1JOVptRnNjMlVLYVhkelFteHZZMnRVYlZOamIzSmxWbUZzZFdVOU1UQXdMakF3Q21sM2MwSnNiMk5yVFhWc2RHbHdiR1ZGZUd" .
            "GamRDNWlQV1poYkhObENtbHViR2x1WlVOa1lYUmhMbUk5ZEhKMVpRcHphMmx3VG05TmNtdFRaV2RUYjNWeVkyVXVZajFtWVd4elpRcDFjMlZEYjJSbFJtbHVaR1Z5TG1JOVptRnNjMlVLWTI5a1pVWnBi" .
            "bVJsY2xKMWJHVnpMbU52ZFc1MExtazlNUXBqYjJSbFJtbHVaR1Z5VW5Wc1pYTXVjblZzWlRBOVBDOC9LRnRCTFZvd0xUbGhMWHBkS2lsY1lsdGVQbDBxUGdwamIyUmxSbWx1WkdWeVVuVnNaWE11YzJGd" .
            "GNHeGxQU1p1WVcxbE95QThkR0ZuUGp3dllYUStQSFJoWnk4K0lEeDBZV2NnWVhSMGNqMG5kbUZzSno0Z1BDOTBZV2M5SW5aaGJDSStDbU52WkdWR2FXNWtaWEpTZFd4bGN5NTFjMlZCYkd4U2RXeGxjMW" .
            "RvWlc1VVpYTjBhVzVuTG1JOWRISjFaUT09PC9kb2M+CjwvbWFuaWZlc3Q+</internal-file></reference></header><body/></file>\r\n<file datatype=\"x-undefined\" " . 
            "original=\"word/styles.xml\" source-language=\"en-US\" target-language=\"it-IT\">\r\n<body>\r\n</body>\r\n</file>\r\n<file datatype=\"x-undefined\" " . 
            "original=\"word/document.xml\" source-language=\"en-US\" target-language=\"it-IT\">\r\n<body>\r\n<trans-unit id=\"tu1\" xml:space=\"preserve\">\r\n" .
            "<source xml:lang=\"en-US\">American Express : pour des raisons indépendantes de notre volonté, nous ne pourrons effectuer le débit " . 
            "qu’en Euros. Merci de votre compréhension<x id=\"1\"/></source>\r\n<seg-source><mrk mid=\"0\" mtype=\"seg\">American Express :</mrk> " . 
            "<mrk mid=\"1\" mtype=\"seg\">pour des raisons indépendantes de notre volonté, nous ne pourrons effectuer le débit qu’en Euros.</mrk> " . 
            "<mrk mid=\"2\" mtype=\"seg\">Merci de votre compréhension<x id=\"1\"/></mrk></seg-source>\r\n<target xml:lang=\"it-IT\" state=\"new\">" .
            "<mrk mid=\"0\" mtype=\"seg\">AMERICAN EXPRESS :</mrk><mrk mid=\"1\" mtype=\"seg\">POUR DES RAISONS INDÉPENDANTES DE NOTRE VOLONTÉ, " . 
            "NOUS NE POURRONS EFFECTUER LE DÉBIT QU’EN EUROS.</mrk><mrk mid=\"2\" mtype=\"seg\">MERCI DE VOTRE COMPRÉHENSION<x id=\"1\"/></mrk></target>" .
            "\r\n</trans-unit>\r\n</body>\r\n</file>\r\n<file datatype=\"x-undefined\" original=\"word/settings.xml\" source-language=\"en-US\" target-language=\"it-IT\">" .
            "\r\n<body>\r\n</body>\r\n</file>\r\n<file datatype=\"x-undefined\" original=\"docProps/core.xml\" source-language=\"en-US\" target-language=\"it-IT\">\r\n" .
            "<body>\r\n</body>\r\n</file>\r\n</xliff>";

        var_dump($response[ 'body' ]);

        $response_decoded = json_decode($response[ 'body' ]);

        $this->assertEquals( $expected_job_id, $response_decoded->job->id );
        $this->assertEquals( $expected_job_password, $response_decoded->job->password );
        $this->assertEquals( $expected_xliff, $response_decoded->job->output_content->$file_id->document_content );
    }

}
