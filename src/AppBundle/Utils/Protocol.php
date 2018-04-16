<?php
namespace AppBundle\Utils;

/**
 * File Name: Protocol.php
 * Created by Jacobs <jacobs@anviz.com>.
 * Date: 2016-3-22
 * Time: 9:44
 * Description:
 */
class Protocol
{

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: getToken
     * @param int $expires
     * @return array
     * @Description:
     */
    public static function getToken ($expires = 86400)
    {
        /** Get 8 bit random numbers */
        $randomkey = Tools::randomkey(8);
        /** Set the validity period of the random numbers, default is 1 day */
        $randomtime = time() + $expires;
        /** Generate Token */
        $token = substr(sha1(KEY . $randomkey), 16, 8);

        return array (
            'randomkey' => $randomkey,
            'randomtime' => $randomtime,
            'token' => $token
        );
    }

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: explodeCommand
     * @param $token
     * @param $data
     * @return bool
     * @Description:
     */
    public static function explodeCommand ($token, $data)
    {
        if (empty($token) || empty($data))
            return false;

        $data = base64_decode($data);
        $data = Tools::decrypt3DES($data, $token);
        $result["device_id"] = trim(substr($data, 0, 32));
        $result["id"] = trim(substr($data, 32, 8));
        $result["command"] = trim(substr($data, 40, 4));
        $result["length"] = trim(substr($data, 48, 8));
        $result["content"] = @str_pad(substr($data, 56), $result['length'], ' ', STR_PAD_RIGHT);

        return $result;
    }

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: RegisterDevice
     * @param string $data
     * @return array|bool
     * @Description:
     */
    public static function RegisterDevice ($data = '')
    {
        if (empty($data))
            return false;

        $data = base64_decode($data);

        $result = array ();
        /** Serial number */
        $result["serial_number"] = trim(substr($data, 0, 20));
        /** Deivce model */
        $result["model"] = trim(substr($data, 20, 20));
        /** Firmware */
        $result["firmware"] = trim(substr($data, 40, 20));
        /** Communication protocol version */
        $result["protocol"] = trim(substr($data, 60, 20));

        return $result;
    }

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: LoginDevice
     * @param string $content
     * @return array|bool
     * @Description:
     */
    public static function LoginDevice ($content = '')
    {
        if (empty($content))
            return false;

        $result = array ();

        $result['username'] = trim(substr($content, 0, 20));
        $result['dpassword'] = trim(substr($content, 20, 20));

        return $result;
    }

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: NetworkDevice
     * @param string $content
     * @return array|bool
     * @Description:
     */
    public static function NetworkDevice ($content = '')
    {
        if (empty($content))
            return false;

        $result = array ();
        $result['internet'] = ord($content[0]);
        $result['ipaddress'] = ord($content[1]) . "." . ord($content[2]) . "." . ord($content[3]) . "." . ord($content[4]);
        $result['netmask'] = ord($content[5]) . "." . ord($content[6]) . "." . ord($content[7]) . "." . ord($content[8]);
        $result['mac'] = strtoupper(str_pad(sprintf("%x", ord($content[9])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[10])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[11])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[12])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[13])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[14])), 2, "0", STR_PAD_LEFT));
        $result['gateway'] = ord($content[15]) . "." . ord($content[16]) . "." . ord($content[17]) . "." . ord($content[18]);
        $result['serverip'] = ord($content[19]) . "." . ord($content[20]) . "." . ord($content[21]) . "." . ord($content[22]);
        $result['remote'] = ord($content[23]);
        $result['port'] = (ord($content[24]) << 8) + ord($content[25]);
        $result['comm_method'] = ord($content[26]);
        $result['dhcp'] = ord($content[27]);

        return $result;
    }

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: EmployeeDevice
     * @param string $content
     * @return array|bool
     * @Description:
     */
    public static function EmployeeDevice ($content = '')
    {
        if (empty($content))
            return false;

        /**
         * The length of each employee information is 40
         * if the length of data can not be 40 whole, it's dirty data
         */
        if (strlen($content) % 40 != 0) {
            return false;
        }

        $result = array ();

        /** the total of employee in this acquisition */
        $count = strlen($content) / 40;
        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 40, 40);

            $record = array ();
            /** ID On Device */
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);

            if (ord($row[5]) == 0xFF and ord($row[6]) == 0xFF and ord($row[7]) == 0xFF) {
                $record['passd'] = '';
            }
            else {
                /** The length of password */
                $passlen = intval(ord($row[5]) >> 4);
                /** Attendance Password */
                $record['passd'] = ((ord($row[5]) & 0x0F) << 16) + (ord($row[6]) << 8) + ord($row[7]);
                $record['passd'] = str_pad($record['passd'], $passlen, '0', STR_PAD_LEFT);
            }

            /** Card number */
            if (ord($row[8]) == 0xFF and ord($row[9]) == 0xFF and ord($row[10]) == 0xFF and ord($row[11]) == 0xFF) {
                $record['cardid'] = '';
            }
            else {
                $record['cardid'] = (ord($row[8]) << 24) + (ord($row[9]) << 16) + (ord($row[10]) << 8) + ord($row[11]);
            }

            /** Last Name */
            $record['last_name'] = '';
            for ($i = 0; $i < 10; $i++) {
                $temp = (ord($row[$i * 2 + 13]) << 8) + ord($row[$i * 2 + 12]);
                if (empty($temp)) {
                    continue;
                }
                $record['last_name'] .= ToolHelper::uni2utf8($temp);
            }
            $record['last_name'] = empty($record['last_name']) ? $record['idd'] : $record['last_name'];

            /** Department ID */
            $record['deptid'] = ord($row[32]);

            /** Group ID */
            $record['group_id'] = ord($row[33]);

            /** The sign of the finger had been register */
            $record['fingersign'] = (ord($row[35]) << 8) + ord($row[36]);

            /** Whether administrator */
            $record['is_admin'] = ord($row[37]);

            $result[$i] = $record;
        }

        return $result;
    }

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: FingerDevice
     * @param string $content
     * @return array|bool
     * @Description:
     */
    public static function FingerDevice ($content = '')
    {
        if (empty($content))
            return false;

        /**
         * The length of each finger information is 344
         * if the length of data can not be 344 whole, it's dirty data
         */
        if (strlen($content) % 344 != 0) {
            return false;
        }
        $result = array ();

        /** the total of finger in this acquisition */
        $count = strlen($content) / 344;
        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 344, 344);

            $record = array ();

            /** ID On Device */
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);
            /**
             * 1: Fingerprint
             * 2: Facepass
             */
            $record['temp_type'] = 1;
            /** The number of finger */
            $record['temp_id'] = ord($row[5]);
            /** the data of finger */
            $record['template'] = substr($row, 6, 338);

            $result[$i] = $record;
        }

        return $result;
    }

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: RecordDevice
     * @param string $content
     * @return array|bool
     * @Description:
     */
    public static function RecordDevice ($content = '')
    {
        if (empty($content))
            return false;

        /**
         * The length of each record is 16
         * if the length of data can not be 16 whole, it's dirty data
         */
        if (strlen($content) % 16 != 0) {
            return false;
        }
        $result = array ();

        /** the total of records in this acquisition */
        $count = strlen($content) / 16;
        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 16, 16);

            $record = array ();

            /** ID On Device */
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);
            /** Check Time */
            $record['checktime'] = (ord($row[5]) << 24) + (ord($row[6]) << 16) + (ord($row[7]) << 8) + ord($row[8]);
            $record['checktime'] = $record['checktime'] + strtotime('2000-01-02 00:00:00');
            /** Check Type */
            $record['checktype'] = ord($row[9]);
            /** Work Type */
            $record['worktype'] = ord($row[10]);

            $result[$i] = $record;
        }

        return $result;
    }

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: joinCommand
     * @param $sha1         Token value
     * @param $device_id
     * @param $id
     * @param $command
     * @param $nexttime
     * @param int $length
     * @param string $content
     * @return bool|string
     * @Description:
     */
    public static function joinCommand ($sha1, $device_id, $id, $command, $nexttime, $length = 0, $content = "")
    {

        if (empty($sha1) || empty($device_id) || empty($id) || empty($command)) {
            return false;
        }

        $id = empty($id) ? '11111111' : str_pad($id, 8, ' ', STR_PAD_LEFT);

        $command = empty($command) ? '0000' : str_pad($command, 4, ' ', STR_PAD_LEFT);
        /** Next heartbeat packet send interval time */
        /** eg.（0，5，10，60，300）*/
        $nextime = empty($command) ? '0005' : str_pad($nexttime, 4, ' ', STR_PAD_LEFT);

        $length = empty($length) ? strlen($content) : $length;
        $length = str_pad($length, 8, 0x00, STR_PAD_LEFT);

        $device_id = str_pad($device_id, 32, 0x00, STR_PAD_LEFT);

        $string = $device_id . $id . $command . $nextime . $length . $content;

        switch ($command) {
            case CMD_REGESTER:
            case CMD_FORBIDDEN:
                return $string;

            default:
                return Tools::encrypt3DES($string, $sha1);
        }
    }
}
