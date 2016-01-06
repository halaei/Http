<?php
namespace Poirot\Http\Util;

class UCookie
{
    static function parseCookie($header)
    {
        $cookies = [];

        $cookie = new cookie();

        $parts = explode("=",$header);
        for ($i=0; $i< count($parts); $i++) {
            $part = $parts[$i];
            if ($i==0) {
                $key = $part;
                continue;
            } elseif ($i== count($parts)-1) {
                $cookie->set_value($key,$part);
                $cookies[] = $cookie;
                continue;
            }
            $comps = explode(" ",$part);
            $new_key = $comps[count($comps)-1];
            $value = substr($part,0,strlen($part)-strlen($new_key)-1);
            $terminator = substr($value,-1);
            $value = substr($value,0,strlen($value)-1);
            $cookie->set_value($key,$value);
            if ($terminator == ",") {
                $cookies[] = $cookie;
                $cookie = new cookie();
            }

            $key = $new_key;
        }

        return $cookies;
    }
}

class cookie {
    public $name = "";
    public $value = "";
    public $expires = "";
    public $domain = "";
    public $path = "";
    public $secure = false;

    public function set_value($key,$value) {
        switch (strtolower($key)) {
            case "expires":
                $this->expires = $value;
                return;
            case "domain":
                $this->domain = $value;
                return;
            case "path":
                $this->path = $value;
                return;
            case "secure":
                $this->secure = ($value == true);
                return;
        }
        if ($this->name == "" && $this->value == "") {
            $this->name = $key;
            $this->value = $value;
        }
    }
}