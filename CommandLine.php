<?php
class CommandLine
{
    public static $args;

    public static function parseArgv($argv = null)
    {
        $argv = $argv !== null ? $argv : $_SERVER['argv'];

        $out = [0 => array_shift($argv)];
        $countArgs = count($argv);
        for ($i = 0, $j = $countArgs; $i < $j; $i++) {
            $arg = $argv[$i];
            if (substr($arg, 0, 2) === '--') { // --foo --bar=baz
                $eqPos = strpos($arg, '=');
                if ($eqPos === false) { // --foo
                    $key = substr($arg, 0);
                    if ($i + 1 < $j && $argv[$i + 1][0] !== '-') { // --foo value
                        $value = $argv[$i + 1];
                        $i++;
                    } else {
                        $value = isset($out[$key]) ? $out[$key] : true;
                    }
                    $out[$key] = $value;
                } else { // --bar=baz
                    $key = substr($arg, 0, $eqPos );
                    $value = substr($arg, $eqPos + 1);
                    $out[$key] = $value;
                }
            } else { // -k=value -abc
                if (substr($arg, 0, 1) === '-') {
                    if (substr($arg, 2, 1) === '=') { // -k=value
                        $key = substr($arg, 0, 2);
                        $value = substr($arg, 3);
                        $out[$key] = $value;
                    } else { // -abc
                        $chars = str_split(substr($arg, 1));
                        foreach ($chars as $char) {
                            $key = '-'.$char;
                            $value = isset($out[$key]) ? $out[$key] : true;
                            $out[$key] = $value;
                        }
                        if ($i + 1 < $j && $argv[$i + 1][0] !== '-') { // -a value1 -abc value2
                            $out[$key] = $argv[$i + 1];
                            $i++;
                        }
                    }
                } else { // plain-arg
                    $value = $arg;
                    $out[] = $value;
                }
            }
        }

        self::$args = $out;

        return $out;
    }

    public static function parseString($command)
    {
        $arguments = false;
        $quote = "(?:'(?:[^']|\\\\')*')";
        $doubleQuote = '(?:"(?:[^"]|\\\\")*")';
        $withoutQuote = '(?:[^\'"\s]+)';
        $name = '(?:^|\s+)(?:-{0,2}\w+=?)';
        if (preg_match_all("%(?<argv>{$name}?({$quote}|{$doubleQuote}|{$withoutQuote}))%i", $command, $matches)) {
            $arguments = array_map(function($a) {
                $a = trim($a);
                $a = preg_replace('%(^["\']|["\']$|(?<==)["\'])%', '', $a);
                return $a;
            }, $matches['argv']);
        }
        return self::parseArgv($arguments);
    }

    public static function getBoolean($key, $default = false)
    {
        if (!isset(self::$args[$key])) {
            return $default;
        }
        $value = self::$args[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (bool)$value;
        }

        if (is_string($value)) {
            $value = strtolower($value);
            $map = [
                'y' => true,
                'n' => false,
                'yes' => true,
                'no' => false,
                'true' => true,
                'false' => false,
                '1' => true,
                '0' => false,
                'on' => true,
                'off' => false,
            ];
            if (isset($map[$value])) {
                return $map[$value];
            }
        }

        return $default;
    }
}
