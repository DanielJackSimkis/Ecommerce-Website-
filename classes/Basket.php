<?php
    class Basket{
        private $name = "":
        private $total = 0.00;
        private $price = 0.00;
        private $items = array();
        
        public static function setName($name){
            $this->$name;
        }
        
        public static function getName(){
            return $name;
        }
        
        public static function addTotal($price){
            $this->$total += $price;
        }
        
        public static function removeFromTotal($price){
            $this->$total -= $price;
        }
        
        public static function getTotal(){
            return $this->$total;
        }
        
        public static function setPrice($price){
            $this->$price = $price;
        }
        
        public static function getPrice(){
            return $price;
        }
        
        public static function addItems($id){
            array_push($items, $id)
        } 
        
        public static function getItems(){
            return $this->$items
        }
    }
?>