<?php

namespace App\Helpers;

    class Cypher {
        public function encrypt($text, $salt) {
            // encode text using base64
            $encoded = base64_encode($text);

            // string to array for $text
            $arr = str_split($encoded);

            // string to array for $salt
            $arrSalt = str_split($salt);

            // arr of salt
            $lastSaltLetter = 0;

            // encrypted string
            $encrypted = [];

            // encrypt
            for ($x = 0; $x < sizeof($arr); $x++) {
                $letter = $arr[$x];
                $saltLetter = $arrSalt[$lastSaltLetter];

                $temp = $this->getLetterFromAlphabetForLetter($saltLetter, $letter);

                if (isset($temp) || $temp === 0) {
                    // concat to the final response encrypted string
                    array_push($encrypted, $temp);
                } 
                else {
                    // if any error, return null
                    return null;
                }		

                /*
                    This is important: if we're out of letters in our 
                    password, we need to start from the begining.
                */
                if ($lastSaltLetter == (count($arrSalt) - 1)) {
                    $lastSaltLetter = 0;
                } else {
                    ++$lastSaltLetter;
                }
            }
            // We finally return the encrypted string
            return implode('', $encrypted);

        }

        public function encryptID($text, $salt) {
            // encode text using base64
            $encoded = base64_encode($text);

            // string to array for $text
            $arr = str_split($encoded);

            // string to array for $salt
            $arrSalt = str_split($salt);

            // arr of salt
            $lastSaltLetter = 0;

            // encrypted string
            $encrypted = [];

            // encrypt
            for ($x = 0; $x < sizeof($arr); $x++) {
                $letter = $arr[$x];
                $saltLetter = $arrSalt[$lastSaltLetter];

                $temp = $this->getLetterFromAlphabetForLetterID($saltLetter, $letter);

                if (isset($temp) || $temp === 0) {
                    // concat to the final response encrypted string
                    array_push($encrypted, $temp);
                } 
                else {
                    // if any error, return null
                    return null;
                }		

                /*
                    This is important: if we're out of letters in our 
                    password, we need to start from the begining.
                */
                if ($lastSaltLetter == (count($arrSalt) - 1)) {
                    $lastSaltLetter = 0;
                } else {
                    ++$lastSaltLetter;
                }
            }
            // We finally return the encrypted string
            return implode('', $encrypted);

        }

        public function getLetterFromAlphabetForLetter($letter, $letterToChange) {
            // this is the alphabet we know, plus numbers and the = sign 
            $abc = 'abcdefghijklmnopqrstuvwxyz0123456789=ABCDEFGHIJKLMNOPQRSTUVWXYZ":,}';

            // get the position of the given letter, according to our abc
            $posLetter = strpos($abc, $letter);

            // if we cannot get it, then we can't continue
            echo($posLetter == -1);
            if ($posLetter == -1) {
                echo('Password letter ' . $letter . ' not allowed.');
                return null;
            }

            // according to our abc, get the position of the letter to encrypt
            $posLetterToChange = strpos($abc, $letterToChange);

            // again, if any error, we cannot continue...
            if ($posLetterToChange == -1) {
                echo('Password letter ' .$letter. ' not allowed.');
                return null;
            }

            // let's build the new abc. this is the important part
            $part1 = substr($abc, $posLetter, strlen($abc));
            // substr($str, $start, $end - $start);
            $part2 = substr($abc, 0, $posLetter);
            $newABC = $part1.$part2;
            
            // we get the encrypted letter
            $letterAccordingToAbc = str_split($newABC)[$posLetterToChange];

            // and return to the routine...
            return $letterAccordingToAbc;
        }

        public function getLetterFromAlphabetForLetterID($letter, $letterToChange) {
            // this is the alphabet we know, plus numbers and the = sign 
            $abc = 'abcdefghijklmnopqrstuvwxyz0123456789=ABCDEFGHIJKLMNOPQRSTUVWXYZ';

            // get the position of the given letter, according to our abc
            $posLetter = strpos($abc, $letter);

            // if we cannot get it, then we can't continue
            if ($posLetter == -1) {
                echo('Password letter ' . $letter . ' not allowed.');
                return null;
            }

            // according to our abc, get the position of the letter to encrypt
            $posLetterToChange = strpos($abc, $letterToChange);

            // again, if any error, we cannot continue...
            if ($posLetterToChange == -1) {
                echo('Password letter ' .$letter. ' not allowed.');
                return null;
            }

            // let's build the new abc. this is the important part
            $part1 = substr($abc, $posLetter, strlen($abc));
            // substr($str, $start, $end - $start);
            $part2 = substr($abc, 0, $posLetter);
            $newABC = $part1.$part2;
            
            // we get the encrypted letter
            $letterAccordingToAbc = str_split($newABC)[$posLetterToChange];

            // and return to the routine...
            return $letterAccordingToAbc;
        }

        public function decrypt($salt, $text){
            // convert the string to decrypt into an array
            $arr = str_split($text);
        
            // let's also create an array from our password
            $arrSalt = str_split($salt);
        
            // keep control about which letter from the password we use
            $lastSaltLetter = 0;
        
            // this is the final decrypted string
            $decrypted = [];
        
            // let's start...
            for ($x=0; $x < sizeof($arr); $x++) {
        
                // next letter from the string to decrypt
                $letter = $arr[ $x ];
        
                // get the next letter from the password
                $saltLetter = $arrSalt[ $lastSaltLetter ];
        
                // get the decrypted letter according to the password
                $temp = $this->getInvertedLetterFromAlphabetForLetter( $saltLetter, $letter );       
                if (isset($temp) || $temp === 0) {
                    // concat the response
                    array_push($decrypted, $temp);
                } else {
                    // if any error, return null
                    return null;
                }		
        
                // if our password is too short, let's start again from the first letter
                if ($lastSaltLetter == (sizeof($arrSalt) - 1) ) {
                    $lastSaltLetter = 0;
                } else {
                    ++$lastSaltLetter;
                }

            }
        
            // return the decrypted string and converted from base64 to plain text
            return base64_decode(implode('', $decrypted));
         
        }

        public function decryptID($salt, $text){
            // convert the string to decrypt into an array
            $arr = str_split($text);
        
            // let's also create an array from our password
            $arrSalt = str_split($salt);
        
            // keep control about which letter from the password we use
            $lastSaltLetter = 0;
        
            // this is the final decrypted string
            $decrypted = [];
        
            // let's start...
            for ($x=0; $x < sizeof($arr); $x++) {
        
                // next letter from the string to decrypt
                $letter = $arr[ $x ];
        
                // get the next letter from the password
                $saltLetter = $arrSalt[ $lastSaltLetter ];
        
                // get the decrypted letter according to the password
                $temp = $this->getInvertedLetterFromAlphabetForLetterID( $saltLetter, $letter );       
                if (isset($temp) || $temp === 0) {
                    // concat the response
                    array_push($decrypted, $temp);
                } else {
                    // if any error, return null
                    return null;
                }		
        
                // if our password is too short, let's start again from the first letter
                if ($lastSaltLetter == (sizeof($arrSalt) - 1) ) {
                    $lastSaltLetter = 0;
                } else {
                    ++$lastSaltLetter;
                }

            }
        
            // return the decrypted string and converted from base64 to plain text
            return base64_decode(implode('', $decrypted));
         
        }
        
        public function getInvertedLetterFromAlphabetForLetter($letter, $letterToChange){
            // the alphabet together with numbers and the equal sign 
            $abc = 'abcdefghijklmnopqrstuvwxyz0123456789=ABCDEFGHIJKLMNOPQRSTUVWXYZ":,}';
        
            $posLetter = strpos($abc, $letter);
    
            if ($posLetter == -1) {
                echo 'Password letter '.$letter.' not allowed.';
                return null;
            }

            // let's build the new abc. this is the important part
            $part1 = substr($abc, $posLetter, strlen($abc));
            $part2 = substr($abc, 0, $posLetter);
            $newABC = $part1.$part2;
    
            $posLetterToChange = strpos($newABC, $letterToChange);
    
            if ($posLetterToChange == -1) {
                echo 'Password letter ' .$letter. ' not allowed.';
                return null;
            }
    
            $letterAccordingToAbc = str_split($abc)[$posLetterToChange];
    
            return $letterAccordingToAbc;
        
        }

        public function getInvertedLetterFromAlphabetForLetterID($letter, $letterToChange){
            // the alphabet together with numbers and the equal sign 
            $abc = 'abcdefghijklmnopqrstuvwxyz0123456789=ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
            $posLetter = strpos($abc, $letter);
    
            if ($posLetter == -1) {
                echo 'Password letter '.$letter.' not allowed.';
                return null;
            }

            // let's build the new abc. this is the important part
            $part1 = substr($abc, $posLetter, strlen($abc));
            $part2 = substr($abc, 0, $posLetter);
            $newABC = $part1.$part2;
    
            $posLetterToChange = strpos($newABC, $letterToChange);
    
            if ($posLetterToChange == -1) {
                echo 'Password letter ' .$letter. ' not allowed.';
                return null;
            }
    
            $letterAccordingToAbc = str_split($abc)[$posLetterToChange];
    
            return $letterAccordingToAbc;
        
        }
    }