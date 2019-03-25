<?php

/**
 * Created by PhpStorm.
 * User: Hashashiyyin
 * Date: 17/05/16
 * Time: 13:11
 *
 * @property int id
 */
class Engines_MMT extends Engines_AbstractEngine {

    protected $_config = [
            'segment'        => null,
            'translation'    => null,
            'newsegment'     => null,
            'newtranslation' => null,
            'source'         => null,
            'target'         => null,
            'langpair'       => null,
            'email'          => null,
            'keys'           => null,
            'mt_context'     => null,
            'id_user'        => null
    ];

    /**
     * @var array
     */
    protected $_head_parameters = [];

    /**
     * @var bool
     */
    protected $_skipAnalysis = true;

    public function __construct( $engineRecord ) {

        parent::__construct( $engineRecord );

        if ( $this->engineRecord->type != "MT" ) {
            throw new Exception( "Engine {$this->engineRecord->id} is not a MT engine, found {$this->engineRecord->type} -> {$this->engineRecord->class_load}" );
        }

    }

    /**
     * MMT exception name from tag_projection call
     * @see Engines_MMT::_decode
     */
    const LanguagePairNotSupportedException = 1;

    protected static $_supportedExceptions = [
            'LanguagePairNotSupportedException' => self::LanguagePairNotSupportedException
    ];

    protected function _getClient(){
        return new Engines\MMT\MMTServiceAPIWrapper(
                null,
                null,
                $this->engineRecord->extra_parameters[ 'MMT-License' ],
                "1.0",
                "MateCat",
                INIT::MATECAT_USER_AGENT . INIT::$BUILD_NUMBER
        );
    }

    /**
     * Get the available languages in MMT
     *
     * @return mixed
     * @throws \Engines\MMT\MMTServiceApiException
     */
    public function getAvailableLanguages(){
        $client = $this->_getClient();
        return $client->getAvailableLanguages();
    }

    public function get( $_config ) {

        if ( $this->_isAnalysis && $this->_skipAnalysis ) {
            return null;
        }

        $client = $this->_getClient();

        $_keys = $this->_reMapKeyList( @$_config[ 'keys' ] );

        $text = $this->_preserveSpecialStrings( $_config[ 'segment' ] );
        $translation = $client->translate( $_config[ 'source' ], $_config[ 'target' ], $text, @$_config[ 'mt_context' ], $_keys, @$_config[ 'job_id' ] );

        if( !empty( $translation[ 'translation' ] ) ){
            $this->result = ( new Engines_Results_MyMemory_Matches(
                    $this->_resetSpecialStrings( $text ),
                    $translation[ 'translation' ],
                    100 - $this->getPenalty() . "%",
                    "MT-" . $this->getName(),
                    date( "Y-m-d" )
            ) )->get_as_array();
        }

        return $this->result;

    }

    /**
     * @param $_keys
     *
     * @return array
     */
    protected function _reMapKeyList( $_keys = [] ){

        if ( !empty( $_keys ) ) {

            if ( !is_array( $_keys ) ) {
                $_keys = array( $_keys );
            }

            $_keys = array_map( function( $key ){
                return 'x_mm-' . $key;
            }, $_keys );

        }

        return $_keys;

    }

    /**
     * @param $keyList TmKeyManagement_MemoryKeyStruct[]
     *
     * @return array
     */
    protected function _reMapKeyStructsList( $keyList ){
        $keyList = array_map( function( $kStruct ){
            return 'x_mm-' . $kStruct->tm_key->key;
        }, $keyList );
        return $keyList;
    }

    public function set( $_config ) {

        $client = $this->_getClient();
        $_keys  = $this->_reMapKeyList( @$_config[ 'keys' ] );

        try {
            $client->addToMemoryContent( $_keys, $_config[ 'source' ], $_config[ 'target' ], $_config[ 'segment' ], $_config[ 'translation' ] );
        } catch ( Exception $e ){
            return false;
        }

        return true;

    }

    public function update( $_config ) {

        $client = $this->_getClient();
        $_keys  = $this->_reMapKeyList( @$_config[ 'keys' ] );

        try {
            $client->updateMemoryContent(
                    $_keys,
                    $_config[ 'source' ],
                    $_config[ 'target' ],
                    $_config[ 'newsegment' ],
                    $_config[ 'newtranslation' ],
                    $_config[ 'segment' ],
                    $_config[ 'translation'
            ] );
        } catch ( Exception $e ){
            return false;
        }

        return true;

    }

    public function delete( $_config ) {
        throw new DomainException( "Method " . __FUNCTION__ . " not implemented." );
    }

