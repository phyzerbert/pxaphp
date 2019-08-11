<?php

    namespace App;

    use App\Models\Setting;

    /**
     * Application Configuration
     * Do not change constants titles. Change only values for avoiding errors
     * Some app settings / configs are loaded from database (settings table) and can be set from admin panel
     */
    class Config
    {
        /**
         * Database host
         * @var string
         */
        const DB_HOST = 'localhost';

        /**
         * Database name
         * @var string
         */
        const DB_NAME = 'qxaphp';

        /**
         * Database user
         * @var string
         */
        const DB_USER = 'root';

        /**
         * Database password
         * @var string
         */
        const DB_PASSWORD = '';

        /**
         * Show or hide error messages on screen
         * @var boolean (true or false)
         */
        const SHOW_ERRORS = true;

        /**
         * Hash token key (256 bit key). Recommend to change it.
         * https://randomkeygen.com/
         * @var string
         */
        const HASH_TOKEN_KEY = 'fFqXRjqHF8nRMfgjTy8sepjsVNRZICOQ';

        /**
         * Secret token key (256 bit key). Recommend to change it.
         * https://randomkeygen.com/
         * @var string
         */
        const SECRET_TOKEN_KEY = 'BFEDB4C2B65624AE127BA1E316F1C';


        /**
         * Function which returns some settings values from database
         *
         * @var string $type - Settings type (Optional)
         *
         * List of options for type:
         *      app_name            Website name
         *      app_version         Application version
         *      app_email           Email address used for reply back
         *      email_option        Option for using email function: 0 - default php mail() | 1 - Mailgun | 2 - PHPMailer
         *      mailgun_api_key     Mailgun API key   
         *      mailgun_domain      Mailgun domain
         *      email_activation    Require or not email confirmation after sign up 0 - disabled | 1 - enabled
         *                          If is false - all new accounts are automatically sign up as activated
         *
         * @return mixed - int / boolean / string - depending which value has column in database for requested type
         * @return Settings - if type is empty (Return all settings)
         */
        public static function getValues(string $type = '')
        {
            $settings = new Setting();

            $config = $settings->get();

            $config->secret = self::SECRET_TOKEN_KEY;

            if ($type) {

                if ($config->$type) {

                    return $config->$type;

                } else {

                    return null;
                }

            } else {

                return $config;
            }
        }
    }