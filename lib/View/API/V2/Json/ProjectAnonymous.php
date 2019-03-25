<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 20/01/17
 * Time: 16.41
 *
 */

namespace API\V2\Json;

use Projects_ProjectStruct;

class ProjectAnonymous extends Project {

    /** @noinspection PhpMissingParentConstructorInspection */

    /**
     * Project constructor.
     *
     * @param Projects_ProjectStruct[] $data
     */
    public function __construct( array $data = null ) {
        $this->data = $data;
        $this->jRenderer = new JobAnonymous();
    }

    /**
     * @param $data Projects_ProjectStruct
     *
     * @return array
     */
    public function renderItem( Projects_ProjectStruct $data ) {

        $projectOutputFields = parent::renderItem( $data );
        unset( $projectOutputFields[ 'id_team' ] );
        unset( $projectOutputFields[ 'id_assignee' ] );

        return $projectOutputFields;

    }

}