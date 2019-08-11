<?php

    namespace App\Controllers;

    use App\Models\User;

    /**
     * Account Controller
     */
    class Account extends \Core\Controller
    {
        /**
         * Function which checks if email is available for a new signup
         * using AJAX method.
         *
         * @return void
         */
        public function validateEmailAction()
        {
            $is_valid = ! User::emailExists($_GET['email']);
            
            header('Content-Type: application/json');
            echo json_encode($is_valid);
        }


        /**
         * Function which checks if username is available for a new signup
         * using AJAX method.
         *
         * @return void
         */
        public function validateUsernameAction()
        {
            $is_valid = ! User::usernameExists($_GET['username']);
            
            header('Content-Type: application/json');
            echo json_encode($is_valid);
        }
    }