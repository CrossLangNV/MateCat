<?php

use Teams\TeamDao;
use Database;

class Factory_User extends Factory_Base {

    static function create( $values=array() ) {

        $values = array_merge(array(
            'email' => "test-email-" . uniqid() . "@example.org",
            'salt' => '1234abcd',
            'pass' => '1234abcd',
            'first_name' => 'John',
            'last_name' => 'Connor',
            'api_key' => '1234abcd'
        ), $values);

        $dao = new Users_UserDao( Database::obtain() );
        $userStruct = new Users_UserStruct( $values );
        $user = $dao->createUser( $userStruct );

        $orgDao = new TeamDao() ;
        Database::obtain()->begin();
        $team = $orgDao->createUserTeam( $user, array(
            'type' => Constants_Teams::PERSONAL,
            'name' => 'personal'
        ));
        Database::obtain()->commit();

        return $user ;
    }

}
