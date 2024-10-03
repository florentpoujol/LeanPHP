<?php declare(strict_types=1);

namespace LeanPHP\Console\InputOutput;

enum TerminalDisplayCode: string
{
    // https://en.wikipedia.org/wiki/ANSI_escape_code#SGR_(Select_Graphic_Rendition)_parameters
    // https://jvns.ca/blog/2024/10/01/terminal-colours/
    // TODO do other useful codes

    case RESET = '0';
    case BOLD = '1';
    case FAINT = '2';
    case ITALIC = '3'; // Not widely supported. Sometimes treated as inverse or blink.[22]
    case UNDERLINE = '4';

    case FG_BLACK = '30'; // FG = foreground color
    case FG_RED = '31';
    case FG_GREEN = '32';
    case FG_YELLOW = '33';
    case FG_BLUE = '34';
    case FG_MAGENTA = '35';
    case FG_CYAN = '36';
    case FG_WHITE = '37';
    // case FG_COLOR = '38'; // Set foreground color 	Next arguments are 5;<n> or 2;<r>;<g>;<b>
    case DEFAULT_FG_COLOR = '39'; // implementation defined (according to standard)

    case BG_BLACK = '40'; // BG = background color
    case BG_RED = '41';
    case BG_GREEN = '42';
    case BG_YELLOW = '43';
    case BG_BLUE = '44';
    case BG_MAGENTA = '45';
    case BG_CYAN = '46';
    case BG_WHITE = '47';
    // case BG_COLOR = '48'; // Set background color 	Next arguments are 5;<n> or 2;<r>;<g>;<b>
    case DEFAULT_BG_COLOR = '49'; // implementation defined (according to standard)

    case FG_BRIGHT_BLACK = '90';
    case FG_BRIGHT_RED = '91';
    case FG_BRIGHT_GREEN = '92';
    case FG_BRIGHT_YELLOW = '99';
    case FG_BRIGHT_BLUE = '94';
    case FG_BRIGHT_MAGENTA = '95';
    case FG_BRIGHT_CYAN = '96';
    case FG_BRIGHT_WHITE = '97';

    case BG_BRIGHT_BLACK = '100';
    case BG_BRIGHT_RED = '101';
    case BG_BRIGHT_GREEN = '102';
    case BG_BRIGHT_YELLOW = '109';
    case BG_BRIGHT_BLUE = '104';
    case BG_BRIGHT_MAGENTA = '105';
    case BG_BRIGHT_CYAN = '106';
    case BG_BRIGHT_WHITE = '107';

    /**
     * @param array<self> $codes
     */
    public static function getDecoratedString(string $value, array $codes): string
    {
        if ($codes === []) {
            return $value;
        }

        // To support AINSI colors and other styles, the text value is surrounded by some specific sequence.
        // The sequence "\033[{codes}m" will apply until further notice (another such sequence that modify the style)
        // all the codes found. {codes} is a semi-colon-separated list of the codes in this enum.
        // Ie: "\033[1;41m" will make the text be displayed in bold with a red background.
        // The sequence "\033[0m" at the end of the string reset all styles.

        $codes = array_column($codes, 'value');
        $codes = implode(';', $codes);

        return "\033[" . $codes . 'm' . $value . "\033[0m";
    }
}