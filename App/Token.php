<?php

    namespace App;

    /**
     * Unique random keys Class
     */
    class Token
    {
        /**
         * The token value
         * @var array
         */
        protected $token;


        /**
         * Class constructor. Creates a new random token
         *
         * @param string $token_value (optional) - A token value
         *
         * @return void
         */
        public function __construct($token_value = null)
        {
            if ($token_value) {

                $this->token = $token_value;

            } else {

                $this->token = bin2hex(random_bytes(16));
            }
        }


        /**
         * Get token value
         *
         * @return string The value
         */
        public function getValue()
        {
            return $this->token;
        }


        /**
         * Get hashed token value
         *
         * @return string The hashed value
         */
        public function getHash()
        {
            return hash_hmac('sha256', $this->token, \App\Config::HASH_TOKEN_KEY);
        }
    }