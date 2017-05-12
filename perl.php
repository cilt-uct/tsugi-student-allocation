<?php

echo("<pre>\n");
echo("Hello\n");
echo(shell_exec ( '/bin/bash zap.sh bob' ));
echo("Back from shell\n");
echo("</pre>\n");
