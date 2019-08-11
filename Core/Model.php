<?php

    namespace Core;

    use PDO;
    use App\Config;

    /**
     * Base Model Class
     */
    abstract class Model
    {
        /**
         * Get the PDO database connection
         *
         * @return mixed
         */
        protected static function getDB()
        {
            static $db = null;

            if ($db === null) {
        
                $dsn = 'mysql:host=' . Config::DB_HOST . ';dbname=' . Config::DB_NAME . ';charset=utf8';
                $db = new PDO($dsn, Config::DB_USER, Config::DB_PASSWORD);

                // Throw an Exception when an error occurs
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }

            return $db;
        }


        /**
         * Function which return user's IP address
         *
         * @return string - IP address
         */
        protected static function getClientIp() 
        {
            $ipaddress = '';

            if (getenv('HTTP_CLIENT_IP')) {
                $ipaddress = getenv('HTTP_CLIENT_IP');
            } else if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
            } else if (getenv('HTTP_X_FORWARDED')) {
                $ipaddress = getenv('HTTP_X_FORWARDED');
            } else if (getenv('HTTP_FORWARDED_FOR')) {
                $ipaddress = getenv('HTTP_FORWARDED_FOR');
            } else if (getenv('HTTP_FORWARDED')) {
               $ipaddress = getenv('HTTP_FORWARDED');
            } else if (getenv('REMOTE_ADDR')) {
                $ipaddress = getenv('REMOTE_ADDR');
            } else {
                $ipaddress = 'UNKNOWN';
            }

            return $ipaddress;
        }


        /**
         * Function which convert string into url (Slugify)
         *
         * @param string $string - String which should be converted into URL
         *
         * @return string - converted URL from string
         */
        public static function slug(string $string, string $separator = '-')
        {
            $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
            $special_cases = array('&' => 'and', "'" => '');
            $string = mb_strtolower(trim($string), 'UTF-8');
            $string = str_replace(array_keys($special_cases), array_values($special_cases), $string);
            $string = preg_replace($accents_regex, '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
            $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
            $string = preg_replace("/[$separator]+/u", "$separator", $string);
            
            return $string;
        }
    }