<?php

class API_Endpoints {
    public function register_routes() {
        register_rest_route('yxs/v1', '/certification', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_certification_request'),
            'permission_callback' => array($this, 'check_api_key_permission'),
            'args' => array(
                'realName' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'cardNo' => array(
                    'required' => true,
                    'type' => 'string',
                )
            )
        ));
    }
} 