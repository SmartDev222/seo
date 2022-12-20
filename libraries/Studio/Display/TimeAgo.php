<?php

namespace Studio\Display;

/**
 * Time Ago
 * Developed by Bailey Herbert
 * https://baileyherbert.com/
 *
 * This class was created for SEO Studio and can be used in compliance with the license you purchased for that product.
 * View CodeCanyon license specifications here: http://codecanyon.net/licenses ("Standard")
 */
/**
 * A class to calculate and format a timestamp into "human readable" form (i.e. 4 minutes ago).
 */

 class TimeAgo
 {
     /**
      * int Timestamp
      */
     public $timestamp;
     public $precision;
     private $below;
     private $above;

     public function __construct($timestamp, $precision = 0) {
         $this->timestamp = $timestamp;
         $this->precision = $precision;
     }

     function setBelow($s) {
         $this->below = $s;
         return $this;
     }
     function setAbove($s) {
         $this->above = $s;
         return $this;
     }

     /**
      * Retrieves the human readable form of the timestamp.
      */
     public function get() {
         $t = abs(time() - $this->timestamp);

         $seconds = ($t % 60) / pow(10, $this->precision);
         $minutes = floor(($t / 60) * pow(10, $this->precision)) / pow(10, $this->precision);
         $hours = floor(($t / 3600) * pow(10, $this->precision)) / pow(10, $this->precision);
         $days = floor(($t / 86400) * pow(10, $this->precision)) / pow(10, $this->precision);
         $weeks = floor(($t / (86400 * 7)) * pow(10, $this->precision)) / pow(10, $this->precision);

         $years = $this->getYears();
         $months = $this->getMonths();

         $stamp = "";

         if ($seconds > 0) $stamp = number_format($seconds, $this->precision) ." second{$this->p($seconds)}";
         if ($minutes >= 1) $stamp = number_format($minutes, $this->precision) ." minute{$this->p($minutes)}";
         if ($hours >= 1) $stamp = number_format($hours, $this->precision) ." hour{$this->p($hours)}";
         if ($days >= 1) $stamp = number_format($days, $this->precision) ." day{$this->p($days)}";
         if ($weeks >= 1) $stamp = number_format($weeks, $this->precision) ." week{$this->p($weeks)}";
         if ($months >= 1 && $weeks >= 4) $stamp = number_format($months, $this->precision) ." month{$this->p($months)}";
         if ($years >= 1) $stamp = number_format($years, $this->precision) ." year{$this->p($years)}";

         if ($t == 0) return "just now";

         if ($this->below != null && $this->timestamp < time()) return $this->below;
         if ($this->above != null && $this->timestamp > time()) return $this->above;

         if ($this->timestamp > time()) return "in " . $stamp;
         return $stamp . " ago";
     }

     private function p($time) {
         if ($time > 1) return "s";
         return "";
     }

     private function getMonths() {
         return abs(((date('Y') - date('Y', $this->timestamp)) * 12) + (date('m') - date('m', $this->timestamp)));
     }

     private function getYears() {
         return (time() - $this->timestamp) / (3600 * 24 * 365.25);
     }
 }

?>
