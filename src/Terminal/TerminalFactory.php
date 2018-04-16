<?php

namespace PhpSchool\CliMenu\Terminal;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class TerminalFactory
{
    /**
     * @return TerminalInterface
     */
    public static function fromSystem()
    {
        return new UnixTerminal();
    }
}
