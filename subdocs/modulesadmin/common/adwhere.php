<?php

class GlobalAdArray {

    static function getFilter() {
        // 検索するフィールド
        if (defined("AD_SEARCH_FIELD") && AD_SEARCH_FIELD) {
            $ad_field = AD_SEARCH_FIELD;
        } else {
            $ad_field = "mail";
        }
        
        // 検索パターン
        if (defined("AD_SEARCH_CASE") && AD_SEARCH_CASE) {
            $ad_case = AD_SEARCH_CASE;
        } else {
            $ad_case = "strintmark2";
        }

        $pattern = GlobalAdArray::setPattern($ad_case);
        
        $where = array();
        foreach($pattern as $val) {
            $where[] = "(".$ad_field."=".$val."*)";
        }
        return $where;
    }

    static function setPattern($case) {
        $rs = array();

        // str
        if ($case === "str") {
            return GlobalAdArray::getSearchStr();
        }
        
        // str2
        if ($case === "str2") {
            $ary1 = GlobalAdArray::getSearchStr();
            $ary2 = $ary1;
            foreach($ary1 as $val1) {
                foreach($ary2 as $val2) {
                    $rs[] = $val1.$val2;
                }
            }
            return $rs;
        }
        
        // str3
        if ($case === "str3") {
            $ary1 = GlobalAdArray::setPattern("str2");
            $ary2 = GlobalAdArray::getSearchStr();
            foreach($ary1 as $val1) {
                foreach($ary2 as $val2) {
                    $rs[] = $val1.$val2;
                }
            }
            return $rs;
        }

        // int
        if ($case === "int") {
            return GlobalAdArray::getSearchInt();
        }

        // strint
        if ($case === "strint") {
            $ary1 = GlobalAdArray::getSearchStr();
            $ary2 = GlobalAdArray::getSearchInt();
            return array_merge($ary1,$ary2);
        }

        // strint2
        if ($case === "strint2") {
            $ary1 = GlobalAdArray::setPattern("strint");
            $ary2 = $ary1;
            foreach($ary1 as $val1) {
                foreach($ary2 as $val2) {
                    $rs[] = $val1.$val2;
                }
            }
            return $rs;
        }

        // strint3
        if ($case === "strint3") {
            $ary1 = GlobalAdArray::setPattern("strint2");
            $ary2 = GlobalAdArray::setPattern("strint");
            foreach($ary1 as $val1) {
                foreach($ary2 as $val2) {
                    $rs[] = $val1.$val2;
                }
            }
            return $rs;
        }

        // strmark
        if ($case === "strmark") {
            $ary1 = GlobalAdArray::getSearchStr();
            $ary2 = GlobalAdArray::getSearchMark();
            return array_merge($ary1,$ary2);
        }

        // strintmark
        if ($case === "strintmark") {
            $ary1 = GlobalAdArray::setPattern("strint");
            $ary2 = GlobalAdArray::getSearchMark();
            return array_merge($ary1,$ary2);
        }

        // strintmark2
        if ($case === "strintmark2") {
            $ary1 = GlobalAdArray::setPattern("strintmark");
            $ary2 = GlobalAdArray::setPattern("strintmark");
            foreach($ary1 as $val1) {
                foreach($ary2 as $val2) {
                    $rs[] = $val1.$val2;
                }
            }
            return $rs;
        }

        // strintmark3
        if ($case === "strintmark3") {
            $ary1 = GlobalAdArray::setPattern("strintmark2");
            $ary2 = GlobalAdArray::setPattern("strintmark");
            foreach($ary1 as $val1) {
                foreach($ary2 as $val2) {
                    $rs[] = $val1.$val2;
                }
            }
            return $rs;
        }

        // other
        if ($case === "other") {
            return GlobalAdArray::getSearchOther();
        }
    }

    static function getSearchStr() {
        return array(
                     "a",
                     "b",
                     "c",
                     "d",
                     "e",
                     "f",
                     "g",
                     "h",
                     "i",
                     "j",
                     "k",
                     "l",
                     "m",
                     "n",
                     "o",
                     "p",
                     "q",
                     "r",
                     "s",
                     "t",
                     "u",
                     "v",
                     "w",
                     "x",
                     "y",
                     "z"
                     );
    }

    static function getSearchInt() {
        return array(
                     "0",
                     "1",
                     "2",
                     "3",
                     "4",
                     "5",
                     "6",
                     "7",
                     "8",
                     "9"
                     );
    }

    static function getSearchMark() {
        return array(
                     "-",
                     "_",
                     ".",
                     "+"
                     );
    }

    static function getSearchOther() {
        return array(
                     "*@"
                     );
    }

}
?>
