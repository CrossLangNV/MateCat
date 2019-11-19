<?php

class Factory_ApiKey extends Factory_Base {

    static function create( $values ) {

        $values = array_merge(array(
            'uid' => 1,
            'api_key' => md5(uniqid()),
            'api_secret' => 'api_secret',
            'enabled' => true
        ), $values );

        $dao = new ApiKeys_ApiKeyDao( Database::obtain() );
        $struct = new ApiKeys_ApiKeyStruct( $values );

        return $dao->create( $struct );


    }
}