    /**
     * @param      $filePath
     * @param      $key
     * @param bool $fileName
     *
     * @return mixed
     * @throws \Engines\MMT\MMTServiceApiException
     */
    public function import( $filePath, $key, $fileName = false ) {

        $fp_out = gzopen( "$filePath.gz", 'wb9' );

        if( !$fp_out ){
            $fp_out   = null;
            @unlink( $filePath );
            $filePath = null;
            @unlink( "$fileName.gz" );
            throw new RuntimeException( 'IOException. Unable to create temporary file.' );
        }

        $tmpFileObject = new \SplFileObject( $filePath, 'r' );

        while ( ! $tmpFileObject->eof() ) {
            gzwrite( $fp_out, $tmpFileObject->fgets() );
        }

        $tmpFileObject = null;
        @unlink( $filePath );
        gzclose( $fp_out );

        $client = $this->_getClient();
        $client->importIntoMemoryContent( 'x_mm-' . trim( $key ), "$filePath.gz", 'gzip' );
        $fp_out   = null;
        @unlink( "$filePath.gz" );

        return $this->result;
    }

    /**
     *
     * @param $file    \SplFileObject
     * @param $source  string
     * @param $targets string[]
     *
     * @return mixed
     * @internal param array $langPairs
     *
     * @throws \Engines\MMT\MMTServiceApiException
     */
    public function getContext( \SplFileObject $file,  $source, $targets  ) {

        $fileName = $file->getRealPath();
        $file->rewind();

        $fp_out = gzopen( "$fileName.gz", 'wb9' );

        if( !$fp_out ){
            $fp_out = null;
            $file = null;
            @unlink( $fileName );
            @unlink( "$fileName.gz" );
            throw new RuntimeException( 'IOException. Unable to create temporary file.' );
        }

        while ( ! $file->eof() ) {
            gzwrite( $fp_out, $file->fgets() );
        }

        $file = null;
        gzclose( $fp_out );

        $client = $this->_getClient();
        $result = $client->getContextVectorFromFile( $source, $targets, "$fileName.gz", 'gzip' );

        $plainContexts = [];
        foreach ($result['vectors'] as $target => $vector) {
            $plainContexts["$source|$target"] = $vector;
        }

        return $plainContexts;

    }

    /**
     * Call to check the license key validity
     * @return Engines_Results_MMT_ExceptionError
     * @throws \Engines\MMT\MMTServiceApiException
     */
    public function checkAccount(){
        $client = $this->_getClient();
        $this->result = $client->me();
        return $this->result;
    }

    /**
     * Activate the account and also update/add keys to User MMT data
     *
     * @param $keyList TmKeyManagement_MemoryKeyStruct[]
     *
     * @return mixed
     * @throws \Engines\MMT\MMTServiceApiException
     */
    public function activate( Array $keyList ){

        $keyList = array_map( function( $kStruct ){
            return 'x_mm-' . $kStruct->tm_key->key;
        }, $keyList );

        $client = $this->_getClient();
        $this->result = $client->connectMemories( $keyList );

        return $this->result;

    }

    /**
     * @param $rawValue
     *
     * @return Engines_Results_AbstractResponse
     */
    protected function _decode( $rawValue ) {

        $args         = func_get_args();
        $functionName = $args[ 2 ];

        if ( is_string( $rawValue ) ) {
            $decoded = json_decode( $rawValue, true );
        } else {

            if ( $rawValue[ 'responseStatus' ] >= 400 ){
                $_rawValue = json_decode( $rawValue[ 'error' ][ 'response' ], true );
                foreach( self::$_supportedExceptions as $exception => $code ){
                    if( stripos( $rawValue[ 'error' ][ 'response' ], $exception ) !== false ){
                        $_rawValue[ 'error' ][ 'code' ] = @constant( 'self::' . $rawValue[ 'error' ][ 'type' ] );
                        break;
                    }
                }
                $rawValue = $_rawValue;
            }

            $decoded = $rawValue; // already decoded in case of error

        }

        switch ( $functionName ) {
            case 'tags_projection' :
                $result_object = Engines_Results_MMT_TagProjectionResponse::getInstance( $decoded );
                break;
            default:
                //this case should not be reached
                $result_object = Engines_Results_MMT_ExceptionError::getInstance( [
                        'error' => [
                                'code'      => -1100,
                                'message'   => " Unknown Error.",
                                'response'  => " Unknown Error." // Some useful info might still be contained in the response body
                        ],
                        'responseStatus'    => 400
                ] ); //return generic error
                break;
        }

        return $result_object;

    }

    /**
     * TODO FixMe whit the url parameter and method extracted from engine record on the database
     * when MyMemory TagProjection will be public
     *
     * @param $config
     *
     * @return array|Engines_Results_MMT_TagProjectionResponse
     */
    public function getTagProjection( $config ){

        $parameters           = array();
        $parameters[ 's' ]    = $config[ 'source' ];
        $parameters[ 't' ]    = $config[ 'target' ];
        $parameters[ 'hint' ] = $config[ 'suggestion' ];

        /*
         * For now override the base url and the function params
         */
        $this->engineRecord[ 'base_url' ] = 'http://149.7.212.129:10000';
        $this->engineRecord->others[ 'tags_projection' ] = 'tags-projection/' . $config[ 'source_lang' ] . "/" . $config[ 'target_lang' ] . "/";

        $this->call( 'tags_projection', $parameters );

        return $this->result;

    }

}