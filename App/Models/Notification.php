<?php

    namespace App\Models;

    use PDO;
    use \Core\View;
    use \App\Mail;
    use \App\Config;
    use \App\Models\Question;
    use \App\Models\User;

    /**
     * Notification Model
     */
    class Notification extends \Core\Model
    {
        /**
         * Add new notification function
         *
         * @param array $params - array with parameters.
         * Types:
         * - na: New Answer
         * - nm: New Mention
         * - cq: Closed Question
         * - da: Deleted answer (From admin panel)
         * - dq: Deleted Question (From admin panel)
         *
         * @return boolean - True if added, False otherwise
         */
        public static function add(array $params = [])
        {
            $to_user = $params['to_user'];
            $question_id = $params['question_id'] ?? 0;
            $answer_id = $params['answer_id'] ?? 0;
            $type = (in_array($params['type'], ['na', 'nm', 'cq', 'da', 'dq'])) ? $params['type'] : '';
            $from_user = $params['from_user'] ?? null;

            if ($to_user > 0 && ($question_id > 0 || $answer_id > 0) && $type <> '') {

                $sql = 'INSERT INTO notifications (to_user, question_id, answer_id, type, from_user, created_at, viewed) VALUES (:to_user, :question_id, :answer_id, :type, :from_user, :created_at, :viewed)';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':to_user', $to_user, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);
                $stmt->bindValue(':type', $type, PDO::PARAM_STR);
                $stmt->bindValue(':from_user', $from_user, PDO::PARAM_INT);
                $stmt->bindValue(':created_at', time(), PDO::PARAM_INT);
                $stmt->bindValue(':viewed', 0, PDO::PARAM_INT);

                if ($stmt->execute()) {

                    if (Config::getValues('email_notifications') == 1) {

                        $notification = '';

                        switch($params['type']) {

                            case 'na':  {

                                $question = Question::getById($question_id);
                                $notification = 'New answer for "'.$question->title.'"';

                                break;
                            }

                            case 'nm':  {

                                $question = Question::getById($question_id);
                                $notification = 'New mention in "'.$question->title.'"';

                                break;
                            }

                            case 'cq':  {

                                $question = Question::getById($question_id);
                                $notification = 'Question "'.$question->title.'" was closed';

                                break;
                            }

                            case 'dq':  {

                                $question = Question::getById($question_id, 1);
                                $notification = 'Question "'.$question->title.'" was deleted by administration';

                                break;
                            }

                            case 'da':  {

                                $answer = Answer::getById($answer_id, 1);
                                $notification = 'Answer "'.strip_tags($answer->answer).'" was deleted by administration';

                                break;
                            }
                        }

                        static::sendToUserEmail($to_user, $notification);
                    }

                    return true;
                }
            }

            return false;
        }


        /**
         * Send notification to user's email if option is enabled
         *
         * @param int $email - User's id who will receive notification
         * @param string $notification - Notification which should be sent
         *
         * @return void()
         */
        public static function sendToUserEmail(int $user_id, string $notification)
        {
            $user = User::getById($user_id);

            if ($user->email_notifications == 1) {

                $url = 'http://' . $_SERVER['HTTP_HOST'] . '/notifications';

                $text = View::getTemplate('email_templates/notification.txt', ['url' => $url, 'notification' => $notification]);
                $html = View::getTemplate('email_templates/notification.html', ['url' => $url, 'notification' => $notification]);

                Mail::send($user->email, 'New Notification', $text, $html);
            }
        }


        /**
         * Send all notification to user's email during one hour if 
         * user enabled email notifications (Cron notification)
         *
         * @return void()
         */
        public static function sendAll()
        {
            /* Getting users who has enabled notifications to email */
            $usersList = User::get(['email_notifications' => 1]);

            /* Getting unviewed notifications for last hour for each user */
            foreach($usersList as $user) {

                $notificationsCounter = [
                    'na' => 0,
                    'nm' => 0,
                    'cq' => 0,
                    'dq' => 0,
                    'da' => 0,
                ];

                $notifications = static::get([
                    'to_user' => $user['id'],
                    'viewed' => 0, 
                    'added_after' => strtotime('-1 hour'),
                ]);

                if ($notifications) {

                    /* Creating email with all notifications */
                    foreach ($notifications as $notification) {

                        $notificationType = $notification['type'];
                        $notificationsCounter[$notificationType]++;
                    }

                    $notificationsMessage = [];
                    $notificationsMessageHtml = [];
                    if ($notificationsCounter['na'] > 0) {

                        $notificationsMessage[] = 'New '.$notificationsCounter['na'].' answers';
                        $notificationsMessageHtml[] = 'New '.$notificationsCounter['na'].' answers';
                    }

                    if ($notificationsCounter['nm'] > 0) {

                        $notificationsMessage[] = 'New '.$notificationsCounter['na'].' mentions';
                        $notificationsMessageHtml[] = 'New '.$notificationsCounter['na'].' mentions';
                    }

                    if ($notificationsCounter['cq'] > 0) {

                        $notificationsMessage[] = $notificationsCounter['cq'].' questions was closed';
                        $notificationsMessageHtml[] = $notificationsCounter['cq'].' questions was closed';
                    }

                    if ($notificationsCounter['dq'] > 0) {

                        $notificationsMessage[] = $notificationsCounter['dq'].' questions was deleted';
                        $notificationsMessageHtml[] = $notificationsCounter['dq'].' questions was deleted';
                    }

                    if ($notificationsCounter['da'] > 0) {

                        $notificationsMessage[] = $notificationsCounter['da'].' answers was deleted';
                        $notificationsMessageHtml[] = $notificationsCounter['da'].' answers was deleted';
                    }

                    /* Sending an email */
                    $url = 'http://' . $_SERVER['HTTP_HOST'] . '/notifications';

                    $text = View::getTemplate('email_templates/notifications.txt', ['url' => $url, 'notifications' => $notificationsMessage]);
                    $html = View::getTemplate('email_templates/notifications.html', ['url' => $url, 'notifications' => $notificationsMessageHtml]);

                    Mail::send($user['email'], 'New Notifications', $text, $html);
                }
            }
        }


        /**
         * Function which returns number of notifications
         *
         * @param array $params - Array with parameters
         *
         * @return int - number of notifications
         */
        public static function count(array $params = [])
        {
            if (isset($params['to_user']) && $params['to_user'] > 0) {

                $viewed_sql = (isset($params['viewed'])) ? ' AND viewed = :viewed' : '';
                $added_before_sql = (isset($params['added_before'])) ? ' AND created_at < :added_before' : '';
                $added_after_sql = (isset($params['added_after'])) ? ' AND created_at > :added_after' : '';

                $sql = 'SELECT COUNT(id) AS total FROM notifications WHERE to_user = :to_user'.$viewed_sql.$added_before_sql.$added_after_sql.' LIMIT 1';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':to_user', $params['to_user'], PDO::PARAM_INT);
                if ($viewed_sql) {
                    $stmt->bindValue(':viewed', $params['viewed'], PDO::PARAM_INT);
                }
                if ($added_before_sql) {
                    $stmt->bindValue(':added_before', $params['added_before'], PDO::PARAM_INT);
                }
                if ($added_after_sql) {
                    $stmt->bindValue(':added_after', $params['added_after'], PDO::PARAM_INT);
                }

                $stmt->execute();
                $return = $stmt->fetch();

                return $return['total'];
            }

            return 0;
        }


        /**
         * Function which returns notifications
         *
         * @param array $params - Array with parameters
         *
         * @return mixed - Notifications array with objects if found, Null otherwise
         */
        public static function get(array $params = [])
        {
            if (isset($params['to_user']) && $params['to_user'] > 0) {

                $params['limit']        = $params['limit']      ?? 100;
                $params['offset']       = $params['offset']     ?? 0;
                $params['order_by']     = $params['order_by']   ?? 'id';
                $params['order_type']   = $params['order_type'] ?? 'DESC';

                $viewed_sql = (isset($params['viewed'])) ? ' AND viewed = :viewed' : '';
                $added_before_sql = (isset($params['added_before'])) ? ' AND created_at < :added_before' : '';
                $added_after_sql = (isset($params['added_after'])) ? ' AND created_at > :added_after' : '';

                $sql = 'SELECT * FROM notifications WHERE to_user = :to_user'.$viewed_sql.$added_before_sql.$added_after_sql.' ORDER BY '.$params['order_by'].' '.$params['order_type'].' LIMIT :offset, :limit';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':to_user', $params['to_user'], PDO::PARAM_INT);
                if ($viewed_sql) {
                    $stmt->bindValue(':viewed', $params['viewed'], PDO::PARAM_INT);
                }
                if ($added_before_sql) {
                    $stmt->bindValue(':added_before', $params['added_before'], PDO::PARAM_INT);
                }
                if ($added_after_sql) {
                    $stmt->bindValue(':added_after', $params['added_after'], PDO::PARAM_INT);
                }
                $stmt->bindValue(':offset', intval($params['offset']), PDO::PARAM_INT);
                $stmt->bindValue(':limit', intval($params['limit']), PDO::PARAM_INT);

                $stmt->execute();

                return $stmt->fetchAll();
            }
        }


        /**
         * Delete notification function
         *
         * @param int $notification_id - Notication ID, which should be deleted
         *
         * @return boolean - True if deleted or Null otherwise
         */
        public static function delete(int $notification_id)
        {
            $sql = 'DELETE FROM notifications WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $notification_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Mark notifications as viewed
         *
         * @param int $user_id - User ID, which mark notifications as viewed
         *
         * @return boolean - True if updated or Null otherwise
         */
        public static function setAllAsViewed(int $user_id)
        {
            $sql = 'UPDATE notifications SET viewed = 1 WHERE to_user = :to_user AND viewed = 0';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':to_user', $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        }
    }