<?php

    namespace App\Models;

    use PDO;
    use \App\Config;

    /**
     * Setting values from database 
     * Configs which can be edited through admin panel
     */
    class Setting extends \Core\Model
    {

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
         * Load all settings from database
         *
         * @return void
         */
        public function get()
        {
            $sql = 'SELECT * FROM settings';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Save all settings into database
         *
         * @return boolean - True if settings was updated, Null otherwise
         */
        public function update()
        {
            $sql = 'UPDATE settings SET app_name = :app_name, app_email = :app_email, email_option = :email_option, mailgun_api_key = :mailgun_api_key, mailgun_domain = :mailgun_domain, email_activation = :email_activation, smtp_host = :smtp_host, smtp_username = :smtp_username, smtp_password = :smtp_password, smtp_secure = :smtp_secure, smtp_port = :smtp_port, per_page_admin = :per_page_admin, per_page_user = :per_page_user, question_price = :question_price, question_max_title = :question_max_title, question_max_images = :question_max_images, question_max_description = :question_max_description, question_max_topics = :question_max_topics, banned_words = :banned_words, banner_top = :banner_top, banner_top_status = :banner_top_status, banner_left = :banner_left, banner_left_status = :banner_left_status, analytics_code = :analytics_code, analytics_code_status = :analytics_code_status, email_notifications = :email_notifications';

            $email_activation = (isset($this->email_activation)) ? 1 : 0;
            $banner_top_status = (isset($this->banner_top_status)) ? 1 : 0;
            $banner_left_status = (isset($this->banner_left_status)) ? 1 : 0;
            $analytics_code_status = (isset($this->analytics_code_status)) ? 1 : 0;

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':app_name', $this->app_name, PDO::PARAM_STR);
            $stmt->bindValue(':app_email', $this->app_email, PDO::PARAM_STR);
            $stmt->bindValue(':email_option', $this->email_option, PDO::PARAM_INT);
            $stmt->bindValue(':mailgun_api_key', $this->mailgun_api_key, PDO::PARAM_STR);
            $stmt->bindValue(':mailgun_domain', $this->mailgun_domain, PDO::PARAM_STR);
            $stmt->bindValue(':email_activation', $email_activation, PDO::PARAM_INT);
            $stmt->bindValue(':smtp_host', $this->smtp_host, PDO::PARAM_STR);
            $stmt->bindValue(':smtp_username', $this->smtp_username, PDO::PARAM_STR);
            $stmt->bindValue(':smtp_password', $this->smtp_password, PDO::PARAM_STR);
            $stmt->bindValue(':smtp_secure', $this->smtp_secure, PDO::PARAM_STR);
            $stmt->bindValue(':smtp_port', $this->smtp_port, PDO::PARAM_INT);
            $stmt->bindValue(':per_page_admin', $this->per_page_admin, PDO::PARAM_INT);
            $stmt->bindValue(':per_page_user', $this->per_page_user, PDO::PARAM_INT);
            $stmt->bindValue(':question_price', $this->question_price, PDO::PARAM_INT);
            $stmt->bindValue(':question_max_title', $this->question_max_title, PDO::PARAM_INT);
            $stmt->bindValue(':question_max_images', $this->question_max_images, PDO::PARAM_INT);
            $stmt->bindValue(':question_max_description', $this->question_max_description, PDO::PARAM_INT);
            $stmt->bindValue(':question_max_topics', $this->question_max_topics, PDO::PARAM_INT);
            $stmt->bindValue(':banned_words', $this->banned_words, PDO::PARAM_STR);
            $stmt->bindValue(':banner_top', $this->banner_top, PDO::PARAM_STR);
            $stmt->bindValue(':banner_top_status', $banner_top_status, PDO::PARAM_INT);
            $stmt->bindValue(':banner_left', $this->banner_left, PDO::PARAM_STR);
            $stmt->bindValue(':banner_left_status', $banner_left_status, PDO::PARAM_INT);
            $stmt->bindValue(':analytics_code', $this->analytics_code, PDO::PARAM_STR);
            $stmt->bindValue(':analytics_code_status', $analytics_code_status, PDO::PARAM_INT);
            $stmt->bindValue(':email_notifications', $this->email_notifications, PDO::PARAM_INT);

            return $stmt->execute();
        }
    }