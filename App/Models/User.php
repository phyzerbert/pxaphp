<?php

    namespace App\Models;

    use PDO;
    use \App\Token;
    use \App\Mail;
    use \App\Config;
    use \Core\View;

    /**
     * Users model
     */
    class User extends \Core\Model
    {
        /*
         * Array with errors
         */
        public $errors = [];

        /*
         * Default parameters for sign up
         */
        private $minUsernameLength = 4;
        private $maxUsernameLength = 20;
        private $minPasswordLength = 6;
        private $maxPasswordLength = 20;

        static $forbiddenUsernames = [
            'admin', 'administration', 'signup', 'signin', 'register', 'search', 'logout', 'terms', 
            'policy', 'trends', 'categories', 'topics', 'users', 'settings', 'favorites', 
            'login', 'logout'
        ];


        /**
         * Class constructor
         *
         * @param array $data - Initial property values
         *
         * @return void
         */
        public function __construct(array $data = [])
        {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }


        /**
         * Sign Up the user model with the current property values
         *
         * @return boolean - True if the user was added, False otherwise
         */
        public function signup()
        {
            $this->validate();

            if (empty($this->errors)) {

                $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

                $token = new Token();
                $token_hash = $token->getHash();
                $this->activation_token = $token->getValue();

                $is_active = (Config::getValues('email_activation')) ? 0 : 1;

                $sql = 'INSERT INTO users (username, password, email, signup_stamp, signup_ip, activation_hash, is_active)
                        VALUES (:username, :password_hash, :email, :signup_stamp, :signup_ip, :activation_hash, :is_active)';

                $db = static::getDB();
                $stmt = $db->prepare($sql);

                $stmt->bindValue(':username', $this->username, PDO::PARAM_STR);
                $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
                $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
                $stmt->bindValue(':signup_stamp', time(), PDO::PARAM_INT);
                $stmt->bindValue(':signup_ip', static::getClientIp(), PDO::PARAM_STR);
                $stmt->bindValue(':activation_hash', $token_hash, PDO::PARAM_STR);
                $stmt->bindValue(':is_active', $is_active, PDO::PARAM_INT);

                return $stmt->execute();
            }

            return false;
        }


        /**
         * Validate current property values, adding valiation error messages to the errors array property
         *
         * @param array $types - Types of validation (username, email, password, new_username, new_email, birth_date, gender)
         *
         * @return void
         */
        public function validate(array $types = ['username', 'email', 'password'])
        {
            // Username
            if (in_array('username', $types)) {

                if ($this->username == '') {
                    $this->errors[] = 'Username is required';
                }
                if (static::usernameExists($this->username)) {
                    $this->errors[] = 'Username already taken';
                }
                if (in_array($this->username, User::$forbiddenUsernames)) {
                    $this->errors[] = 'This username can not be used';
                }
            }

            // Email Address
            if (in_array('email', $types)) {

                if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
                    $this->errors[] = 'Invalid email';
                }
                if (static::emailExists($this->email)) {
                    $this->errors[] = 'Email already taken';
                }
            }

            // New Username
            if (in_array('new_username', $types)) {

                $user = static::getById($this->user_id);

                /* Check if user changed username or keep as it was */
                if ($this->username != $user->username) {
                    
                    if ($this->username == '') {
                        $this->errors[] = 'Username is required';
                    }
                    if (static::usernameExists($this->username)) {
                        $this->errors[] = 'Username already taken';
                    }
                    if (in_array($this->username, User::$forbiddenUsernames)) {
                        $this->errors[] = 'This username can not be used';
                    }
                }
            }

            // New Email Address
            if (in_array('new_email', $types)) {

                $user = static::getById($this->user_id);

                /* Check if user changed email or keep as it was */
                if ($this->email != $user->email) {
                    
                    if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
                        $this->errors[] = 'Invalid email';
                    }
                    if (static::emailExists($this->email)) {
                        $this->errors[] = 'Email already taken';
                    }
                }
            }

            // Password
            if (in_array('password', $types)) {
                
                if (strlen($this->password) < 6) {
                    $this->errors[] = 'Please enter at least 6 characters for the password';
                }
                if (preg_match('/.*[a-z]+.*/i', $this->password) == 0) {
                    $this->errors[] = 'Password needs at least one letter';
                }
                if (preg_match('/.*\d+.*/i', $this->password) == 0) {
                    $this->errors[] = 'Password needs at least one number';
                }
            }

            // New Password
            if (in_array('new_password', $types)) {

                if (isset($this->current_password) && strlen($this->current_password) == 0 && isset($this->new_password) && strlen($this->new_password) > 0) {
                    $this->errors[] = 'Enter current password';
                }


                if (isset($this->current_password) && strlen($this->current_password) > 0 && isset($this->new_password) && strlen($this->new_password) == 0) {
                    $this->errors[] = 'Enter new password';
                }


                if (isset($this->current_password) && strlen($this->current_password) > 0 && isset($this->new_password) && strlen($this->new_password) > 0) {

                    $user = static::getById($this->user_id);

                    if (! password_verify($this->current_password, $user->password)) {
                        $this->errors[] = 'Current password is wrong';
                    }
                    
                    if (strlen($this->new_password) < 6) {
                        $this->errors[] = 'Please enter at least 6 characters for the new password';
                    }
                    if (preg_match('/.*[a-z]+.*/i', $this->new_password) == 0) {
                        $this->errors[] = 'New password needs at least one letter';
                    }
                    if (preg_match('/.*\d+.*/i', $this->new_password) == 0) {
                        $this->errors[] = 'New password needs at least one number';
                    }
                }
            }

            // Birth Date
            if (in_array('birth_date', $types)) {

                if (strlen($this->birth_date) > 0) {

                    if ($this->birth_date < "1990-01-01" || $this->birth_date >= date("Y-m-d")) {

                        $this->errors[] = 'Wrong birth date';
                    }
                }
            }

            // Gender
            if (in_array('gender', $types)) {

                if ($this->gender != 1 && $this->gender != 2) {

                    $this->gender = 0;
                }
            }
        }


        /**
         * See if a user record already exists with the specified email
         *
         * @param string $email - Email address to search for
         *
         * @return boolean - True if a record already exists with the specified email, False otherwise
         */
        public static function emailExists(string $email)
        {
            return static::getByEmail($email) !== false;
        }


        /**
         * See if a user record already exists with the specified username
         *
         * @param string $username - Username address to search for
         *
         * @return boolean - True if a record already exists with the specified email, False otherwise
         */
        public static function usernameExists(string $username)
        {
            return static::getByUsername($username) !== false;
        }


        /**
         * Find and return user by id
         *
         * @param int $id - User's id to search for
         * @param array $columns - Columns from database which are required in results
         *
         * @return mixed - User object if found, Null otherwise
         */
        public static function getById(int $id, array $columns = [])
        {
            if (count($columns) > 0) {
                $columns_sql = implode(", ", $columns);
            } else {
                $columns_sql = '*';
            }

            $sql = "SELECT {$columns_sql} FROM users WHERE id = :id";

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Find and return user by email address
         *
         * @param string $email - Email address to search for
         *
         * @return mixed - User object if found, Null otherwise
         */
        public static function getByEmail(string $email)
        {
            $sql = 'SELECT * FROM users WHERE email = :email';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Find and return user by username
         *
         * @param string $username - Username to search for
         *
         * @return mixed - User object if found, Null otherwise
         */
        public static function getByUsername(string $username)
        {
            $sql = 'SELECT * FROM users WHERE username = :username';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Authenticate a user by email and password.
         *
         * @param string $email - Email address
         * @param string $password - Password
         *
         * @return mixed - The user object or False if authentication fails
         */
        public static function authenticate(string $email, string $password)
        {
            $user = static::getByEmail($email);

            if ($user && ( (Config::getValues('email_activation') == 1 && $user->is_active) || (Config::getValues('email_activation') == 0) )) {
                if (password_verify($password, $user->password)) {
                    if ($user->active == 1) {
                        return $user;
                    }
                }
            }

            return false;
        }


        /**
         * Remember the signin by inserting a new unique token into the remembered_logins table
         *
         * @return boolean - True if the login was remembered successfully, Null otherwise
         */
        public function rememberSignin()
        {
            $token = new Token();
            $hashed_token = $token->getHash();
            $this->remember_token = $token->getValue();

            // 30 days
            $added_timestamp = time();
            $this->expiry_timestamp = $added_timestamp + 60 * 60 * 24 * 30;

            $sql = 'INSERT INTO remembered_logins (token_hash, user_id, expires_date, added_date) 
                    VALUES (:token_hash, :user_id, :expires_date, :added_date)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':expires_date', date('Y-m-d H:i:s', $this->expiry_timestamp), PDO::PARAM_STR);
            $stmt->bindValue(':added_date', date('Y-m-d H:i:s', $added_timestamp), PDO::PARAM_STR);

            return $stmt->execute();
        }


        /**
         * Send password reset instructions to the user specified
         *
         * @param string $email - The email address
         *
         * @return void
         */
        public static function sendPasswordReset(string $email)
        {
            $user = static::getByEmail($email);

            if ($user) {

                if ($user->startPasswordReset()) {

                    $user->sendPasswordResetEmail();
                }
            }
        }


        /**
         * Start the password reset process by generating a new token and expiry
         *
         * @return void
         */
        protected function startPasswordReset()
        {
            $token = new Token();
            $hashed_token = $token->getHash();
            $this->password_reset_token = $token->getValue();

            $expiry_timestamp = time() + 60 * 60 * 2;  // 2 hours from now

            $sql = 'UPDATE users
                    SET password_reset_hash = :token_hash,
                        password_reset_expires_at = :expires_at
                    WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
            $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $expiry_timestamp), PDO::PARAM_STR);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Send password reset instructions in an email to the user
         *
         * @return void
         */
        protected function sendPasswordResetEmail()
        {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/password/reset/' . $this->password_reset_token;

            $text = View::getTemplate('email_templates/reset_email.txt', ['url' => $url]);
            $html = View::getTemplate('email_templates/reset_email.html', ['url' => $url]);

            Mail::send($this->email, 'Password reset', $text, $html);
        }


        /**
         * Find a user model by password reset token and expiry
         *
         * @param string $token - Password reset token sent to user
         *
         * @return mixed - User object if found and the token hasn't expired, Null otherwise
         */
        public static function getByPasswordReset(string $token)
        {
            $token = new Token($token);
            $hashed_token = $token->getHash();

            $sql = 'SELECT * FROM users
                    WHERE password_reset_hash = :token_hash';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            $user = $stmt->fetch();

            if ($user) {

                // Check password reset token hasn't expired
                if (strtotime($user->password_reset_expires_at) > time()) {

                    return $user;
                }
            }
        }

        /**
         * Reset the password
         *
         * @param string $password - The new password
         *
         * @return boolean - True if the password was updated successfully, False otherwise
         */
        public function resetPassword(string $password)
        {
            $this->password = $password;

            $this->validate(['password']);

            if (empty($this->errors)) {

                $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

                $sql = 'UPDATE users
                        SET password = :password_hash,
                            password_reset_hash = NULL,
                            password_reset_expires_at = NULL
                        WHERE id = :id';

                $db = static::getDB();
                $stmt = $db->prepare($sql);

                $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
                $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);

                return $stmt->execute();
            }

            return false;
        }


        /**
         * Send account activation email to the user which signed up
         *
         * @return void
         */
        public function sendActivationEmail()
        {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/signup/activate/' . $this->activation_token;

            $text = View::getTemplate('email_templates/activation_email.txt', ['url' => $url]);
            $html = View::getTemplate('email_templates/activation_email.html', ['url' => $url]);

            Mail::send($this->email, 'Account activation', $text, $html);
        }


        /**
         * Activate the user account with the specified activation token
         *
         * @param string $value - Activation token from the URL
         *
         * @return void
         */
        public static function activate(string $value)
        {
            $token = new Token($value);
            $hashed_token = $token->getHash();

            $sql = 'UPDATE users
                    SET is_active = 1,
                        activation_hash = null
                    WHERE activation_hash = :hashed_token';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':hashed_token', $hashed_token, PDO::PARAM_STR);

            $stmt->execute();                
        }


        /**
         * Update user activity timestamp
         *
         * @param integer $value - User ID
         *
         * @return boolean - True if the query run successfully, Null otherwise
         */
        public static function setActiveNow(int $user_id)
        {
            $sql = "UPDATE users SET last_visit = :last_visit WHERE id = :user_id";

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':last_visit', time(), PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();                
        }


        /**
         * Add amount of points for profile
         *
         * @param integer $value - User ID
         * @param integer $value - Number of points which should be added
         *
         * @return boolean - True if the query run successfully, Null otherwise
         */
        public static function addPoints(int $user_id, int $value = 0)
        {
            $sql = "UPDATE users
                    SET points = points + ".intval($value)."
                    WHERE id = :user_id";

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();                
        }


        /**
         * Remove amount of points from profile
         *
         * @param integer $value - User ID
         * @param integer $value - Number of points which should be removed
         *
         * @return boolean - True if the query run successfully, Null otherwise
         */
        public static function removePoints(int $user_id, int $value = 0)
        {
            $sql = "UPDATE users
                    SET points = points - ".intval($value)."
                    WHERE id = :user_id";

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();                
        }


        /**
         * Return number of all users available
         *
         * @param array $params - Array with parameters
         *
         * @return int - Number of users
         */
        public static function count(array $params = [])
        {
            $params['active'] = $params['active'] ?? 1;

            $search_sql = (isset($params['search'])) ? ' AND (username LIKE :search OR name LIKE :search OR about LIKE :search)' : '';

            $sql = 'SELECT COUNT(id) AS total FROM users WHERE active = :active'.$search_sql;

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':active', $params['active'], PDO::PARAM_INT);
            if ($search_sql) {
                $stmt->bindValue(':search', '%'.$params['search'].'%', PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result['total'] > 0) {
                
                return $result['total'];

            } else {
                
                return 0;
            }
        }


        /**
         * Return number of all users available for period of time
         *
         * @param int $timestamp - timestamp from which time count users
         *
         * @return int - Number of users
         */
        public static function countByTime(int $timestamp)
        {
           $sql = 'SELECT COUNT(id) AS total FROM users WHERE signup_stamp > :timestamp';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':timestamp', $timestamp, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result['total'] > 0) {
                
                return $result['total'];

            } else {
                
                return 0;
            }
        }


        /**
         * Function which returns users
         *
         * @param array $params - Array with parameters
         *
         * @return mixed - Users array with objects if found, Null otherwise
         */
        public static function get(array $params = [])
        {
            $params['active']       = $params['active']     ?? 1;
            $params['limit']        = $params['limit']      ?? 100;
            $params['offset']       = $params['offset']     ?? 0;
            $params['order_by']     = $params['order_by']   ?? 'id';
            $params['order_type']   = $params['order_type'] ?? 'DESC';

            $search_sql = (isset($params['search'])) ? ' AND (u.username LIKE :search OR u.name LIKE :search OR u.about LIKE :search)' : '';
            $email_notifications_sql = (isset($params['email_notifications'])) ? ' AND (u.email_notifications = :email_notifications)' : '';

            $sql = 'SELECT *,(SELECT COUNT(id) FROM questions WHERE user_id = u.id) AS questions, (SELECT COUNT(id) FROM answers WHERE user_id = u.id) AS answers FROM users u WHERE u.active = :active'.$search_sql.$email_notifications_sql.' ORDER BY u.'.$params['order_by'].' '.$params['order_type'].' LIMIT :offset, :limit';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':active', $params['active'], PDO::PARAM_INT);
            if ($search_sql) {
                $stmt->bindValue(':search', '%'.$params['search'].'%', PDO::PARAM_STR);
            }
            if ($email_notifications_sql) {
                $stmt->bindValue(':email_notifications', $params['email_notifications'], PDO::PARAM_INT);
            }
            $stmt->bindValue(':offset', intval($params['offset']), PDO::PARAM_INT);
            $stmt->bindValue(':limit', intval($params['limit']), PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetchAll();
        }


        /**
         * Save all user settings into database
         *
         * @return void
         */
        public function update()
        {
            $this->validate(['gender', 'new_email', 'new_password']);

            if (empty($this->errors)) {

                $new_photo_sql = (isset($this->photo) && strlen($this->photo) > 0) ? 'photo = :photo, ' : '';
                $new_password_sql = (isset($this->current_password) && strlen($this->current_password) > 0 && isset($this->new_password) && strlen($this->new_password) > 0) ? 'password = :new_password, ' : '';

                $sql = 'UPDATE users SET name = :name, birth_date = :birth_date, location = :location, gender = :gender, about = :about, '.$new_photo_sql.$new_password_sql.' email = :email, email_notifications = :email_notifications WHERE id = :user_id';

                $email_notifications = (isset($this->email_notifications)) ? 1 : 0;

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
                $stmt->bindValue(':birth_date', $this->birth_date, PDO::PARAM_STR);
                $stmt->bindValue(':location', $this->location, PDO::PARAM_STR);
                $stmt->bindValue(':gender', $this->gender, PDO::PARAM_INT);
                $stmt->bindValue(':about', $this->about, PDO::PARAM_STR);
                if ($new_photo_sql) {
                    $stmt->bindValue(':photo', $this->photo, PDO::PARAM_STR);
                }
                if ($new_password_sql) {

                    $password_hash = password_hash($this->new_password, PASSWORD_DEFAULT);

                    $stmt->bindValue(':new_password', $password_hash, PDO::PARAM_STR);
                }
                $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
                $stmt->bindValue(':email_notifications', $email_notifications, PDO::PARAM_INT);


                $stmt->bindValue(':user_id', $this->user_id, PDO::PARAM_INT);

                return $stmt->execute();
            }

            return false;
        }


        /**
         * Ban user function
         *
         * @param integer $user_id - User ID
         *
         * @return boolean - True if the query run successfully, Null otherwise
         */
        public static function ban(int $user_id)
        {
            $sql = "UPDATE users
                    SET active = 0
                    WHERE id = :user_id";

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();                
        }


        /**
         * Remove ban user function
         *
         * @param integer $value - User ID
         *
         * @return boolean - True if the query run successfully, Null otherwise
         */
        public static function unban(int $user_id)
        {
            $sql = "UPDATE users
                    SET active = 1
                    WHERE id = :user_id";

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();                
        }


        /**
         * Add new user from admin panel
         *
         * @return boolean - True if the user was created, Null otherwise
         */
        public function add()
        {
            $this->validate();

            if (empty($this->errors)) {

                $sql = 'INSERT INTO users (username, password, email, name, is_active, birth_date, location, about, points, email_notifications, is_admin, signup_stamp, last_visit)
                            VALUES (:username, :password, :email, :name, :is_active, :birth_date, :location, :about, :points, :email_notifications, :is_admin, :signup_stamp, :last_visit)';

                $db = static::getDB();
                $stmt = $db->prepare($sql);

                $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

                $stmt->bindValue(':username', $this->username, PDO::PARAM_STR);
                $stmt->bindValue(':password', $password_hash, PDO::PARAM_STR);
                $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
                $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
                $stmt->bindValue(':is_active', $this->is_active, PDO::PARAM_INT);
                $stmt->bindValue(':birth_date', $this->birth_date, PDO::PARAM_STR);
                $stmt->bindValue(':location', $this->location, PDO::PARAM_STR);
                $stmt->bindValue(':about', $this->about, PDO::PARAM_STR);
                $stmt->bindValue(':points', $this->points, PDO::PARAM_INT);
                $stmt->bindValue(':email_notifications', $this->email_notifications, PDO::PARAM_INT);
                $stmt->bindValue(':is_admin', $this->is_admin, PDO::PARAM_INT);
                $stmt->bindValue(':signup_stamp', time(), PDO::PARAM_INT);
                $stmt->bindValue(':last_visit', time(), PDO::PARAM_INT);

                return $stmt->execute();
            }

            return false;
        }


        /**
         * Save / Update user from admin panel
         *
         * @return boolean - True if the user was updated, Null otherwise
         */
        public function save()
        {
            $this->validate(['new_username', 'new_email']);

            if (empty($this->errors)) {

                $sql = 'UPDATE users SET username = :username, email = :email, name = :name, is_active = :is_active, birth_date = :birth_date, location = :location, about = :about, points = :points, email_notifications = :email_notifications, is_admin = :is_admin WHERE id = :id';

                $db = static::getDB();
                $stmt = $db->prepare($sql);

                $stmt->bindValue(':username', $this->username, PDO::PARAM_STR);
                $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
                $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
                $stmt->bindValue(':is_active', $this->is_active, PDO::PARAM_INT);
                $stmt->bindValue(':birth_date', $this->birth_date, PDO::PARAM_STR);
                $stmt->bindValue(':location', $this->location, PDO::PARAM_STR);
                $stmt->bindValue(':about', $this->about, PDO::PARAM_STR);
                $stmt->bindValue(':points', $this->points, PDO::PARAM_INT);
                $stmt->bindValue(':email_notifications', $this->email_notifications, PDO::PARAM_INT);
                $stmt->bindValue(':is_admin', $this->is_admin, PDO::PARAM_INT);
                $stmt->bindValue(':id', $this->user_id, PDO::PARAM_INT);

                return $stmt->execute();
            }

            return false;
        }


        /**
         * Delete user completely
         *
         * @param integer $user_id - User ID
         *
         * @return boolean - True if the user was deleted, Null otherwise
         */
        public static function delete(int $user_id)
        {
            $sql = 'DELETE FROM users WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        }
    }