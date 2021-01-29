<?php

namespace Mageseller\ProductImport\Model\Resource\Resolver;

/**
 * @author Patrick van Bergen
 */
class NameToUrlKeyConverter
{
    public function createUrlKeyFromName(string $name)
    {
        $key = $name;
        $key = strtolower($key);
        try {
            $key = $this->sanitizeUTF8($key);
            //  $key = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo "\r\n";
            echo $name;
            die;
        }

        $key = preg_replace("/[^a-z0-9]/", "-", $key);
        $key = preg_replace("/-{2,}/", "-", $key);
        $key = trim($key, '-');

        return $key;
    }
    public static function sanitizeUTF8($value)
    {
        /*if (self::getIsIconvEnabled()) {*/

        // NEW ----------------------------------------------------------------
        $encoding = mb_detect_encoding($value, mb_detect_order(), false);

        if ($encoding == "UTF-8") {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        $value = iconv(mb_detect_encoding($value, mb_detect_order(), false), "UTF-8//IGNORE", $value);
        // --------------------------------------------------------------------

        // OLD --------------------------------------
        // $value = @iconv('UTF-8', 'UTF-8', $value);
        // -------------------------------------------
        return $value;
        /*}

        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');*/

        return $value;
    }

    public function createUniqueUrlKeyFromName(string $name, array $excluded)
    {
        $urlKey = $this->createUrlKeyFromName($name);

        if (in_array($urlKey, $excluded)) {
            $suffix = 0;

            do {
                $suffix++;
                $uniqueUrlKey = $urlKey . '_' . $suffix;
            } while (in_array($uniqueUrlKey, $excluded));

            $urlKey = $uniqueUrlKey;
        }

        return $urlKey;
    }
}
