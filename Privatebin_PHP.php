<?php

declare(strict_types = 1);

/*

Copyright (c) 2020 CyVaX

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

namespace Cyvax;


use Exception;
use Tuupola\Base58;

class PrivatebinPHP
{
    private $options = [
        "url" => "https://paste.i2pd.xyz/",
        "version" => 2,
        "compression" => "zlib",
        "formatter" => "plaintext",
        "attachment" => null,
        "attachment_name" => null,
        "password" => null,
        "expire" => "1day",
        "discussion" => false,
        "burn" => false,
        "text" => "",
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, (array) $options);
    }

    /**
     * set paste password
     * @param string $password
     */
    public function set_password(string $password)
    {
        $this->options = array_merge($this->options, ["password" => $password]);
    }

    /**
     * set paste password
     * @param string $formatter
     * @param bool $bypass
     */
    public function set_formatter(string $formatter, bool $bypass = false)
    {
        if (in_array($formatter, ["plaintext", "syntaxhighlighting", "markdown"]) || $bypass) {
            $this->options = array_merge($this->options, ["formatter" => $formatter]);
        } else {
            trigger_error('$formatter not in default value and $bypass is false, using default value...', E_USER_WARNING);
        }
    }

    /**
     * set the attachment, use file_location as url or path, use filename to force a filename.
     * @param string $file_location
     * @param string $filename
     */
    public function set_attachment(string $file_location, string $filename= null)
    {
        $file = file_get_contents($file_location);
        if ($file) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_buffer($finfo, $file);
            if (!$mime) {
                $mime = "application/octet-stream";
            }
            $data = "data:" . $mime . ";base64," . base64_encode($file);
            if (is_null($filename)) {
                $name = basename($file_location);
            } else {
                $name = $filename;
            }
            $this->options = array_merge($this->options, ["attachment" => $data, "attachment_name" => $name]);
        }
    }

    /**
     * set the text of the paste!
     * @param string $text
     */
    public function set_text(string $text)
    {
        $this->options["text"] = $text;
    }
    /**
     * set compression method
     * @param string $compression : zlib or none
     */
    public function set_compression(string $compression)
    {
        if (in_array($compression, ["zlib", "none"])) {
            $this->options = array_merge($this->options, ["compression" => $compression]);
        } else {
            trigger_error('Unknown compression type, (zlib or none)...', E_USER_ERROR);
        }
    }

    /**
     * set discussion true or false, (default : true)
     * setting this to true will desactivate burn if it's to true.
     * @param bool $discussion
     */
    public function set_discussion(bool $discussion)
    {
        if ($this->options["burn"]) {
            $this->options["burn"] = false;
        }
        $this->options["discussion"] = $discussion;
    }

    /**
     * set burn true or false, (default : false)
     * setting this to true will desactivate discussion if it's to true.
     * @param bool $burn
     */
    public function set_burn(bool $burn)
    {
        if ($this->options["discussion"]) {
            $this->options["discussion"] = false;
        }
        $this->options["burn"] = $burn;
    }

    /**
     * set expire time, (default : 1day)
     * use bypass for value not in ["5min", "10min", "1hour", "1day", "1week", "1month", "1year", "never"]. (default : false)
     * @param string $expire
     * @param bool $bypass
     */
    public function set_expire(string $expire, bool $bypass = false)
    {
        if (in_array($expire, ["5min", "10min", "1hour", "1day", "1week", "1month", "1year", "never"]) || $bypass) {
            $this->options = array_merge($this->options, ["expire" => $expire]);
        } else {
            trigger_error('$expire not in default value and $bypass is false, using default value...', E_USER_WARNING);
        }
    }

    /**
     * Get paste_data_json.
     * @return array
     */
    private function get_paste_data(): array
    {
        $paste_data = ["paste" => $this->options["text"]];
        if (!is_null($this->options["attachment"])) {
            $paste_data = array_merge($paste_data, ["attachment" => $this->options["attachment"], "attachment_name" => $this->options["attachment_name"]]);
        }
        return $paste_data;
    }

    /**
     * Encode string to a paste, return http post requests data with b58 key.
     * @return array|Exception[]
     */
    public function encode(): array
    {
        $base58 = new Base58(["characters" => Base58::BITCOIN]);
        try {
            $iv = random_bytes(16);
            $salt = random_bytes(8);
            $password = random_bytes(32);
            $b58 = $base58->encode($password);
            $auth_data = [[base64_encode($iv), base64_encode($salt), 100000, 256, 128, "aes", "gcm", $this->options["compression"]],
                $this->options["formatter"], (int) $this->options["discussion"], (int) $this->options["burn"]];
            if ($this->options["password"]) {
                $key = openssl_pbkdf2($password . $this->options["password"], $salt, 32, 100000, 'sha256');
            } else {
                $key = openssl_pbkdf2($password, $salt, 32, 100000, 'sha256');
            }
            $paste_data = $this->get_paste_data();
            var_dump($paste_data);
            if (!empty($paste_data)) {
                if ($this->options["compression"] == "zlib") {
                    $paste = gzdeflate(json_encode($paste_data));
                } else {
                    $paste = json_encode($paste_data, JSON_UNESCAPED_SLASHES);
                }
                $crypt = openssl_encrypt($paste, 'aes-256-gcm', $key, $options = OPENSSL_RAW_DATA,
                    $iv, $tag, json_encode($auth_data, JSON_UNESCAPED_SLASHES), 16);
                $data = array(
                    "v" => $this->options["version"],
                    "auth_data" => $auth_data,
                    "ct" => base64_encode($crypt . $tag),
                    "meta" => array(
                        "expire" => $this->options["expire"]
                    )
                );
                return array("data" => $data, "b58" => $b58);
            } else {
                trigger_error("Empty PASTE ! use `set_attachment` or `set_text` before post!");
                return array("error" => "Empty PASTE ! use `set_attachment` or `set_text` before post!");
            }
        } catch (Exception $e) {
            return array("error" => $e);
        }
    }

    public function post(array $data): array
    {
        if (array_key_exists("data", $data) && array_key_exists("b58", $data) ) {
            $ch = curl_init($this->options["url"]);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data["data"], JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-Requested-With: JSONHttpRequest'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = json_decode(curl_exec($ch));
            curl_close($ch);

            return array(
                "requests_result" => $result,
                "b58"=> $data["b58"]
            );
        }
        trigger_error('Wrong data.', E_USER_WARNING);
        return array("error"=>"Wrong data...");
    }

    public function encode_and_post(): array
    {
        $raw_data = $this->encode();
        return $this->post($raw_data);
    }
}
