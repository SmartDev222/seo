<?php

namespace Studio\Forms;

class Form
{
    /**
     * An array of any errors that occurred during validation/post
     */
    public $errors;

    /**
     * Outputs HTML errors for all errors in the form.
     */
    public function showErrors() {
        foreach ($this->errors as $error) {
            echo "<div class=\"error\">$error</div>";
        }
    }

    /**
     * Validates the posted data
     * @return boolean True if no errors occurred during validation
     */
    public function validate() {

    }
    
    /**
     * Executes processes and procedures for the posted data
     */
    public function post() {

    }
}
