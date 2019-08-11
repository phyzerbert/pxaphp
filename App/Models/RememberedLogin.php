<?php

    namespace App\Models;

    use PDO;
    use \App\Token;

    /**
     * Remembered logins Model
     */
    class RememberedLogin extends \Core\Model
    {
        /**
         * Find a remembered login model by the token
         *
         * @param string $token - The remembered login token
         *
         * @return mixed - Remembered login object if found and Null otherwise
         */
        public static function getByToken(string $token)
        {
            $token = new Token($token);
            $token_hash = $token->getHash();

            $sql = 'SELECT * FROM remembered_logins WHERE token_hash = :token_hash ';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':token_hash', $token_hash, PDO::PARAM_STR);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Get the user model associated with this remembered login
         *
         * @return User The user model
         */
        public function getUser()
        {
            return User::getByID($this->user_id);        
        }


        /**
         * Check if the remember token has expired or not, based on current system time
         *
         * @return boolean - True if the token has expired and False otherwise
         */
        public function hasExpired()
        {
            return strtotime($this->expires_date) < time();
        }


        /**
         * Delete remembered login
         *
         * @return void
         */
        public function delete()
        {
            $sql = 'DELETE FROM remembered_logins WHERE token_hash = :token_hash';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':token_hash', $this->token_hash, PDO::PARAM_STR);

            $stmt->execute();
        }
    }