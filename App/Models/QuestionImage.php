<?php

    namespace App\Models;

    use PDO;

    /**
     * Question Images Model
     */
    class QuestionImage extends \Core\Model
    {
        /**
         * Add new image for question function
         *
         * @param $question_id integer - Question id
         * @param $image_name string - Image name (url) from media/images/questions folder
         *
         * @return boolean - True if the image was added, Null otherwise
         */
        public function add(int $question_id, string $image_name)
        {
            $sql = 'INSERT INTO question_images (question_id, image) VALUES (:question_id, :image)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
            $stmt->bindValue(':image', $image_name, PDO::PARAM_STR);

            return $stmt->execute();
        }


        /**
         * Get question's images
         *
         * @param int $question_id - Question id
         *
         * @return mixed - Array with images, Null otherwise
         */
        public static function get(int $question_id)
        {
            $sql = 'SELECT * FROM question_images WHERE question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            $stmt->execute();
                
            return $stmt->fetchAll();
        }


        /**
         * Remove all images for question
         *
         * @param $question_id integer - Question id
         *
         * @return boolean - True if all images was removed for question, False otherwise
         */
        public static function delete(int $question_id)
        {
            $sql = 'DELETE FROM question_images WHERE question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                
                return true;
            
            } else {

                return false;
            }
        }
    }