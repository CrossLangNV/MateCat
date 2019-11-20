<?php



class NewControllerWithUidTest extends IntegrationTest {

    private $test_data = array();

    function setup() {
        
        $this->test_data = new stdClass();
        $this->test_data->user = Factory_User::create();
    }

    function test_new_project_with_uid_team_id() {

        $test = new CurlTest();

        $test->method = 'POST';
        $test->path = '/api/new' ;

        $test->params = array(
                'project_name' => 'foo',
                'target_lang' => 'it-IT',
                'source_lang' => 'en-US',
                'uid'     => $this->test_data->user->uid,
                'id_team' => $this->test_data->user->uid
        );
        $test->files[] = test_file_path('xliff/amex-test.docx.xlf');

        $response = $test->getResponse();
        $decoded_response = json_decode( $response['body'] ) ;

        // test whether newly created project contains correct id_team and id_assignee

        $project = Projects_ProjectDao::findById( $decoded_response->id_project );

        $this->assertEquals($this->test_data->user->uid, $project->id_team);
        $this->assertEquals($this->test_data->user->uid, $project->id_assignee);
    }

}
